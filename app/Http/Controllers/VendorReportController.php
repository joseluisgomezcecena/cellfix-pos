<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Brands;
use App\User;
use App\VendorCommissionTarget;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VendorReportController extends Controller
{
    /**
     * Weekly per-vendor report: rows = days (Mon→Sun), columns = brand units sold,
     * plus N.DIA (transactions count) and TOTAL (sum of units).
     */
    public function weekly(Request $request)
    {
        if (!auth()->user()->can('business_settings.access') && !auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Default to current Monday
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
        // Normaliza: vacío o 0 = todas
        $location_id = (!empty($location_id) && (int) $location_id > 0) ? (int) $location_id : null;

        $data = $this->buildReportData($business_id, $start, $end, $location_id);

        $locations = BusinessLocation::forDropdown($business_id);

        return view('vendor_report.weekly', compact(
            'data', 'start_date', 'start', 'end', 'locations', 'location_id'
        ));
    }

    /**
     * Builds the per-vendor matrix for the given date range.
     * Returns an array with:
     *   - brands: list of Brand objects (columns)
     *   - vendors: list of per-vendor stats
     *   - days: ordered list of Carbon dates (Mon..Sun)
     *   - day_labels: short labels for each day
     *   - totals: combined matrix across all vendors
     */
    public function buildReportData($business_id, $start, $end, $location_id = null)
    {
        // 7 days from $start
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $start->copy()->addDays($i);
        }

        $day_labels = [];
        $day_label_map = [0 => 'DOM', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];
        foreach ($days as $d) {
            $day_labels[] = $day_label_map[$d->dayOfWeek] ?? '';
        }

        $brands = Brands::where('business_id', $business_id)
            ->orderBy('name')
            ->get();

        // Identify vendor users (only those with VENDEDOR roles)
        $vendor_role_names = [
            'VENDEDORES NIVEL 1#' . $business_id,
            'VENDEDOR PLUS#' . $business_id,
        ];
        $vendor_users = User::where('business_id', $business_id)
            ->whereHas('roles', function ($q) use ($vendor_role_names) {
                $q->whereIn('name', $vendor_role_names);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Single query: get all sell lines in range, with vendor + brand
        $query = \DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->select(
                't.id as transaction_id',
                't.created_by',
                't.transaction_date',
                'p.brand_id',
                'tsl.quantity'
            );

        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $lines = $query->get();

        // Aggregate: vendor_id → day_key → brand_id → quantity, plus transaction set per day
        $matrix = [];
        $tx_set = []; // vendor_id => day_key => set of transaction ids (for N.DIA count)

        foreach ($lines as $line) {
            $vid = $line->created_by;
            $day_key = Carbon::parse($line->transaction_date)->toDateString();
            $bid = $line->brand_id ?? 0;

            if (!isset($matrix[$vid])) $matrix[$vid] = [];
            if (!isset($matrix[$vid][$day_key])) $matrix[$vid][$day_key] = [];
            if (!isset($matrix[$vid][$day_key][$bid])) $matrix[$vid][$day_key][$bid] = 0;

            $matrix[$vid][$day_key][$bid] += (float) $line->quantity;

            if (!isset($tx_set[$vid])) $tx_set[$vid] = [];
            if (!isset($tx_set[$vid][$day_key])) $tx_set[$vid][$day_key] = [];
            $tx_set[$vid][$day_key][$line->transaction_id] = true;
        }

        // Build per-vendor rows. Include only vendors with at least one line in range.
        // Plus include all "VENDEDORES" users even if they have 0 sales (so they appear in the report with zeros)
        $vendor_users_keyed = $vendor_users->keyBy('id');

        // Add any vendor that has sales but isn't in the role list (e.g. admin took a sale)
        foreach (array_keys($matrix) as $vid) {
            if (!$vendor_users_keyed->has($vid)) {
                $u = User::find($vid);
                if ($u) {
                    $vendor_users_keyed->put($vid, $u);
                }
            }
        }

        $vendors = [];
        // Totals across vendors
        $combined = [];
        foreach ($days as $d) {
            $combined[$d->toDateString()] = [];
            foreach ($brands as $b) {
                $combined[$d->toDateString()][$b->id] = 0;
            }
            $combined[$d->toDateString()]['n_dia'] = 0;
            $combined[$d->toDateString()]['total'] = 0;
        }

        // Cargar metas/comisiones configuradas para todos los vendedores del business.
        // Estructura: $targets_by_user[user_id][brand_id] = ['meta' => x, 'rate' => y]
        $targets_by_user = [];
        $target_rows = VendorCommissionTarget::where('business_id', $business_id)
            ->whereIn('user_id', $vendor_users_keyed->keys()->all())
            ->get(['user_id', 'brand_id', 'meta_units', 'commission_per_unit']);
        foreach ($target_rows as $tr) {
            $targets_by_user[$tr->user_id][$tr->brand_id] = [
                'meta' => (int) $tr->meta_units,
                'rate' => (float) $tr->commission_per_unit,
            ];
        }

        foreach ($vendor_users_keyed as $user) {
            $vid = $user->id;
            $rows = [];
            $vendor_totals = [];
            $vendor_commissions = []; // por marca
            foreach ($brands as $b) {
                $vendor_totals[$b->id] = 0;
                $vendor_commissions[$b->id] = ['units' => 0, 'meta' => 0, 'rate' => 0, 'over_meta' => 0, 'commission' => 0, 'met' => false];
            }
            $vendor_totals['n_dia'] = 0;
            $vendor_totals['total'] = 0;
            $vendor_totals['commission_total'] = 0;

            foreach ($days as $d) {
                $day_key = $d->toDateString();
                $row = ['brands' => [], 'n_dia' => 0, 'total' => 0];

                foreach ($brands as $b) {
                    $qty = isset($matrix[$vid][$day_key][$b->id]) ? (float) $matrix[$vid][$day_key][$b->id] : 0;
                    $row['brands'][$b->id] = $qty;
                    $row['total'] += $qty;
                    $vendor_totals[$b->id] += $qty;
                    $combined[$day_key][$b->id] += $qty;
                    $combined[$day_key]['total'] += $qty;
                }

                $row['n_dia'] = isset($tx_set[$vid][$day_key]) ? count($tx_set[$vid][$day_key]) : 0;
                $vendor_totals['n_dia'] += $row['n_dia'];
                $vendor_totals['total'] += $row['total'];
                $combined[$day_key]['n_dia'] += $row['n_dia'];

                $rows[] = $row;
            }

            // Calcular comisión por marca: solo unidades por encima de la meta.
            foreach ($brands as $b) {
                $units = (float) $vendor_totals[$b->id];
                $target = $targets_by_user[$vid][$b->id] ?? null;
                $meta = $target ? (int) $target['meta'] : 0;
                $rate = $target ? (float) $target['rate'] : 0;
                $over = max(0, $units - $meta);
                $commission = round($over * $rate, 2);
                $vendor_commissions[$b->id] = [
                    'units' => $units,
                    'meta' => $meta,
                    'rate' => $rate,
                    'over_meta' => $over,
                    'commission' => $commission,
                    'met' => $rate > 0 && $units >= $meta && $meta > 0,
                ];
                $vendor_totals['commission_total'] += $commission;
            }

            $vendors[] = [
                'user' => $user,
                'rows' => $rows,
                'totals' => $vendor_totals,
                'commissions' => $vendor_commissions,
            ];
        }

        // Build combined totals row
        $combined_totals = ['brands' => array_fill_keys($brands->pluck('id')->toArray(), 0), 'n_dia' => 0, 'total' => 0];
        foreach ($days as $d) {
            $day_key = $d->toDateString();
            foreach ($brands as $b) {
                $combined_totals['brands'][$b->id] = ($combined_totals['brands'][$b->id] ?? 0) + $combined[$day_key][$b->id];
            }
            $combined_totals['n_dia'] += $combined[$day_key]['n_dia'];
            $combined_totals['total'] += $combined[$day_key]['total'];
        }

        return [
            'brands' => $brands,
            'days' => $days,
            'day_labels' => $day_labels,
            'vendors' => $vendors,
            'combined' => $combined,
            'combined_totals' => $combined_totals,
        ];
    }

    /**
     * Export to Excel.
     */
    public function exportWeekly(Request $request)
    {
        if (!auth()->user()->can('business_settings.access') && !auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }

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

        $data = $this->buildReportData($business_id, $start, $end, $location_id);

        $filename = 'reporte_vendedores_' . $start->toDateString() . '_a_' . $end->toDateString() . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\WeeklyVendorReportExport($data, $start, $end),
            $filename
        );
    }
}
