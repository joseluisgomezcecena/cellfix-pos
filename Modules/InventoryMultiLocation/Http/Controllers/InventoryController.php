<?php

namespace Modules\InventoryMultiLocation\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use DB;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('AdminSidebarMenu');
    }

    public function dashboard()
    {
        $business_id = request()->session()->get('user.business_id');
        $user = auth()->user();

        // Check if user has permission to view all locations for inventory
        if ($user->can('inventory_multi.view_all_locations')) {
            $permitted_locations = 'all';
        } else {
            $permitted_locations = $user->permitted_locations();
        }

        $locations_query = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->where('is_active', 1);

        if ($permitted_locations !== 'all') {
            $locations_query->whereIn('id', $permitted_locations);
        }

        $locations = $locations_query->get();

        $location_stats = [];
        foreach ($locations as $location) {
            $stats = $this->getLocationStats($location->id);
            $location_stats[$location->id] = $stats;
        }

        $recent_transfers = DB::table('inventory_transfers as it')
            ->join('business_locations as bl1', 'it.from_location_id', '=', 'bl1.id')
            ->join('business_locations as bl2', 'it.to_location_id', '=', 'bl2.id')
            ->join('users as u', 'it.created_by', '=', 'u.id')
            ->where('it.business_id', $business_id)
            ->select(
                'it.*',
                'bl1.name as from_location',
                'bl2.name as to_location',
                'u.first_name',
                'u.last_name'
            )
            ->orderBy('it.created_at', 'desc')
            ->limit(10)
            ->get();

        return view('inventorymultilocation::dashboard', compact(
            'locations',
            'location_stats',
            'recent_transfers'
        ));
    }

    public function index(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $user = auth()->user();

        // Check if user has permission to view all locations for inventory
        if ($user->can('inventory_multi.view_all_locations')) {
            $permitted_locations = 'all';
        } else {
            $permitted_locations = $user->permitted_locations();
        }

        $location_id = $request->get('location_id', 'all');

        $locations_query = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->where('is_active', 1);

        if ($permitted_locations !== 'all') {
            $locations_query->whereIn('id', $permitted_locations);
        }

        $locations = $locations_query->get();

        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('parent_id', 0)
            ->get();

        $brands = DB::table('brands')
            ->where('business_id', $business_id)
            ->get();

        $query = DB::table('products as p')
            ->join('variations as v', 'p.id', '=', 'v.product_id')
            ->leftJoin('variation_location_details as vld', function($join) use ($location_id, $permitted_locations) {
                $join->on('v.id', '=', 'vld.variation_id');
                if ($location_id != 'all') {
                    $join->where('vld.location_id', $location_id);
                } elseif ($permitted_locations !== 'all') {
                    $join->whereIn('vld.location_id', $permitted_locations);
                }
            })
            ->leftJoin('business_locations as bl', 'vld.location_id', '=', 'bl.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('p.business_id', $business_id)
            ->whereNotNull('vld.location_id')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.sku',
                'p.alert_quantity',
                'v.id as variation_id',
                'v.name as variation_name',
                'v.sub_sku',
                'v.default_purchase_price',
                'v.default_sell_price',
                'vld.qty_available',
                'vld.location_id',
                'bl.name as location_name',
                'c.name as category_name',
                'b.name as brand_name',
                'u.short_name as unit_name',
                'vld.updated_at as last_updated'
            );

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('p.name', 'like', "%{$search}%")
                  ->orWhere('p.sku', 'like', "%{$search}%")
                  ->orWhere('v.sub_sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('p.category_id', $request->get('category_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('p.brand_id', $request->get('brand_id'));
        }

        if ($request->filled('stock_status')) {
            $status = $request->get('stock_status');
            if ($status == 'low_stock') {
                $query->whereRaw('vld.qty_available <= p.alert_quantity');
            } elseif ($status == 'out_of_stock') {
                $query->where(function($q) {
                    $q->whereNull('vld.qty_available')
                      ->orWhere('vld.qty_available', '<=', 0);
                });
            } elseif ($status == 'in_stock') {
                $query->where('vld.qty_available', '>', 0);
            }
        }

        $inventory = $query->orderBy('p.name')
                           ->orderBy('v.name')
                           ->paginate(50);

        // Enriquecer cada fila con el ÚLTIMO movimiento real (tipo + fecha + origen/destino).
        // Antes solo mostrábamos vld.updated_at, que no es fiable como "último movimiento" —
        // muchas cosas del sistema tocan el updated_at del VLD (garantías, resave, etc).
        $this->enrichLastMovement($inventory->items());

        if ($request->ajax()) {
            return view('inventorymultilocation::partials.inventory_table', compact('inventory', 'locations'));
        }

        return view('inventorymultilocation::inventory', compact(
            'inventory',
            'locations',
            'categories',
            'brands',
            'location_id'
        ));
    }

    public function getLocationData(Request $request)
    {
        $location_id = $request->get('location_id');
        $stats = $this->getLocationStats($location_id);

        return response()->json($stats);
    }

    /**
     * Agrega la propiedad `last_movement` a cada item del inventario, con la info
     * del último movimiento REAL de ese variation en esa sucursal.
     *
     * Fuentes consideradas (todo movimiento que afecta stock, más eventos que el
     * user quiere ver):
     *   - purchase_lines (compras y purchase_transfer entrantes)
     *   - transaction_sell_lines (ventas + sell_transfer + returns)
     *   - stock_adjustment_lines (ajustes manuales)
     *   - warranty_claims (reemplazos por garantía — salida)
     *   - store_repairs (reparación de tienda — evento, no afecta stock)
     *
     * Estrategia: UN solo query UNION ALL por página, no N queries por fila.
     * Con 50 filas → 1 query UNION en vez de 50 queries independientes.
     *
     * Formato del atributo agregado:
     *   $item->last_movement = [
     *     'type' => 'purchase_transfer',
     *     'type_label' => 'Transferencia recibida',
     *     'icon' => 'fa-arrow-down',
     *     'color' => '#2e7d32',
     *     'date' => '2026-08-27 11:14:00',
     *     'ref' => 'de Sucursal Villa Fontana',
     *   ]
     *   o null si no hay ningún movimiento.
     */
    private function enrichLastMovement(array $items): void
    {
        if (empty($items)) return;

        $business_id = request()->session()->get('user.business_id');

        // Recolectar todos los (variation_id, location_id) de la página.
        $pairs = [];
        foreach ($items as $it) {
            $pairs[] = ['v' => (int) $it->variation_id, 'l' => (int) $it->location_id];
        }
        // Construir WHERE (v.id, l.id) IN ((..),(..))
        $vals = collect($pairs)->map(fn($p) => "({$p['v']},{$p['l']})")->implode(',');
        // Escape defensivo: si hay 0 pares no ejecutamos
        if ($vals === '') return;

        // UNION de todas las fuentes de movimiento con un rank por (variation, location).
        // Solo traemos el más reciente por par.
        $sql = "
            SELECT variation_id, location_id, type, ts, ref FROM (
                SELECT
                    pl.variation_id, t.location_id,
                    CASE WHEN t.type = 'purchase' THEN 'purchase'
                         WHEN t.type = 'purchase_transfer' THEN 'purchase_transfer'
                         WHEN t.type = 'opening_stock' THEN 'opening_stock'
                         ELSE t.type END AS type,
                    t.transaction_date AS ts,
                    COALESCE(t.ref_no, t.invoice_no,
                        CASE WHEN t.type = 'purchase_transfer' THEN
                            (SELECT CONCAT('desde ', bl2.name) FROM business_locations bl2
                             INNER JOIN transactions t2 ON t2.return_parent_id IS NULL AND t2.id = t.id
                             WHERE bl2.id = t.location_id LIMIT 1)
                        END,
                        '') AS ref
                FROM purchase_lines pl
                INNER JOIN transactions t ON t.id = pl.transaction_id
                WHERE t.business_id = ?
                  AND (pl.variation_id, t.location_id) IN ({$vals})
                  AND t.type IN ('purchase','purchase_transfer','opening_stock')
                  AND t.status = 'received'

                UNION ALL
                SELECT
                    tsl.variation_id, t.location_id,
                    CASE WHEN tsl.quantity_returned > 0 THEN 'sell_return'
                         WHEN t.type = 'sell_transfer' THEN 'sell_transfer'
                         ELSE 'sell' END AS type,
                    t.transaction_date AS ts,
                    COALESCE(t.invoice_no, t.ref_no, '') AS ref
                FROM transaction_sell_lines tsl
                INNER JOIN transactions t ON t.id = tsl.transaction_id
                WHERE t.business_id = ?
                  AND (tsl.variation_id, t.location_id) IN ({$vals})
                  AND t.type IN ('sell','sell_transfer','sell_return')
                  AND t.status IN ('final','received')

                UNION ALL
                SELECT
                    sal.variation_id, t.location_id,
                    'stock_adjustment' AS type,
                    t.transaction_date AS ts,
                    COALESCE(t.ref_no, '') AS ref
                FROM stock_adjustment_lines sal
                INNER JOIN transactions t ON t.id = sal.transaction_id
                WHERE t.business_id = ?
                  AND (sal.variation_id, t.location_id) IN ({$vals})
                  AND t.type = 'stock_adjustment'

                UNION ALL
                SELECT
                    wc.replacement_variation_id AS variation_id,
                    wc.location_id,
                    'warranty' AS type,
                    wc.claim_date AS ts,
                    wc.ref_no AS ref
                FROM warranty_claims wc
                WHERE wc.business_id = ?
                  AND wc.replacement_variation_id IS NOT NULL
                  AND (wc.replacement_variation_id, wc.location_id) IN ({$vals})
                  AND wc.status = 'completed'
            ) x
            ORDER BY variation_id, location_id, ts DESC
        ";

        $rows = DB::select($sql, [$business_id, $business_id, $business_id, $business_id]);

        // Además de las transactions, buscar en store_repairs (por IMEI == variation.sub_sku).
        // Es un evento, no afecta stock, pero el user lo quiere ver aquí también.
        $sr_rows = DB::select("
            SELECT sr.imei, sr.location_id, sr.created_at AS ts,
                   sr.status, CONCAT(t.name) AS technician
            FROM store_repairs sr
            LEFT JOIN technicians t ON t.id = sr.technician_id
            WHERE sr.business_id = ?
        ", [$business_id]);
        // Mapa IMEI → variation_id de este set (para cruzar)
        $imei_to_var = [];
        foreach ($items as $it) {
            $imei_to_var[$it->sub_sku] = ['vid' => (int) $it->variation_id, 'lid' => (int) $it->location_id];
        }

        // Reducir a un mapa "variation-location → último movimiento"
        $best = [];
        foreach ($rows as $r) {
            $k = $r->variation_id . '_' . $r->location_id;
            if (!isset($best[$k]) || $r->ts > $best[$k]->ts) $best[$k] = $r;
        }
        foreach ($sr_rows as $sr) {
            if (empty($imei_to_var[$sr->imei])) continue;
            $vid = $imei_to_var[$sr->imei]['vid'];
            if ($imei_to_var[$sr->imei]['lid'] != $sr->location_id) continue;
            $k = $vid . '_' . $sr->location_id;
            if (!isset($best[$k]) || $sr->ts > $best[$k]->ts) {
                $best[$k] = (object) [
                    'variation_id' => $vid,
                    'location_id' => $sr->location_id,
                    'type' => 'store_repair',
                    'ts' => $sr->ts,
                    'ref' => 'Técnico: ' . ($sr->technician ?: '—') . ' · ' . $sr->status,
                ];
            }
        }

        // Diccionario de labels e íconos por tipo
        $labels = [
            'purchase'         => ['Compra recibida',            'fa-shopping-cart', '#1976d2'],
            'purchase_transfer' => ['Transferencia recibida',    'fa-arrow-down',    '#00897b'],
            'opening_stock'    => ['Stock inicial',              'fa-cube',          '#7b1fa2'],
            'sell'             => ['Venta',                       'fa-shopping-bag',  '#c62828'],
            'sell_transfer'    => ['Transferencia enviada',      'fa-arrow-up',      '#ef6c00'],
            'sell_return'      => ['Devolución recibida',        'fa-undo',          '#5d4037'],
            'stock_adjustment' => ['Ajuste manual',              'fa-wrench',        '#f57f17'],
            'warranty'         => ['Garantía (reemplazo)',       'fa-shield-alt',    '#4527a0'],
            'store_repair'     => ['Reparación de tienda',       'fa-tools',         '#00695c'],
        ];

        // Adjuntar last_movement a cada item
        foreach ($items as $it) {
            $k = $it->variation_id . '_' . $it->location_id;
            if (isset($best[$k])) {
                $b = $best[$k];
                $meta = $labels[$b->type] ?? [$b->type, 'fa-clock', '#666'];
                $it->last_movement = [
                    'type'       => $b->type,
                    'type_label' => $meta[0],
                    'icon'       => $meta[1],
                    'color'      => $meta[2],
                    'date'       => $b->ts,
                    'ref'        => $b->ref ?: null,
                ];
            } else {
                $it->last_movement = null;
            }
        }
    }

    private function getLocationStats($location_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $total_products = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->where('vld.location_id', $location_id)
            ->count();

        $total_value = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->where('vld.location_id', $location_id)
            ->sum(DB::raw('vld.qty_available * v.default_purchase_price'));

        $low_stock_count = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->where('vld.location_id', $location_id)
            ->whereRaw('vld.qty_available <= p.alert_quantity')
            ->count();

        $out_of_stock_count = DB::table('variation_location_details as vld')
            ->join('variations as v', 'vld.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->where('p.business_id', $business_id)
            ->where('vld.location_id', $location_id)
            ->where('vld.qty_available', '<=', 0)
            ->count();

        return [
            'total_products' => $total_products,
            'total_value' => $total_value,
            'low_stock_count' => $low_stock_count,
            'out_of_stock_count' => $out_of_stock_count
        ];
    }
}
