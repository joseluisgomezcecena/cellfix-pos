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
        $user_id = $request->session()->get('user.id');

        $location_id = $request->get('location_id');
        $start_date = $request->get('start_date', Carbon::now()->subDays(7)->toDateString());
        $end_date = $request->get('end_date', Carbon::now()->toDateString());

        // Asegura que HOY tenga una fila por sucursal — así por la mañana las cajas
        // aparecen "abiertas" en $0 y se van actualizando durante el día. No las cierra.
        $this->ensureTodayCutsExist($business_id, $user_id);

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

        // Lista de todas las ventas del día/sucursal del cut, con cliente y monto.
        // Usa la MISMA lógica que /sells (final_total directo) para que cuadre exacto.
        $cut_date = $cut->cut_date->toDateString();
        $sales = \DB::table('transactions as t')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoin('users as u', 'u.id', '=', 't.created_by')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $cut->location_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereRaw('DATE(t.transaction_date) = ?', [$cut_date])
            ->where(function ($q) {
                $q->where('t.sub_type', '!=', 'project_invoice')
                  ->orWhereNull('t.sub_type');
            })
            ->select(
                't.id',
                't.invoice_no',
                't.transaction_date',
                't.final_total',
                \DB::raw("COALESCE(c.name, 'Walk-In Customer') as customer_name"),
                \DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as vendedor")
            )
            ->orderBy('t.transaction_date')
            ->get();

        // Métodos de pago por venta para mostrar en la lista (no es por venta = un solo método;
        // si hay pago múltiple los unimos con coma).
        $sale_ids = $sales->pluck('id')->toArray();
        $payments_by_tx = [];
        if (!empty($sale_ids)) {
            $payments = \DB::table('transaction_payments')
                ->whereIn('transaction_id', $sale_ids)
                ->where('is_return', 0)
                ->select('transaction_id', 'method', 'amount')
                ->get();
            foreach ($payments as $p) {
                $payments_by_tx[$p->transaction_id][] = $p->method;
            }
        }

        return view('daily_cut.show', compact('cut', 'sales', 'payments_by_tx'));
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

        // Si NO hay sucursal seleccionada, no calculamos nada — la vista muestra un
        // aviso "selecciona una sucursal del dropdown". Esto evita mezclar totales de
        // varias sucursales por accidente. El valor especial "all" sí permite ver todas.
        if (empty($location_id)) {
            $locations = BusinessLocation::forDropdown($business_id);
            return view('daily_cut.weekly', [
                'days' => [],
                'locations' => $locations,
                'location_id' => null,
                'start_date' => $start_date,
            ]);
        }

        // "all" = todas las sucursales sumadas (el usuario lo eligió explícitamente).
        // Cualquier otro valor = una sucursal específica.
        $is_all = ($location_id === 'all');
        $specific_loc_id = $is_all ? null : (int) $location_id;

        // Make sure cuts are fresh in the requested range
        $this->ensureCutsForRange($business_id, $start->toDateString(), $end->toDateString(), $specific_loc_id);

        // Load all cuts in the range
        $query = DailyCut::where('business_id', $business_id)
            ->whereBetween('cut_date', [$start->toDateString(), $end->toDateString()]);
        if (!$is_all) {
            $query->where('location_id', $specific_loc_id);
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
            'cambio_cash' => 0,
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
            // Cambio entregado como vuelto en efectivo (pagos con is_return=1 y method=cash).
            // Este dinero salió físicamente del cajón durante ventas cuando el cliente
            // pagó de más y se le dio cambio en efectivo. Antes no aparecía en el
            // reporte de denominaciones y el TOTAL EFECTIVO no cuadraba con la vista
            // semanal, que sí lo restaba.
            $row_cambio_cash = $this->getCashChangeForDay(
                $business_id,
                $location_id,
                $date->toDateString()
            );
            // TOTAL EFECTIVO = billetes/monedas recibidos − cambio entregado.
            $row_total_cash = $row_mxn_subtotal + $row_usd_in_mxn - $row_cambio_cash;
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
                'cambio_cash' => $row_cambio_cash,
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
            $totals['cambio_cash'] += $row_cambio_cash;
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

        // Aviso de desglose incompleto: comparamos el TOTAL EFECTIVO del reporte
        // (mxn+usd_mxn − cambio) contra el total_cash real de los cortes. Si difiere,
        // hubo pagos cash sin denomination_breakdown registrado por la cajera.
        $weekly_total_cash = (float) $cuts->sum('total_cash');
        $undesglosado_cash = max(0, $weekly_total_cash - $totals['total_cash']);

        $locations = BusinessLocation::forDropdown($business_id);

        return view('daily_cut.denominations', compact(
            'rows', 'totals', 'mxn_faces', 'usd_faces', 'terminal_names',
            'start_date', 'location_id', 'locations',
            'weekly_total_cash', 'undesglosado_cash'
        ));
    }

    private function dayName($dow)
    {
        $names = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];

        return $names[$dow] ?? '';
    }

    /**
     * Suma de cambio en efectivo entregado como vuelto ese día
     * (transaction_payments con method=cash + is_return=1, sobre ventas 'sell').
     * Si $location_id viene vacío o 'all', suma todas las sucursales.
     */
    private function getCashChangeForDay($business_id, $location_id, $date)
    {
        $q = \DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', $date)
            ->where('tp.method', 'cash')
            ->where('tp.is_return', 1);
        if (!empty($location_id) && $location_id !== 'all') {
            $q->where('t.location_id', $location_id);
        }
        return (float) $q->sum('tp.amount');
    }

    /**
     * Ensures daily_cuts records exist and are up to date for the given range.
     * Always regenerates today (sales might be in flight). For past days, only
     * generates if no snapshot exists yet.
     */
    /**
     * Garantiza que existan filas de daily_cut para HOY en TODAS las sucursales activas.
     * Útil para que al cargar /daily-cuts en la mañana las "cajas" se vean abiertas
     * desde $0 en vez de aparecer sólo a las 18:00 cuando dispara el heartbeat.
     * No marca closed_at — sigue mutables durante el día.
     *
     * BONUS: también cierra cualquier corte de días pasados que haya quedado abierto
     * (ej: nadie usó el sistema entre las 18:00 y medianoche → heartbeat no disparó).
     * Lo regenera primero para capturar todas las ventas del día completo, luego cierra.
     */
    private function ensureTodayCutsExist($business_id, $user_id = null)
    {
        $today_str = Carbon::now()->toDateString();

        // PASO 1 — cerrar cortes de DÍAS PASADOS que hayan quedado abiertos
        // (caso típico: nadie usó el sistema después de las 18:00 ayer, heartbeat
        // nunca disparó. El día ya pasó, esos cortes tienen que cerrarse.)
        $past_open = DailyCut::where('business_id', $business_id)
            ->where('cut_date', '<', $today_str)
            ->whereNull('closed_at')
            ->get();
        foreach ($past_open as $stale_cut) {
            // Regenera con todas las ventas del día (por si entraron tarde)
            $regen = $this->util->upsert(
                $business_id,
                $stale_cut->location_id,
                $stale_cut->cut_date->toDateString(),
                $user_id
            );
            $regen->closed_at = Carbon::now();
            $regen->closed_by = null; // sistema cerró tardíamente
            $regen->save();
        }

        // PASO 2 — crear filas de hoy para sucursales que aún no las tengan
        $location_ids = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->pluck('id');

        $existing_today = DailyCut::where('business_id', $business_id)
            ->where('cut_date', $today_str)
            ->pluck('location_id')
            ->flip();

        foreach ($location_ids as $loc_id) {
            if (!$existing_today->has($loc_id)) {
                $this->util->upsert($business_id, $loc_id, $today_str, $user_id);
            }
        }
    }

    private function ensureCutsForRange($business_id, $start_date, $end_date, $location_id = null)
    {
        $loc_query = BusinessLocation::where('business_id', $business_id)->where('is_active', 1);
        if (!empty($location_id)) {
            $loc_query->where('id', $location_id);
        }
        $location_ids = $loc_query->pluck('id');

        $today_str = Carbon::now()->toDateString();
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

                // REGLA: si el corte ya está CERRADO (closed_at != NULL), NUNCA se regenera.
                // Solo se regenera si:
                //   - No existe (alguien abrió el reporte sin que hubiera corte aún), o
                //   - Es de HOY y aún NO está cerrado (sigue mutable durante el día hasta que
                //     el cajero presione "Cerrar caja" o el heartbeat lo cierre a las 18:00).
                $regenerate = false;
                if (!$cut) {
                    $regenerate = true;
                } elseif ($date_str === $today_str && is_null($cut->closed_at)) {
                    $regenerate = true;
                }
                // Cortes con closed_at != NULL: NUNCA regenerar (incluye días pasados).

                if ($regenerate) {
                    $created_cut = $this->util->upsert($business_id, $loc_id, $date_str, $user_id);
                    // Si es un día PASADO (no hoy), cerrar automáticamente. Un día que ya pasó
                    // no debe quedar mutable — esto es el cierre implícito del "fin del día".
                    if ($date_str !== $today_str && is_null($created_cut->closed_at)) {
                        $created_cut->closed_at = Carbon::now();
                        $created_cut->closed_by = null; // sistema
                        $created_cut->save();
                    }
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
     * Regenera cortes históricos con la lógica actual. Útil cuando cambia una
     * regla de negocio (ej: apartados activos ya no cuentan al cut) y se necesita
     * que los cortes viejos se recalculen para reflejar la nueva regla.
     *
     * Por defecto regenera desde la fecha del corte más antiguo. Opcional:
     * pasar start_date / end_date / location_id para acotar.
     */
    public function regenerateHistorical(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(900);

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date', Carbon::now()->toDateString());
        $location_id = $request->get('location_id');

        // Si no se especifica start, usar el corte más antiguo del business.
        if (empty($start_date)) {
            $oldest = DailyCut::where('business_id', $business_id)->min('cut_date');
            $start_date = $oldest ?: Carbon::now()->subDays(90)->toDateString();
        }

        $loc_query = BusinessLocation::where('business_id', $business_id)->where('is_active', 1);
        if (!empty($location_id)) {
            $loc_query->where('id', $location_id);
        }
        $location_ids = $loc_query->pluck('id');

        $current = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);
        if ($end->gt(Carbon::now())) {
            $end = Carbon::now();
        }

        $today_str = Carbon::now()->toDateString();
        $count = 0;
        $errors = [];
        while ($current->lte($end)) {
            $date_str = $current->toDateString();
            foreach ($location_ids as $loc_id) {
                try {
                    $regen_cut = $this->util->upsert($business_id, $loc_id, $date_str, $user_id);
                    // Días pasados: cierra automáticamente (un día que ya pasó no debe quedar abierto).
                    if ($date_str !== $today_str && is_null($regen_cut->closed_at)) {
                        $regen_cut->closed_at = Carbon::now();
                        $regen_cut->closed_by = null;
                        $regen_cut->save();
                    }
                    $count++;
                } catch (\Throwable $e) {
                    $errors[] = "{$date_str} loc={$loc_id}: " . $e->getMessage();
                }
            }
            $current->addDay();
        }

        \Log::info("[regenerate-historical] business={$business_id} from={$start_date} to={$end->toDateString()} regenerated={$count} errors=" . count($errors));

        $msg = "Regenerados {$count} cortes ({$start_date} → {$end->toDateString()}).";
        if (!empty($errors)) {
            $msg .= " <br>Errores: " . implode(' | ', array_slice($errors, 0, 5));
        }

        return redirect()->route('daily-cuts.index')
            ->with('status', ['success' => empty($errors) ? 1 : 0, 'msg' => $msg]);
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
     * Genera el corte. Comportamiento depende de location_id:
     *   - location_id = "all" o vacío  → REFRESCA todas las sucursales SIN cerrar.
     *                                    (totales actualizados pero el cut sigue mutable.)
     *   - location_id = ID específico  → REGENERA esa sucursal Y la CIERRA.
     *                                    (acción del cajero al terminar su turno: "este
     *                                    es mi cut, ya no debe cambiar".)
     *
     * Esto reemplaza el viejo flujo de "Generar ahora" + "Cerrar caja" en dos pasos:
     * ahora el cajero hace todo en uno solo eligiendo su sucursal en el dropdown.
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
        $is_all = empty($location_id) || $location_id === 'all';

        try {
            if ($is_all) {
                // REFRESCAR todas — no cierra ninguna. Útil durante el día para ver
                // totales en vivo. Si una sucursal ya está cerrada, NO se sobreescribe
                // (respeta closed_at — usuario debe pasar por reopen si quiere cambiarla).
                $location_ids = BusinessLocation::where('business_id', $business_id)
                    ->where('is_active', 1)
                    ->pluck('id');
                $skipped = 0;
                $refreshed = 0;
                foreach ($location_ids as $loc_id) {
                    $existing = DailyCut::where('business_id', $business_id)
                        ->where('location_id', $loc_id)
                        ->where('cut_date', $date)
                        ->first();
                    if ($existing && $existing->closed_at) {
                        $skipped++;
                        continue;
                    }
                    $this->util->upsert($business_id, $loc_id, $date, $user_id);
                    $refreshed++;
                }
                $msg = "Cortes refrescados: {$refreshed}.";
                if ($skipped > 0) {
                    $msg .= " Saltados (ya cerrados): {$skipped}.";
                }
                $output = ['success' => true, 'msg' => $msg];
            } else {
                // GENERAR + CERRAR una sucursal específica (acción del cajero al terminar).
                $cut = $this->util->upsert($business_id, (int) $location_id, $date, $user_id);
                $cut->closed_at = Carbon::now();
                $cut->closed_by = $user_id;
                $cut->save();
                $output = ['success' => true, 'msg' => 'Corte generado y cerrado para esa sucursal.'];
            }
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->route('daily-cuts.index')->with('status', $output);
    }

    /**
     * Cierra DEFINITIVAMENTE el corte de una sucursal+fecha. Una vez cerrado:
     *   - El heartbeat de las 18:00 NO lo va a sobreescribir.
     *   - ensureCutsForRange NO lo regenera al abrir reportes.
     *   - Solo "Reabrir" lo vuelve a hacer mutable.
     *
     * Antes de cerrar, regenera el corte para que refleje las ventas más recientes.
     */
    public function close(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'date' => 'required|date',
            'location_id' => 'required|integer',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $date = $request->input('date');
        $location_id = (int) $request->input('location_id');

        try {
            // Verifica que la sucursal pertenezca al business
            \App\BusinessLocation::where('business_id', $business_id)->findOrFail($location_id);

            // Genera/regenera el corte con datos actuales (es el snapshot que va a quedar fijo)
            $cut = $this->util->upsert($business_id, $location_id, $date, $user_id);

            // Cierra el corte
            $cut->closed_at = Carbon::now();
            $cut->closed_by = $user_id;
            $cut->save();

            \Log::info("[daily-cut-close] business={$business_id} location={$location_id} date={$date} closed_by={$user_id}");

            $output = ['success' => 1, 'msg' => 'Corte cerrado definitivamente. Ya no se modificará.'];
        } catch (\Throwable $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->route('daily-cuts.index')->with('status', $output);
    }

    /**
     * Reabre un corte previamente cerrado. Solo admin. Después de reabrir,
     * el corte vuelve a ser mutable (heartbeat / ensureCutsForRange lo pueden tocar otra vez).
     */
    public function reopen(Request $request, $id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        try {
            $cut = DailyCut::where('business_id', $business_id)->findOrFail($id);
            $cut->closed_at = null;
            $cut->closed_by = null;
            $cut->save();

            \Log::info("[daily-cut-reopen] business={$business_id} cut_id={$id} location={$cut->location_id} date={$cut->cut_date} reopened_by=" . $request->session()->get('user.id'));

            $output = ['success' => 1, 'msg' => 'Corte reabierto. Volverá a actualizarse con las ventas actuales.'];
        } catch (\Throwable $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->route('daily-cuts.index')->with('status', $output);
    }
}
