<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\DailyCut;
use App\Utils\DailyCutUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyCutController extends Controller
{
    protected $util;

    public function __construct(DailyCutUtil $util)
    {
        $this->util = $util;
    }

    public function index(Request $request)
    {
        if (!auth()->user()->can('business_settings.access') && !auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $location_id = $request->get('location_id');
        $start_date = $request->get('start_date', Carbon::now()->subDays(7)->toDateString());
        $end_date = $request->get('end_date', Carbon::now()->toDateString());

        $cuts = DailyCut::where('business_id', $business_id)
            ->whereBetween('cut_date', [$start_date, $end_date])
            ->with('location')
            ->orderBy('cut_date', 'desc')
            ->orderBy('location_id');

        if (!empty($location_id)) {
            $cuts->where('location_id', $location_id);
        }

        $cuts = $cuts->get();
        $locations = BusinessLocation::forDropdown($business_id);

        // Estado del corte automático de hoy (¿se generó ya después de las 18:00?)
        $today = Carbon::now()->toDateString();
        $cutoff_today = Carbon::now()->startOfDay()->setTime(18, 0);
        $auto_cut_today = DailyCut::where('business_id', $business_id)
            ->where('cut_date', $today)
            ->where('generated_at', '>=', $cutoff_today)
            ->orderByDesc('generated_at')
            ->first();

        return view('daily_cut.index', compact('cuts', 'locations', 'location_id', 'start_date', 'end_date', 'auto_cut_today'));
    }

    public function show($id)
    {
        if (!auth()->user()->can('business_settings.access') && !auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $cut = DailyCut::where('business_id', $business_id)
            ->with('location', 'generatedBy')
            ->findOrFail($id);

        return view('daily_cut.show', compact('cut'));
    }

    /**
     * Weekly view — 7 day cards with category and payment-method breakdown.
     */
    public function weekly(Request $request)
    {
        if (!auth()->user()->can('business_settings.access') && !auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Default to this week's Monday — Mon–Sun week
        $start_date = $request->get('start_date');
        if (empty($start_date)) {
            $today = Carbon::now();
            // Monday is day-of-week 1 in Carbon (with Sunday=0)
            // Semana de SÁBADO a VIERNES (no Mon→Sun). Sat dayOfWeek=6.
            $daysSinceStart = ($today->dayOfWeek + 1) % 7;
            $start_date = $today->copy()->subDays($daysSinceStart)->toDateString();
        }
        $start = Carbon::parse($start_date);
        $end = $start->copy()->addDays(6);

        $location_id = $request->get('location_id');

        // Make sure cuts are fresh in the requested range
        $this->ensureCutsForRange($business_id, $start->toDateString(), $end->toDateString(), $location_id);

        // Load all cuts in the range
        $query = DailyCut::where('business_id', $business_id)
            ->whereBetween('cut_date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $cuts = $query->get();

        // Group by date string up front because cut_date is cast as Carbon and
        // a direct ->where('cut_date', '2026-05-08') won't match a Carbon instance
        $cuts_by_date = $cuts->groupBy(function ($c) {
            return $c->cut_date->toDateString();
        });

        // Aggregate per day (sum across locations if no location filter)
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $day_cuts = $cuts_by_date->get($key, collect());

            $days[$key] = [
                'date' => $date,
                'day_name' => $this->dayName($date->dayOfWeek),
                'total_sales' => $day_cuts->sum('total_sales'),
                'total_cash' => $day_cuts->sum('total_cash'),
                'total_card' => $day_cuts->sum('total_card'),
                'total_transfer' => $day_cuts->sum('total_transfer'),
                'total_cheque' => $day_cuts->sum('total_cheque'),
                'total_expenses' => $day_cuts->sum('total_expenses'),
                'sales_by_brand' => $this->mergeBrandTotals($day_cuts),
                'card_by_terminal' => $this->mergeTerminalTotals($day_cuts),
            ];
        }

        $locations = BusinessLocation::forDropdown($business_id);

        return view('daily_cut.weekly', compact('days', 'locations', 'location_id', 'start_date'));
    }

    /**
     * Denominations report — wide table with each day's MXN/USD bills + terminal totals,
     * mirroring the Excel layout the team uses today.
     */
    public function denominations(Request $request)
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
        $start = Carbon::parse($start_date);
        $end = $start->copy()->addDays(6);

        $location_id = $request->get('location_id');

        // Make sure cuts are fresh in the requested range
        $this->ensureCutsForRange($business_id, $start->toDateString(), $end->toDateString(), $location_id);

        $query = DailyCut::where('business_id', $business_id)
            ->whereBetween('cut_date', [$start->toDateString(), $end->toDateString()]);

        if (!empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $cuts = $query->get();

        // Group by date string (cut_date is Carbon, can't use ->where on string)
        $cuts_by_date = $cuts->groupBy(function ($c) {
            return $c->cut_date->toDateString();
        });

        // Fixed denomination columns (highest to lowest for MXN, lowest to highest for USD as in the Excel)
        $mxn_faces = [1000, 500, 200, 100, 50, 20];
        $usd_faces = [1, 5, 10, 20, 50, 100];

        // Discover all unique terminals across the week
        $terminal_names = [];
        foreach ($cuts as $cut) {
            foreach (($cut->summary['card_by_terminal'] ?? []) as $t) {
                $name = $t['name'] ?? '—';
                if (!in_array($name, $terminal_names)) {
                    $terminal_names[] = $name;
                }
            }
        }
        sort($terminal_names);

        // Build day-by-day rows
        $rows = [];
        $totals = [
            'mxn_faces' => array_fill_keys($mxn_faces, 0),
            'mxn_coins' => 0,
            'mxn_subtotal' => 0,
            'usd_faces' => array_fill_keys($usd_faces, 0),
            'usd_coins' => 0,
            'usd_subtotal' => 0,
            'usd_in_mxn' => 0,
            'total_cash' => 0,
            'total_card' => 0,
            'terminals' => array_fill_keys($terminal_names, 0),
            'terminals_total' => 0,
            'total_dinero' => 0,
            'transfer' => 0,
            'cheque' => 0,
        ];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $day_cuts = $cuts_by_date->get($key, collect());

            // Aggregate denominations across location cuts of this day
            $row_mxn_faces = array_fill_keys($mxn_faces, 0);
            $row_mxn_coins = 0;
            $row_usd_faces = array_fill_keys($usd_faces, 0);
            $row_usd_coins = 0;
            $row_usd_in_mxn = 0;
            $row_terminals = array_fill_keys($terminal_names, 0);

            foreach ($day_cuts as $cut) {
                $mxn_data = $cut->summary['mxn'] ?? null;
                if ($mxn_data) {
                    foreach ($mxn_data['denominations'] ?? [] as $face => $count) {
                        if (isset($row_mxn_faces[$face])) {
                            $row_mxn_faces[$face] += (int) $count;
                        }
                    }
                    $row_mxn_coins += (float) ($mxn_data['coins'] ?? 0);
                }

                $usd_data = $cut->summary['usd'] ?? null;
                if ($usd_data) {
                    foreach ($usd_data['denominations'] ?? [] as $face => $count) {
                        if (isset($row_usd_faces[$face])) {
                            $row_usd_faces[$face] += (int) $count;
                        }
                    }
                    $row_usd_coins += (float) ($usd_data['coins'] ?? 0);
                    $row_usd_in_mxn += (float) ($usd_data['in_mxn'] ?? 0);
                }

                foreach ($cut->summary['card_by_terminal'] ?? [] as $t) {
                    $name = $t['name'] ?? '—';
                    if (isset($row_terminals[$name])) {
                        $row_terminals[$name] += (float) ($t['total'] ?? 0);
                    }
                }
            }

            $row_mxn_subtotal = $row_mxn_coins;
            foreach ($row_mxn_faces as $face => $count) {
                $row_mxn_subtotal += (float) $face * (int) $count;
            }
            $row_usd_subtotal = $row_usd_coins;
            foreach ($row_usd_faces as $face => $count) {
                $row_usd_subtotal += (float) $face * (int) $count;
            }
            $row_total_cash = $row_mxn_subtotal + $row_usd_in_mxn;
            $row_total_card = $day_cuts->sum('total_card');
            $row_terminals_total = array_sum($row_terminals);
            $row_transfer = $day_cuts->sum('total_transfer');
            $row_cheque = $day_cuts->sum('total_cheque');
            $row_total_dinero = $row_total_cash + $row_total_card + $row_transfer + $row_cheque;

            $rows[] = [
                'date' => $date,
                'day_name' => $this->dayName($date->dayOfWeek),
                'mxn_faces' => $row_mxn_faces,
                'mxn_coins' => $row_mxn_coins,
                'mxn_subtotal' => $row_mxn_subtotal,
                'usd_faces' => $row_usd_faces,
                'usd_coins' => $row_usd_coins,
                'usd_subtotal' => $row_usd_subtotal,
                'usd_in_mxn' => $row_usd_in_mxn,
                'total_cash' => $row_total_cash,
                'total_card' => $row_total_card,
                'terminals' => $row_terminals,
                'terminals_total' => $row_terminals_total,
                'transfer' => $row_transfer,
                'cheque' => $row_cheque,
                'total_dinero' => $row_total_dinero,
            ];

            // Accumulate totals
            foreach ($mxn_faces as $face) {
                $totals['mxn_faces'][$face] += $row_mxn_faces[$face];
            }
            $totals['mxn_coins'] += $row_mxn_coins;
            $totals['mxn_subtotal'] += $row_mxn_subtotal;
            foreach ($usd_faces as $face) {
                $totals['usd_faces'][$face] += $row_usd_faces[$face];
            }
            $totals['usd_coins'] += $row_usd_coins;
            $totals['usd_subtotal'] += $row_usd_subtotal;
            $totals['usd_in_mxn'] += $row_usd_in_mxn;
            $totals['total_cash'] += $row_total_cash;
            $totals['total_card'] += $row_total_card;
            foreach ($terminal_names as $name) {
                $totals['terminals'][$name] += $row_terminals[$name];
            }
            $totals['terminals_total'] += $row_terminals_total;
            $totals['transfer'] += $row_transfer;
            $totals['cheque'] += $row_cheque;
            $totals['total_dinero'] += $row_total_dinero;
        }

        $locations = BusinessLocation::forDropdown($business_id);

        return view('daily_cut.denominations', compact(
            'rows', 'totals', 'mxn_faces', 'usd_faces', 'terminal_names',
            'start_date', 'location_id', 'locations'
        ));
    }

    private function dayName($dow)
    {
        $names = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];

        return $names[$dow] ?? '';
    }

    /**
     * Ensures daily_cuts records exist and are up to date for the given range.
     * Always regenerates today (sales might be in flight). For past days, only
     * generates if no snapshot exists yet.
     */
    private function ensureCutsForRange($business_id, $start_date, $end_date, $location_id = null)
    {
        $loc_query = BusinessLocation::where('business_id', $business_id)->where('is_active', 1);
        if (!empty($location_id)) {
            $loc_query->where('id', $location_id);
        }
        $location_ids = $loc_query->pluck('id');

        $today_str = Carbon::now()->toDateString();
        $yesterday_str = Carbon::yesterday()->toDateString();
        $today_midnight = Carbon::today(); // hoy 00:00:00
        $current = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);
        $user_id = request()->session()->get('user.id');

        // Cap at today — there's no point generating future cuts
        if ($end->gt(Carbon::now())) {
            $end = Carbon::now();
        }

        while ($current->lte($end)) {
            $date_str = $current->toDateString();
            foreach ($location_ids as $loc_id) {
                $cut = DailyCut::where('business_id', $business_id)
                    ->where('location_id', $loc_id)
                    ->where('cut_date', $date_str)
                    ->first();

                $regenerate = false;
                if (!$cut) {
                    // No existe la foto: generarla.
                    $regenerate = true;
                } elseif ($date_str === $today_str) {
                    // Hoy: siempre regenerar para capturar nuevas ventas.
                    $regenerate = true;
                } elseif ($date_str === $yesterday_str && $cut->generated_at < $today_midnight) {
                    // Ayer: si la foto se tomó antes de cerrar el día (caso típico: 6 PM auto-cut),
                    // regenerar una vez después de medianoche para capturar ventas tardías
                    // (ej. cliente que llega 5:59 PM y la venta se registra 6:05 PM).
                    $regenerate = true;
                }

                if ($regenerate) {
                    $this->util->upsert($business_id, $loc_id, $date_str, $user_id);
                }
            }
            $current->addDay();
        }
    }

    private function mergeBrandTotals($day_cuts)
    {
        $merged = [];
        foreach ($day_cuts as $cut) {
            $rows = $cut->summary['sales_by_brand'] ?? [];
            foreach ($rows as $r) {
                $brand = $r['brand'] ?? 'Sin marca';
                if (!isset($merged[$brand])) {
                    $merged[$brand] = ['brand' => $brand, 'quantity' => 0, 'subtotal' => 0];
                }
                $merged[$brand]['quantity'] += (float) ($r['quantity'] ?? 0);
                $merged[$brand]['subtotal'] += (float) ($r['subtotal'] ?? 0);
            }
        }

        return array_values($merged);
    }

    private function mergeTerminalTotals($day_cuts)
    {
        $merged = [];
        foreach ($day_cuts as $cut) {
            $rows = $cut->summary['card_by_terminal'] ?? [];
            foreach ($rows as $r) {
                $name = $r['name'] ?? '—';
                if (!isset($merged[$name])) {
                    $merged[$name] = ['name' => $name, 'bank' => $r['bank'] ?? null, 'total' => 0];
                }
                $merged[$name]['total'] += (float) ($r['total'] ?? 0);
            }
        }

        return array_values($merged);
    }

    /**
     * Export weekly cut — one tab per sucursal in the team's "CORTE POR DIA" layout.
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
        $location_id = $request->get('location_id');

        $end = Carbon::parse($start_date)->addDays(6)->toDateString();

        // Auto-regenerate cuts for the range so the export is always fresh
        $this->ensureCutsForRange($business_id, $start_date, $end, $location_id);

        $filename = 'corte_semanal_' . $start_date . '_a_' . $end . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\WeeklyCutByLocationExport($business_id, $start_date, $location_id),
            $filename
        );
    }

    /**
     * Export the daily cuts (in the given range) as a multi-sheet Excel file.
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('business_settings.access') && !auth()->user()->can('view_purchase_n_sell_report')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $start_date = $request->get('start_date', Carbon::now()->subDays(7)->toDateString());
        $end_date = $request->get('end_date', Carbon::now()->toDateString());
        $location_id = $request->get('location_id');

        // Auto-regenerate cuts in the range
        $this->ensureCutsForRange($business_id, $start_date, $end_date, $location_id);

        $filename = 'cortes_' . $start_date . '_a_' . $end_date . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DailyCutsExport($business_id, $start_date, $end_date, $location_id),
            $filename
        );
    }

    /**
     * Public cron endpoint — triggers cut generation for all businesses for today.
     * Protected by a static token (env DAILY_CUT_CRON_TOKEN). Use with cron-job.org
     * or any external cron service hitting it at 18:00 daily.
     */
    public function cronAutoGenerate(Request $request)
    {
        $expected = env('DAILY_CUT_CRON_TOKEN');
        if (empty($expected) || $request->get('token') !== $expected) {
            return response()->json(['success' => false, 'msg' => 'Forbidden'], 403);
        }

        $date = $request->get('date', Carbon::now()->toDateString());
        $count = $this->util->generateForAllBusinesses($date);

        \Log::info("[daily-cut-cron] businesses={$count} date={$date}");

        return response()->json(['success' => true, 'businesses' => $count, 'date' => $date]);
    }

    /**
     * Manually triggers the cut generation for today (or for a specific date/location).
     */
    public function generate(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $date = $request->get('date', Carbon::now()->toDateString());
        $location_id = $request->get('location_id');

        try {
            if (!empty($location_id)) {
                $this->util->upsert($business_id, (int) $location_id, $date, $user_id);
            } else {
                $this->util->generateForBusiness($business_id, $date, $user_id);
            }

            $output = ['success' => true, 'msg' => 'Corte generado correctamente'];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->route('daily-cuts.index')->with('status', $output);
    }
}
