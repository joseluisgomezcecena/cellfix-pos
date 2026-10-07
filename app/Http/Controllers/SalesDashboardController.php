<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Exports\SalesDashboardExport;
use App\SalesGoal;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SalesDashboardController extends Controller
{
    private function authorizeAccess()
    {
        if (! auth()->user()->can('business_settings.access')
            && ! auth()->user()->can('view_purchase_n_sell_report')
            && ! auth()->user()->can('celfix.sales_dashboard.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function brandId($business_id, $name)
    {
        return DB::table('brands')->where('business_id', $business_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');
    }

    private function brandIds($business_id, array $names)
    {
        $lowered = array_map('strtolower', $names);
        return DB::table('brands')->where('business_id', $business_id)
            ->whereIn(DB::raw('LOWER(name)'), $lowered)->pluck('id')->toArray();
    }

    private function categoryTree($business_id, $rootName)
    {
        $root = DB::table('categories')->where('business_id', $business_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($rootName)])->value('id');
        if (! $root) {
            return [];
        }
        $ids = [$root];
        $queue = [$root];
        while (! empty($queue)) {
            $parent = array_shift($queue);
            $kids = DB::table('categories')->where('parent_id', $parent)->pluck('id')->toArray();
            foreach ($kids as $k) {
                $ids[] = $k;
                $queue[] = $k;
            }
        }
        return array_unique($ids);
    }

    /**
     * Construye todos los datos del tablero (usado por la vista y el export).
     */
    private function buildData(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $start_date = $request->get('start_date');
        if (empty($start_date)) {
            $today = Carbon::now();
            // Semana de SÁBADO a VIERNES (no Mon→Sun). Sat dayOfWeek=6.
            $daysSinceStart = ($today->dayOfWeek + 1) % 7;
            $start_date = $today->copy()->subDays($daysSinceStart)->toDateString();
        }
        $start = Carbon::parse($start_date)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();
        $location_id = $request->get('location_id');

        // Todos los brand lookups usan arrays porque en la BD real hay duplicados por
        // typos (ej. "Hidrogel" id=109 y "HIdrogel" id=105 con I mayúscula). Si tomáramos
        // solo el primero perderíamos las ventas de las demás variantes.
        $brands_equipos = $this->brandIds($business_id, ['Equipos', 'Equipo']);
        $brands_accesorios = $this->brandIds($business_id, ['Accesorios']);
        $brands_reparaciones = $this->brandIds($business_id, ['Reparaciones', 'Reparacion']);
        $brands_servicios = $this->brandIds($business_id, ['Servicios', 'Servicio']);
        $brands_desbloqueos = $this->brandIds($business_id, ['Desbloqueos', 'Desbloqueo']);
        $brands_cortos = $this->brandIds($business_id, ['Corto', 'Cortos']);
        // Los productos HIDROGEL vienen con brand='Hidrogel' pero categoría=VT (root),
        // no en la subcategoría específica. Detectar por brand además de categoría cubre
        // ambos casos. Lo mismo aplica para VT — brand="Vidrio Templado".
        $brands_hidrogel = $this->brandIds($business_id, ['Hidrogel']);
        $brands_vidrio = $this->brandIds($business_id, ['Vidrio Templado', 'VT']);

        // Brands nuevas (creadas ≥ cutoff): aparecen como su propia línea con su
        // nombre real. Ajusta la fecha si necesitas que brands viejas también salgan solas.
        $new_brand_cutoff = '2026-08-13';
        $new_brand_labels = DB::table('brands')->where('business_id', $business_id)
            ->where('created_at', '>=', $new_brand_cutoff)
            ->pluck('name', 'id')
            ->map(fn ($n) => mb_strtoupper($n))
            ->toArray();
        $vt_cats = $this->categoryTree($business_id, 'VT');
        $hidrogel_cats = $this->categoryTree($business_id, 'Hidrogel');
        $vt_only = array_values(array_diff($vt_cats, $hidrogel_cats));

        // Nombre del CAJERO (created_by) para TODOS los usuarios del negocio.
        $userNames = User::where('business_id', $business_id)->get()
            ->mapWithKeys(function ($u) {
                $n = strtoupper(trim($u->first_name.' '.$u->last_name));
                return [$u->id => ($n !== '' ? $n : ('USUARIO '.$u->id))];
            })->toArray();
        $vendorLabel = function ($created_by) use ($userNames) {
            return $userNames[$created_by] ?? 'TIENDA';
        };

        $day_names = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i);
            $days[] = ['key' => $d->toDateString(), 'label' => $day_names[$d->dayOfWeek], 'short' => $d->format('d/m')];
        }

        // ===== EQUIPOS =====
        // REGLA DE APARTADOS (misma que DailyCutUtil): ventas normales por transaction_date,
        // apartados consolidados en su fecha de completed_at, apartados activos EXCLUIDOS.
        // COMISIÓN: si la transacción es de un apartado, el vendedor es quien lo LIQUIDÓ
        // (último layaway_payment.processed_by), no quien lo apartó (t.created_by).
        $start_dt = $start->toDateTimeString();
        $end_dt = $end->toDateTimeString();
        $eq = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->join('variations as v', 'v.id', '=', 'tsl.variation_id')
            ->leftJoin('layaways as sd_l', 'sd_l.id', '=', 't.layaway_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')->where('t.status', 'final')
            ->where('t.is_warranty_exchange', 0)
            ->where(function ($q) use ($start_dt, $end_dt) {
                // Regla A: venta normal (sin layaway, sin repair) → transaction_date
                $q->where(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNull('t.layaway_id')
                        ->whereNull('t.repair_status')
                        ->whereBetween('t.transaction_date', [$start_dt, $end_dt]);
                // Regla B: apartado completado → completed_at
                })->orWhere(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNotNull('t.layaway_id')
                        ->whereNotNull('sd_l.completed_at')
                        ->whereBetween('sd_l.completed_at', [$start_dt, $end_dt]);
                // Regla C: reparación entregada → COALESCE(repair_delivered_at, transaction_date)
                })->orWhere(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNotNull('t.repair_status')
                        ->where('t.repair_status', '!=', 'pending')
                        ->whereBetween(
                            DB::raw('COALESCE(t.repair_delivered_at, t.transaction_date)'),
                            [$start_dt, $end_dt]
                        );
                });
            })
            ->whereIn('p.brand_id', $brands_equipos);
        if (! empty($location_id)) {
            $eq->where('t.location_id', $location_id);
        }
        $equipos_lines = $eq->select([
            't.id as tx_id',
            // Fecha efectiva para agrupar por día en el dashboard:
            //   - apartado → completed_at (día en que se liquidó)
            //   - reparación → repair_delivered_at (día de entrega), fallback transaction_date
            //   - venta normal → transaction_date
            DB::raw('CASE
                WHEN t.layaway_id IS NOT NULL THEN sd_l.completed_at
                WHEN t.repair_status IS NOT NULL AND t.repair_status != "pending" THEN COALESCE(t.repair_delivered_at, t.transaction_date)
                ELSE t.transaction_date
            END as transaction_date'),
            // Vendedor efectivo: liquidador para apartados, creador (receptor) para reparaciones y ventas normales
            DB::raw("IF(t.layaway_id IS NOT NULL, (SELECT lp.processed_by FROM layaway_payments lp WHERE lp.layaway_id = t.layaway_id ORDER BY lp.id DESC LIMIT 1), t.created_by) as created_by"),
            't.invoice_no',
            'tsl.quantity', DB::raw('(tsl.quantity * tsl.unit_price_inc_tax) as amount'),
            'p.name as product_name', 'p.sku',
            DB::raw('COALESCE(v.default_purchase_price,0) as cost'),
            DB::raw('(SELECT tp.method FROM transaction_payments tp WHERE tp.transaction_id = t.id ORDER BY tp.amount DESC LIMIT 1) as pay_method'),
        ])->orderBy('transaction_date')->get();

        $eq_by_day = [];
        foreach ($days as $d) {
            $eq_by_day[$d['key']] = ['qty' => 0, 'amount' => 0];
        }
        $eq_matrix = [];
        $eq_vendor_totals = [];
        $detail = [];
        $eq_total_qty = 0;
        $eq_total_amount = 0;
        $order = 1;
        foreach ($equipos_lines as $l) {
            $dk = Carbon::parse($l->transaction_date)->toDateString();
            $vname = $vendorLabel($l->created_by);
            $qty = (float) $l->quantity;
            $amt = (float) $l->amount;
            if (isset($eq_by_day[$dk])) {
                $eq_by_day[$dk]['qty'] += $qty;
                $eq_by_day[$dk]['amount'] += $amt;
            }
            $eq_matrix[$vname][$dk] = ($eq_matrix[$vname][$dk] ?? 0) + $qty;
            $eq_vendor_totals[$vname] = ($eq_vendor_totals[$vname] ?? 0) + $qty;
            $eq_total_qty += $qty;
            $eq_total_amount += $amt;
            $detail[] = [
                'order' => $order++,
                'vendor' => $vname,
                'product' => $l->product_name,
                'sku' => $l->sku,
                'cost' => (float) $l->cost,
                'amount' => $amt,
                'date' => Carbon::parse($l->transaction_date)->format('d/m/Y'),
                'pay_method' => $l->pay_method,
            ];
        }
        arsort($eq_vendor_totals);
        $eq_vendors = array_keys($eq_vendor_totals);

        $goal_loc = ! empty($location_id) ? (int) $location_id : 0;
        $goal = SalesGoal::where('business_id', $business_id)->where('metric', 'equipos_weekly')
            ->where('location_id', $goal_loc)->first();
        $meta_qty = $goal ? $goal->target_qty : 0;
        $faltan = $meta_qty - $eq_total_qty;

        // ===== CATEGORÍAS =====
        // Misma regla de apartados y comisión al liquidador que EQUIPOS arriba.
        $cq = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoin('layaways as sd_l2', 'sd_l2.id', '=', 't.layaway_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')->where('t.status', 'final')
            ->where('t.is_warranty_exchange', 0)
            ->where(function ($q) use ($start_dt, $end_dt) {
                // A: venta normal
                $q->where(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNull('t.layaway_id')
                        ->whereNull('t.repair_status')
                        ->whereBetween('t.transaction_date', [$start_dt, $end_dt]);
                // B: apartado completado
                })->orWhere(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNotNull('t.layaway_id')
                        ->whereNotNull('sd_l2.completed_at')
                        ->whereBetween('sd_l2.completed_at', [$start_dt, $end_dt]);
                // C: reparación entregada
                })->orWhere(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNotNull('t.repair_status')
                        ->where('t.repair_status', '!=', 'pending')
                        ->whereBetween(
                            DB::raw('COALESCE(t.repair_delivered_at, t.transaction_date)'),
                            [$start_dt, $end_dt]
                        );
                });
            });
        if (! empty($location_id)) {
            $cq->where('t.location_id', $location_id);
        }
        $rows = $cq->select([
            DB::raw("IF(t.layaway_id IS NOT NULL, (SELECT lp.processed_by FROM layaway_payments lp WHERE lp.layaway_id = t.layaway_id ORDER BY lp.id DESC LIMIT 1), t.created_by) as created_by"),
            'p.brand_id', 'p.category_id', 'tsl.quantity',
            DB::raw('(tsl.quantity * tsl.unit_price_inc_tax) as amount'),
        ])->get();

        $vt = array_flip($vt_only);
        $hg = array_flip($hidrogel_cats);
        $eq_flip = array_flip($brands_equipos);
        $ac_flip = array_flip($brands_accesorios);
        $rep_flip = array_flip($brands_reparaciones);
        $srv_flip = array_flip($brands_servicios);
        $des_flip = array_flip($brands_desbloqueos);
        $cortos_flip = array_flip($brands_cortos);
        $hid_flip = array_flip($brands_hidrogel);
        $vidrio_flip = array_flip($brands_vidrio);
        $known_buckets = ['EQUIPOS', 'ACCESORIOS', 'VT', 'HIDROGEL', 'CORTOS', 'REPARACIONES', 'SERVICIOS', 'DESBLOQUEOS'];
        $buckets = [];
        foreach ($known_buckets as $b) {
            $buckets[$b] = ['vendors' => [], 'qty' => 0, 'amount' => 0];
        }
        $buckets['OTROS'] = ['vendors' => [], 'qty' => 0, 'amount' => 0];
        foreach ($rows as $r) {
            $bucket = null;
            if (isset($eq_flip[$r->brand_id])) {
                $bucket = 'EQUIPOS';
            } elseif (isset($rep_flip[$r->brand_id])) {
                $bucket = 'REPARACIONES';
            } elseif (isset($srv_flip[$r->brand_id])) {
                $bucket = 'SERVICIOS';
            } elseif (isset($des_flip[$r->brand_id])) {
                $bucket = 'DESBLOQUEOS';
            } elseif (isset($cortos_flip[$r->brand_id])) {
                $bucket = 'CORTOS';
            } elseif (isset($hid_flip[$r->brand_id])) {
                $bucket = 'HIDROGEL';
            } elseif (isset($hg[$r->category_id])) {
                $bucket = 'HIDROGEL';
            } elseif (isset($vidrio_flip[$r->brand_id])) {
                $bucket = 'VT';
            } elseif (isset($vt[$r->category_id])) {
                $bucket = 'VT';
            } elseif (isset($ac_flip[$r->brand_id])) {
                $bucket = 'ACCESORIOS';
            } elseif (isset($new_brand_labels[$r->brand_id])) {
                // Brand nueva (creada ≥ cutoff) sin bucket conceptual: sale con su propio nombre.
                $bucket = $new_brand_labels[$r->brand_id];
                if (!isset($buckets[$bucket])) {
                    $buckets[$bucket] = ['vendors' => [], 'qty' => 0, 'amount' => 0];
                }
            } else {
                $bucket = 'OTROS';
            }
            $vname = $vendorLabel($r->created_by);
            $qty = (float) $r->quantity;
            $amt = (float) $r->amount;
            if (! isset($buckets[$bucket]['vendors'][$vname])) {
                $buckets[$bucket]['vendors'][$vname] = ['qty' => 0, 'amount' => 0];
            }
            $buckets[$bucket]['vendors'][$vname]['qty'] += $qty;
            $buckets[$bucket]['vendors'][$vname]['amount'] += $amt;
            $buckets[$bucket]['qty'] += $qty;
            $buckets[$bucket]['amount'] += $amt;
        }

        // Reordenar buckets: conocidos primero → brands nuevas alfabéticas → OTROS al final.
        $ordered = [];
        foreach ($known_buckets as $n) $ordered[$n] = $buckets[$n];
        $extra = array_diff(array_keys($buckets), $known_buckets, ['OTROS']);
        sort($extra);
        foreach ($extra as $n) $ordered[$n] = $buckets[$n];
        $ordered['OTROS'] = $buckets['OTROS'];
        $buckets = $ordered;

        $allCatVendors = [];
        foreach ($buckets as $bd) {
            foreach ($bd['vendors'] as $vn => $x) {
                $allCatVendors[$vn] = true;
            }
        }
        $allCatVendors = array_keys($allCatVendors);

        // ===== GARANTÍAS =====
        // Equipos dados en garantía dentro del rango (claim_date). Para cada claim
        // traemos: equipo devuelto por el cliente + equipo entregado (si fue
        // replacement), IMEIs (variations.sub_sku) de ambos, precio pagado por el
        // equipo original en su venta de origen, y precio efectivo del reemplazo
        // (derivado de original_price + price_difference para higher/lower, igual
        // al original para replacement_same).
        $wq = DB::table('warranty_claims as wc')
            ->leftJoin('contacts as c', 'c.id', '=', 'wc.contact_id')
            ->leftJoin('transactions as os', 'os.id', '=', 'wc.original_sell_transaction_id')
            ->leftJoin('variations as vo', 'vo.id', '=', 'wc.original_variation_id')
            ->leftJoin('variations as vr', 'vr.id', '=', 'wc.replacement_variation_id')
            ->leftJoin('transaction_sell_lines as tsl', function ($join) {
                $join->on('tsl.transaction_id', '=', 'wc.original_sell_transaction_id')
                     ->on('tsl.variation_id', '=', 'wc.original_variation_id');
            })
            ->where('wc.business_id', $business_id)
            ->where('wc.status', 'completed')
            ->whereBetween('wc.claim_date', [$start_dt, $end_dt]);
        if (! empty($location_id)) {
            $wq->where('wc.location_id', $location_id);
        }
        $warranty_rows = $wq->select([
            'wc.id', 'wc.ref_no', 'wc.claim_date', 'wc.claim_type', 'wc.created_by',
            'wc.original_product_name', 'wc.replacement_product_name',
            'wc.refund_amount', 'wc.refund_method',
            'wc.price_difference', 'wc.price_difference_method',
            'c.name as contact_name',
            'os.invoice_no as original_invoice',
            'vo.sub_sku as original_imei',
            'vr.sub_sku as replacement_imei',
            DB::raw('COALESCE(tsl.unit_price_inc_tax, vo.default_sell_price) as original_price'),
        ])->orderByDesc('wc.claim_date')->get();

        $warranty_detail = [];
        $warranty_totals = ['refund' => 0, 'diff_in' => 0, 'diff_out' => 0, 'replaced_qty' => 0, 'refund_qty' => 0];
        foreach ($warranty_rows as $r) {
            $type = $r->claim_type;
            $original_price = (float) ($r->original_price ?? 0);
            $diff = $r->price_difference !== null ? (float) $r->price_difference : null;
            $replacement_price = null;
            if ($type === 'replacement_same') {
                $replacement_price = $original_price;
            } elseif (in_array($type, ['replacement_higher', 'replacement_lower'], true)) {
                $replacement_price = $original_price + ($diff ?? 0);
            }
            if ($type === 'refund') {
                $warranty_totals['refund'] += (float) ($r->refund_amount ?? 0);
                $warranty_totals['refund_qty']++;
            } else {
                $warranty_totals['replaced_qty']++;
                if ($diff !== null) {
                    if ($diff >= 0) $warranty_totals['diff_in'] += $diff;
                    else $warranty_totals['diff_out'] += abs($diff);
                }
            }
            $warranty_detail[] = [
                'ref' => $r->ref_no,
                'date' => Carbon::parse($r->claim_date)->format('d/m/Y H:i'),
                'contact' => $r->contact_name,
                'vendor' => $vendorLabel($r->created_by),
                'type' => $type,
                'type_label' => \App\WarrantyClaim::claimTypeLabel($type),
                'original_name' => $r->original_product_name,
                'original_imei' => $r->original_imei,
                'original_price' => $original_price,
                'original_invoice' => $r->original_invoice,
                'replacement_name' => $r->replacement_product_name,
                'replacement_imei' => $r->replacement_imei,
                'replacement_price' => $replacement_price,
                'refund_amount' => $r->refund_amount !== null ? (float) $r->refund_amount : null,
                'refund_method' => $r->refund_method,
                'price_difference' => $diff,
                'price_difference_method' => $r->price_difference_method,
            ];
        }

        return compact(
            'start_date', 'start', 'end', 'location_id', 'days',
            'eq_by_day', 'eq_matrix', 'eq_vendors', 'eq_total_qty', 'eq_total_amount',
            'meta_qty', 'faltan', 'detail', 'buckets', 'goal_loc', 'allCatVendors',
            'warranty_detail', 'warranty_totals'
        );
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $data = $this->buildData($request);
        $data['locations'] = BusinessLocation::forDropdown($request->session()->get('user.business_id'));

        return view('sales_dashboard.index', $data);
    }

    public function exportExcel(Request $request)
    {
        $this->authorizeAccess();
        $data = $this->buildData($request);
        $filename = 'tablero_ventas_'.$data['start']->format('Y-m-d').'.xlsx';

        return Excel::download(new SalesDashboardExport($data), $filename);
    }

    public function saveGoal(Request $request)
    {
        $this->authorizeAccess();
        $request->validate([
            'target_qty' => 'required|integer|min:0',
            'goal_location_id' => 'nullable|integer',
        ]);
        $business_id = $request->session()->get('user.business_id');
        $loc = (int) $request->input('goal_location_id', 0);
        SalesGoal::updateOrCreate(
            ['business_id' => $business_id, 'location_id' => $loc, 'metric' => 'equipos_weekly'],
            ['target_qty' => (int) $request->input('target_qty')]
        );

        return redirect()->back()->with('status', ['success' => 1, 'msg' => __('lang_v1.goal_saved')]);
    }
}
