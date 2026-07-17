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
        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function brandId($business_id, $name)
    {
        return DB::table('brands')->where('business_id', $business_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');
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

        $brand_equipos = $this->brandId($business_id, 'Equipos');
        $brand_accesorios = $this->brandId($business_id, 'Accesorios');
        $brand_reparaciones = $this->brandId($business_id, 'Reparaciones');
        $brand_servicios = $this->brandId($business_id, 'Servicios');
        $brand_desbloqueos = $this->brandId($business_id, 'Desbloqueos');
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
            ->where(function ($q) use ($start_dt, $end_dt) {
                $q->where(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNull('t.layaway_id')
                        ->whereBetween('t.transaction_date', [$start_dt, $end_dt]);
                })->orWhere(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNotNull('t.layaway_id')
                        ->whereNotNull('sd_l.completed_at')
                        ->whereBetween('sd_l.completed_at', [$start_dt, $end_dt]);
                });
            })
            ->where('p.brand_id', $brand_equipos);
        if (! empty($location_id)) {
            $eq->where('t.location_id', $location_id);
        }
        $equipos_lines = $eq->select([
            't.id as tx_id',
            // Para apartados usamos completed_at como "fecha de la venta" para que caiga en
            // la columna del día correcto en el dashboard semanal.
            DB::raw('IF(t.layaway_id IS NOT NULL, sd_l.completed_at, t.transaction_date) as transaction_date'),
            // Vendedor efectivo: liquidador para apartados, creador para ventas normales
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
            ->where(function ($q) use ($start_dt, $end_dt) {
                $q->where(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNull('t.layaway_id')
                        ->whereBetween('t.transaction_date', [$start_dt, $end_dt]);
                })->orWhere(function ($q2) use ($start_dt, $end_dt) {
                    $q2->whereNotNull('t.layaway_id')
                        ->whereNotNull('sd_l2.completed_at')
                        ->whereBetween('sd_l2.completed_at', [$start_dt, $end_dt]);
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
        $bucket_order = ['EQUIPOS', 'ACCESORIOS', 'VT', 'HIDROGEL', 'REPARACIONES', 'SERVICIOS', 'DESBLOQUEOS', 'OTROS'];
        $buckets = [];
        foreach ($bucket_order as $b) {
            $buckets[$b] = ['vendors' => [], 'qty' => 0, 'amount' => 0];
        }
        foreach ($rows as $r) {
            $bucket = 'OTROS';
            if ($r->brand_id == $brand_equipos) {
                $bucket = 'EQUIPOS';
            } elseif ($r->brand_id == $brand_reparaciones) {
                $bucket = 'REPARACIONES';
            } elseif ($r->brand_id == $brand_servicios) {
                $bucket = 'SERVICIOS';
            } elseif ($r->brand_id == $brand_desbloqueos) {
                $bucket = 'DESBLOQUEOS';
            } elseif (isset($hg[$r->category_id])) {
                $bucket = 'HIDROGEL';
            } elseif (isset($vt[$r->category_id])) {
                $bucket = 'VT';
            } elseif ($r->brand_id == $brand_accesorios) {
                $bucket = 'ACCESORIOS';
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

        $allCatVendors = [];
        foreach ($buckets as $bd) {
            foreach ($bd['vendors'] as $vn => $x) {
                $allCatVendors[$vn] = true;
            }
        }
        $allCatVendors = array_keys($allCatVendors);

        return compact(
            'start_date', 'start', 'end', 'location_id', 'days',
            'eq_by_day', 'eq_matrix', 'eq_vendors', 'eq_total_qty', 'eq_total_amount',
            'meta_qty', 'faltan', 'detail', 'buckets', 'goal_loc', 'allCatVendors'
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
