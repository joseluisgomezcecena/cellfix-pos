<?php

namespace App\Http\Controllers;

use App\CardTerminal;
use App\Technician;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairOrderController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    private function authorizeAccess()
    {
        if (! auth()->user()->can('sell.create')
            && ! auth()->user()->can('sell.update')
            && ! auth()->user()->can('business_settings.access') && ! auth()->user()->can('celfix.technicians.repair_orders')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Busca órdenes de reparación pendientes por nombre/teléfono del cliente o folio.
     */
    public function pending(Request $request)
    {
        $this->authorizeAccess();

        $business_id = $request->session()->get('user.business_id');
        $term = trim($request->get('term', ''));

        $q = DB::table('transactions as t')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.repair_status', 'pending');

        if ($term !== '') {
            $q->where(function ($w) use ($term) {
                $w->where('c.name', 'like', "%{$term}%")
                    ->orWhere('c.mobile', 'like', "%{$term}%")
                    ->orWhere('c.supplier_business_name', 'like', "%{$term}%")
                    ->orWhere('t.invoice_no', 'like', "%{$term}%");
            });
        }

        $orders = $q->select(
            't.id', 't.invoice_no', 't.transaction_date', 't.final_total',
            'c.name as customer', 'c.mobile',
            DB::raw('(SELECT COALESCE(SUM(amount),0) FROM transaction_payments tp WHERE tp.transaction_id = t.id AND tp.is_return = 0) as paid')
        )->orderBy('t.transaction_date', 'desc')->limit(40)->get();

        $result = [];
        foreach ($orders as $o) {
            $lines = DB::table('transaction_sell_lines as tsl')
                ->join('products as p', 'p.id', '=', 'tsl.product_id')
                ->where('tsl.transaction_id', $o->id)
                ->select('p.name as product_name', 'tsl.technician_id')
                ->get();
            $products = $lines->pluck('product_name')->implode(', ');
            // Técnico ya asignado (si hay varios distintos, no preseleccionamos ninguno
            // para forzar decisión manual; si todas las líneas tienen el mismo, ese).
            $tech_ids = $lines->pluck('technician_id')->filter()->unique();
            $assigned_technician_id = $tech_ids->count() === 1 ? (int) $tech_ids->first() : null;

            $result[] = [
                'id' => $o->id,
                'invoice_no' => $o->invoice_no,
                'date' => Carbon::parse($o->transaction_date)->format('d/m/Y'),
                'customer' => $o->customer ?: 'Walk-In',
                'mobile' => $o->mobile,
                'total' => (float) $o->final_total,
                'paid' => (float) $o->paid,
                'balance' => round((float) $o->final_total - (float) $o->paid, 2),
                'products' => $products,
                'assigned_technician_id' => $assigned_technician_id,
            ];
        }

        $technicians = Technician::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($t) {
                return ['id' => $t->id, 'name' => $t->name];
            });

        $terminals = [];
        if (\Schema::hasTable('card_terminals')) {
            $terminals = CardTerminal::where('business_id', $business_id)
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function ($t) {
                    return ['id' => $t->id, 'name' => $t->name];
                });
        }

        // Leemos directo de BD para que el valor SIEMPRE sea el más reciente,
        // sin importar cuándo se logueó el cajero. La sesión queda stale después
        // de que admin actualiza el tipo de cambio en /exchange-rate.
        $exchange_rate = (float) (DB::table('business')->where('id', $business_id)->value('cash_exchange_rate') ?: 18);

        return response()->json([
            'orders' => $result,
            'technicians' => $technicians,
            'terminals' => $terminals,
            'exchange_rate' => $exchange_rate,
        ]);
    }

    /**
     * Entrega: asigna técnico, cobra el saldo y marca la orden como entregada.
     */
    public function deliver(Request $request, $id)
    {
        $this->authorizeAccess();

        // Nuevo contrato: acepta un array `payments`, cada uno con method + montos +
        // desglose por fila. Soporta pago múltiple (cash + tarjeta + transferencia) y
        // cada fila cash puede combinar MXN + USD en el mismo pago.
        $request->validate([
            'technician_id' => 'nullable|integer',
            'payments' => 'nullable|array',
            'payments.*.method' => 'required_with:payments|string',
            'payments.*.amount_mxn' => 'nullable|numeric|min:0',
            'payments.*.amount_usd' => 'nullable|numeric|min:0',
            'payments.*.exchange_rate' => 'nullable|numeric|min:0',
            'payments.*.card_type' => 'nullable|string',
            'payments.*.card_terminal_id' => 'nullable|integer',
            'payments.*.mxn_breakdown' => 'nullable|string',
            'payments.*.mxn_coins' => 'nullable|numeric|min:0',
            'payments.*.usd_breakdown' => 'nullable|string',
            'payments.*.usd_coins' => 'nullable|numeric|min:0',
        ]);

        $payments_in = $request->input('payments', []);
        if (!is_array($payments_in)) { $payments_in = []; }

        // Guard servidor: por cada fila cash con monto > 0 exigir al menos algo de
        // desglose (MXN o USD). Es doble-check del validador JS.
        foreach ($payments_in as $i => $p) {
            $m = $p['method'] ?? '';
            $amx = (float) ($p['amount_mxn'] ?? 0);
            $ausd = (float) ($p['amount_usd'] ?? 0);
            if ($m === 'cash') {
                if ($amx > 0) {
                    $mxn_bd = json_decode($p['mxn_breakdown'] ?? '', true);
                    $mxn_coins = (float) ($p['mxn_coins'] ?? 0);
                    if ((!is_array($mxn_bd) || empty($mxn_bd)) && $mxn_coins <= 0) {
                        return ['success' => 0, 'msg' => 'Falta desglose MXN en la fila ' . ($i + 1) . '.'];
                    }
                    // Suma real (billetes + monedas) vs amount MXN declarado.
                    $sum_mxn = $mxn_coins;
                    if (is_array($mxn_bd)) foreach ($mxn_bd as $face => $cnt) {
                        if (is_numeric($face)) $sum_mxn += (int) $face * (int) $cnt;
                    }
                    if (abs($sum_mxn - $amx) > 0.5) {
                        return ['success' => 0, 'msg' => sprintf(
                            'Fila %d: el desglose MXN suma $%s pero el monto es $%s.',
                            $i + 1, number_format($sum_mxn, 2), number_format($amx, 2))];
                    }
                }
                if ($ausd > 0) {
                    $usd_bd = json_decode($p['usd_breakdown'] ?? '', true);
                    $usd_coins = (float) ($p['usd_coins'] ?? 0);
                    if ((!is_array($usd_bd) || empty($usd_bd)) && $usd_coins <= 0) {
                        return ['success' => 0, 'msg' => 'Falta desglose USD en la fila ' . ($i + 1) . '.'];
                    }
                    $sum_usd = $usd_coins;
                    if (is_array($usd_bd)) foreach ($usd_bd as $face => $cnt) {
                        if (is_numeric($face)) $sum_usd += (int) $face * (int) $cnt;
                    }
                    if (abs($sum_usd - $ausd) > 0.5) {
                        return ['success' => 0, 'msg' => sprintf(
                            'Fila %d: el desglose USD suma $%s pero el monto es $%s.',
                            $i + 1, number_format($sum_usd, 2), number_format($ausd, 2))];
                    }
                }
            }
            if ($m === 'card' && empty($p['card_terminal_id'])) {
                return ['success' => 0, 'msg' => 'Falta terminal en la fila ' . ($i + 1) . '.'];
            }
        }

        $business_id = $request->session()->get('user.business_id');

        try {
            DB::beginTransaction();

            $transaction = Transaction::where('business_id', $business_id)
                ->where('id', $id)
                ->where('repair_status', 'pending')
                ->firstOrFail();

            $technician_id = $request->input('technician_id') ?: null;

            // Asignar el técnico a las líneas de la orden
            TransactionSellLine::where('transaction_id', $transaction->id)
                ->update(['technician_id' => $technician_id]);

            // Registrar CADA fila como un TransactionPayment nuevo (no borra el anticipo).
            $prefix_type = 'sell_payment';
            foreach ($payments_in as $p) {
                $m = $p['method'] ?? 'cash';
                $amount_mxn = (float) ($p['amount_mxn'] ?? 0);
                $amount_usd = (float) ($p['amount_usd'] ?? 0);
                $rate = (float) ($p['exchange_rate'] ?? 0);
                $usd_in_mxn = ($amount_usd > 0 && $rate > 0) ? round($amount_usd * $rate, 2) : 0;
                $total_row = round($amount_mxn + $usd_in_mxn, 2);
                if ($total_row <= 0) { continue; }

                // Construir denomination_breakdown: mismo shape que usa POS
                // { mxn: {face: count, coins}, usd: {face: count, coins}, exchange_rate, usd_in_mxn }
                $bd = [];
                if ($m === 'cash') {
                    $mxn_map = json_decode($p['mxn_breakdown'] ?? '', true);
                    $mxn_coins = (float) ($p['mxn_coins'] ?? 0);
                    if (is_array($mxn_map) && !empty($mxn_map)) {
                        $bd['mxn'] = array_map('intval', $mxn_map);
                    } elseif ($mxn_coins > 0) {
                        $bd['mxn'] = [];
                    }
                    if ($mxn_coins > 0) { $bd['mxn']['coins'] = $mxn_coins; }

                    $usd_map = json_decode($p['usd_breakdown'] ?? '', true);
                    $usd_coins = (float) ($p['usd_coins'] ?? 0);
                    if (is_array($usd_map) && !empty($usd_map)) {
                        $bd['usd'] = array_map('intval', $usd_map);
                    } elseif ($usd_coins > 0) {
                        $bd['usd'] = [];
                    }
                    if ($usd_coins > 0) { $bd['usd']['coins'] = $usd_coins; }

                    if (!empty($bd['usd']) && $rate > 0) {
                        $bd['exchange_rate'] = $rate;
                        $bd['usd_in_mxn'] = $usd_in_mxn;
                    }
                }

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type, $business_id);
                TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'amount' => $total_row,
                    'method' => $m,
                    'card_type' => $m === 'card' ? ($p['card_type'] ?? null) : null,
                    'card_terminal_id' => $m === 'card' ? ($p['card_terminal_id'] ?? null) : null,
                    'denomination_breakdown' => !empty($bd) ? json_encode($bd) : null,
                    'paid_on' => Carbon::now()->toDateTimeString(),
                    'created_by' => auth()->id(),
                    'payment_for' => $transaction->contact_id,
                    'payment_ref_no' => $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count, $business_id),
                    'business_id' => $business_id,
                ]);
            }

            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            $transaction->repair_status = 'delivered';
            // Timestamp de entrega — usado por los reportes/corte para atribuir la
            // reparación al día en que se cerró, no al día en que se recibió.
            // Guard con hasColumn por si la migración aún no corre en algún ambiente.
            if (\Schema::hasColumn('transactions', 'repair_delivered_at')) {
                $transaction->repair_delivered_at = Carbon::now();
            }
            $transaction->save();

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.repair_delivered'), 'transaction_id' => $transaction->id];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return $output;
    }

    /**
     * Sección administrativa: ver todas las reparaciones (pendientes o entregadas)
     * y cambiar el técnico asignado en caso de error de captura desde el POS.
     */
    public function adminIndex()
    {
        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('celfix.technicians.repair_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $technicians = Technician::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $locations = \App\BusinessLocation::forDropdown($business_id);

        return view('repair_order.admin_index', compact('technicians', 'locations'));
    }

    /**
     * Búsqueda AJAX de reparaciones para la sección administrativa.
     * Acepta término de búsqueda (nombre cliente, teléfono, folio o nombre de producto)
     * y opcionalmente filtra por estatus (pending/delivered).
     */
    public function adminSearch(Request $request)
    {
        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('celfix.technicians.repair_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // DataTables server-side params
        $draw = (int) $request->get('draw', 1);
        $start = (int) $request->get('start', 0);
        $length = (int) $request->get('length', 25);

        // Filtros de negocio (custom)
        $term = trim($request->input('search.value', '') ?: $request->get('term', ''));
        $status = $request->get('status', '');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $location_id = $request->get('location_id');
        $technician_id = $request->get('technician_id');

        // "Reparación" = transacción con al menos una línea con technician_id asignado.
        // No usamos repair_status como filtro porque casi ninguna reparación histórica
        // lo tiene poblado (20 de 2,827 al 07/2026). El filtro pending/delivered sí
        // usa repair_status para las que sí lo tienen.
        $baseQuery = DB::table('transactions as t')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereExists(function ($sub) use ($technician_id) {
                $sub->select(DB::raw(1))
                    ->from('transaction_sell_lines as tsl_r')
                    ->whereRaw('tsl_r.transaction_id = t.id')
                    ->whereNotNull('tsl_r.technician_id');
                if (!empty($technician_id)) {
                    $sub->where('tsl_r.technician_id', $technician_id);
                }
            });

        // Filtro por estado — con la misma lógica que la UI:
        //   - 'pending' → solo las que tienen repair_status='pending' explícito
        //   - 'delivered' → las que tienen repair_status='delivered' O NULL
        //     (NULL = transacción finalizada sin marca explícita = ya entregada por default)
        if ($status === 'pending') {
            $baseQuery->where('t.repair_status', 'pending');
        } elseif ($status === 'delivered') {
            $baseQuery->where(function ($w) {
                $w->where('t.repair_status', 'delivered')
                    ->orWhereNull('t.repair_status');
            });
        }

        if (!empty($start_date)) {
            $baseQuery->whereRaw('DATE(t.transaction_date) >= ?', [$start_date]);
        }
        if (!empty($end_date)) {
            $baseQuery->whereRaw('DATE(t.transaction_date) <= ?', [$end_date]);
        }

        if (!empty($location_id)) {
            $baseQuery->where('t.location_id', $location_id);
        }

        if ($term !== '') {
            $baseQuery->where(function ($w) use ($term) {
                $w->where('c.name', 'like', "%{$term}%")
                    ->orWhere('c.mobile', 'like', "%{$term}%")
                    ->orWhere('c.supplier_business_name', 'like', "%{$term}%")
                    ->orWhere('t.invoice_no', 'like', "%{$term}%")
                    ->orWhereIn('t.id', function ($sub) use ($term) {
                        $sub->select('tsl.transaction_id')
                            ->from('transaction_sell_lines as tsl')
                            ->join('products as p', 'p.id', '=', 'tsl.product_id')
                            ->where('p.name', 'like', "%{$term}%");
                    });
            });
        }

        // recordsTotal = total sin filtros de búsqueda pero con el filtro base
        // (para el datatables mostrar "N of M"). Aquí lo simplificamos y usamos
        // el filtered para ambos porque el filtro base ya define "reparaciones".
        $recordsFiltered = (clone $baseQuery)->count();
        $recordsTotal = $recordsFiltered;

        // Select: agrego repair_delivered_at si existe (fallback a updated_at para
        // reparaciones históricas entregadas antes de la migración).
        $select_cols = [
            't.id', 't.invoice_no', 't.transaction_date', 't.repair_status', 't.final_total',
            't.location_id', 't.updated_at',
            'c.name as customer', 'c.mobile',
            'bl.name as location_name',
        ];
        if (\Schema::hasColumn('transactions', 'repair_delivered_at')) {
            $select_cols[] = 't.repair_delivered_at';
        }

        // Paginación
        $orders = $baseQuery
            ->select($select_cols)
            ->orderBy('t.transaction_date', 'desc')
            ->limit($length > 0 ? $length : 25)
            ->offset($start)
            ->get();

        // Anticipo por orden = SUM de pagos con paid_on < fecha de entrega.
        // Si la orden aún NO se ha entregado, todos los pagos son "anticipo" hasta ahora.
        // Batch para las órdenes visibles en la página, en 1 sola query.
        $tx_ids = $orders->pluck('id')->toArray();
        $payments_by_tx = [];
        if (!empty($tx_ids)) {
            $payments_rows = DB::table('transaction_payments')
                ->whereIn('transaction_id', $tx_ids)
                ->where('is_return', 0)
                ->select('transaction_id', 'amount', 'paid_on')
                ->orderBy('paid_on')
                ->get();
            foreach ($payments_rows as $p) {
                $payments_by_tx[$p->transaction_id][] = $p;
            }
        }

        $data = [];
        foreach ($orders as $o) {
            $lines = DB::table('transaction_sell_lines as tsl')
                ->join('products as p', 'p.id', '=', 'tsl.product_id')
                ->leftJoin('technicians as tc', 'tc.id', '=', 'tsl.technician_id')
                ->where('tsl.transaction_id', $o->id)
                ->select('p.name as product_name', 'tc.id as technician_id', 'tc.name as technician_name')
                ->get();

            $products = $lines->pluck('product_name')->unique()->implode(', ');
            $technicians_names = $lines->pluck('technician_name')->filter()->unique()->implode(', ');
            $current_technician_id = $lines->pluck('technician_id')->filter()->unique();
            $current_technician_id = $current_technician_id->count() === 1 ? $current_technician_id->first() : null;

            // Fecha de entrega: preferimos repair_delivered_at si la migración corrió.
            // Fallback para históricos: updated_at si repair_status='delivered'.
            $delivered_at = null;
            if (!empty($o->repair_delivered_at ?? null)) {
                $delivered_at = Carbon::parse($o->repair_delivered_at)->format('d/m/Y H:i');
            } elseif ($o->repair_status === 'delivered' && !empty($o->updated_at)) {
                $delivered_at = Carbon::parse($o->updated_at)->format('d/m/Y H:i');
            }

            // Anticipo: si YA está entregada, es la suma de pagos antes de la entrega.
            // Si NO está entregada, todos los pagos hasta ahora cuentan como anticipo.
            $pays = $payments_by_tx[$o->id] ?? [];
            $anticipo_amount = 0.0;
            $anticipo_date = null;
            $cutoff = null;
            if (!empty($o->repair_delivered_at ?? null)) {
                $cutoff = Carbon::parse($o->repair_delivered_at);
            }
            foreach ($pays as $p) {
                $paid_on = Carbon::parse($p->paid_on);
                $is_before_delivery = ($cutoff === null) || $paid_on->lt($cutoff);
                if ($is_before_delivery) {
                    $anticipo_amount += (float) $p->amount;
                    if ($anticipo_date === null) {
                        $anticipo_date = $paid_on->format('d/m/Y H:i');
                    }
                }
            }

            $data[] = [
                'id' => $o->id,
                'invoice_no' => $o->invoice_no,
                'date' => Carbon::parse($o->transaction_date)->format('d/m/Y H:i'),
                'customer' => $o->customer ?: 'Walk-In',
                'mobile' => $o->mobile,
                'products' => $products,
                'technician' => $technicians_names ?: '— sin asignar —',
                'current_technician_id' => $current_technician_id,
                'repair_status' => $o->repair_status,
                'location' => $o->location_name,
                'total' => (float) $o->final_total,
                'anticipo_amount' => $anticipo_amount,
                'anticipo_date' => $anticipo_date,
                'delivered_at' => $delivered_at,
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Devuelve las líneas de una reparación (producto + técnico actual por línea)
     * para poblar el modal de cambio de técnico cuando hay varios técnicos.
     */
    public function repairLines(Request $request, $id)
    {
        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('celfix.technicians.repair_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $transaction = Transaction::where('business_id', $business_id)
            ->where('id', $id)
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('transaction_sell_lines as tsl_r')
                    ->whereRaw('tsl_r.transaction_id = transactions.id')
                    ->whereNotNull('tsl_r.technician_id');
            })
            ->firstOrFail();

        $lines = DB::table('transaction_sell_lines as tsl')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->leftJoin('technicians as tc', 'tc.id', '=', 'tsl.technician_id')
            ->where('tsl.transaction_id', $transaction->id)
            ->whereNotNull('tsl.technician_id')
            ->select(
                'tsl.id as tsl_id',
                'p.name as product_name',
                'tsl.technician_id',
                'tc.name as technician_name'
            )
            ->get();

        return response()->json(['success' => 1, 'lines' => $lines]);
    }

    /**
     * Cambia el técnico asignado. Acepta dos formatos:
     *   - Legacy: {technician_id: X} → aplica a TODAS las líneas.
     *   - Por línea: {assignments: {tsl_id: technician_id, ...}} → cada línea a un técnico
     *     distinto. Útil cuando la reparación tiene 2+ técnicos y queremos mantenerlos.
     */
    public function changeTechnician(Request $request, $id)
    {
        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('celfix.technicians.repair_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'technician_id' => 'nullable|integer',
            'assignments' => 'nullable|array',
        ]);

        $business_id = $request->session()->get('user.business_id');

        try {
            // Consideramos "reparación" cualquier transacción del negocio con al menos una
            // línea con technician_id asignado — mismo criterio que el listado admin.
            // Antes exigíamos whereNotNull('repair_status'), pero eso bloqueaba las miles
            // de reparaciones históricas donde ese campo quedó NULL.
            $transaction = Transaction::where('business_id', $business_id)
                ->where('id', $id)
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('transaction_sell_lines as tsl_r')
                        ->whereRaw('tsl_r.transaction_id = transactions.id')
                        ->whereNotNull('tsl_r.technician_id');
                })
                ->firstOrFail();

            $assignments = $request->input('assignments');
            // Modo por-línea: solo si viene un array no vacío con al menos una asignación.
            if (is_array($assignments) && !empty($assignments)) {
                // Validar cada técnico contra el negocio.
                $tech_ids = array_filter(array_values($assignments), fn ($v) => $v !== null && $v !== '');
                if (!empty($tech_ids)) {
                    $valid = Technician::where('business_id', $business_id)
                        ->whereIn('id', $tech_ids)->pluck('id')->toArray();
                    $invalid = array_diff($tech_ids, $valid);
                    if (!empty($invalid)) {
                        return response()->json(['success' => 0, 'msg' => 'Uno o más técnicos no son válidos.']);
                    }
                }
                // tsl_ids deben pertenecer a la transacción — evita que un admin de otro
                // negocio mande tsl_ids ajenos.
                $tx_tsl_ids = TransactionSellLine::where('transaction_id', $transaction->id)
                    ->pluck('id')->toArray();
                DB::beginTransaction();
                foreach ($assignments as $tsl_id => $tech_id) {
                    if (!in_array((int) $tsl_id, $tx_tsl_ids)) continue;
                    TransactionSellLine::where('id', $tsl_id)
                        ->update(['technician_id' => empty($tech_id) ? null : (int) $tech_id]);
                }
                DB::commit();
                return response()->json(['success' => 1, 'msg' => 'Técnicos actualizados por línea.']);
            }

            // Modo legacy: aplica UN técnico a TODAS las líneas.
            $technician_id = $request->input('technician_id') ?: null;

            // Validar que el técnico (si se especifica) pertenezca al business
            if (!empty($technician_id)) {
                $exists = Technician::where('business_id', $business_id)
                    ->where('id', $technician_id)
                    ->exists();
                if (!$exists) {
                    return response()->json(['success' => 0, 'msg' => 'Técnico no válido']);
                }
            }

            TransactionSellLine::where('transaction_id', $transaction->id)
                ->update(['technician_id' => $technician_id]);

            $output = ['success' => 1, 'msg' => 'Técnico actualizado correctamente'];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return response()->json($output);
    }
}
