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
            && ! auth()->user()->can('business_settings.access')) {
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
            $products = DB::table('transaction_sell_lines as tsl')
                ->join('products as p', 'p.id', '=', 'tsl.product_id')
                ->where('tsl.transaction_id', $o->id)
                ->pluck('p.name')->implode(', ');

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

        $request->validate([
            'technician_id' => 'nullable|integer',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'card_type' => 'nullable|string',
            'card_terminal_id' => 'nullable|integer',
            'usd_amount' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
        ]);

        $business_id = $request->session()->get('user.business_id');

        try {
            DB::beginTransaction();

            $transaction = Transaction::where('business_id', $business_id)
                ->where('id', $id)
                ->where('repair_status', 'pending')
                ->firstOrFail();

            $technician_id = $request->input('technician_id') ?: null;
            $payment_amount = (float) $request->input('payment_amount', 0);
            $method = $request->input('payment_method', 'cash');
            $card_type = $request->input('card_type') ?: null;
            $card_terminal_id = $request->input('card_terminal_id') ?: null;
            $usd_amount = (float) $request->input('usd_amount', 0);
            $exchange_rate = (float) $request->input('exchange_rate', 0);

            // Pago en dólares: el monto en MXN = usd * tipo de cambio; se guarda el desglose
            // para que el reporte de técnicos lo detecte como pago en dólares (D) + tipo de cambio.
            $denomination_breakdown = null;
            if ($usd_amount > 0 && $exchange_rate > 0) {
                $payment_amount = round($usd_amount * $exchange_rate, 2);
                $denomination_breakdown = json_encode([
                    'usd' => ['coins' => $usd_amount],
                    'exchange_rate' => $exchange_rate,
                    'usd_in_mxn' => $payment_amount,
                ]);
            }

            // Asignar el técnico a las líneas de la orden
            TransactionSellLine::where('transaction_id', $transaction->id)
                ->update(['technician_id' => $technician_id]);

            // Registrar el pago del saldo (si hay) — se AGREGA un pago nuevo sin borrar el anticipo.
            if ($payment_amount > 0) {
                $prefix_type = 'sell_payment';
                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type, $business_id);
                TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'amount' => $payment_amount,
                    'method' => $method,
                    'card_type' => $method === 'card' ? $card_type : null,
                    'card_terminal_id' => $method === 'card' ? $card_terminal_id : null,
                    'denomination_breakdown' => $denomination_breakdown,
                    'paid_on' => Carbon::now()->toDateTimeString(),
                    'created_by' => auth()->id(),
                    'payment_for' => $transaction->contact_id,
                    'payment_ref_no' => $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count, $business_id),
                    'business_id' => $business_id,
                ]);
            }

            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            $transaction->repair_status = 'delivered';
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
        if (! auth()->user()->can('business_settings.access')) {
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
        if (! auth()->user()->can('business_settings.access')) {
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

        // Paginación
        $orders = $baseQuery
            ->select(
                't.id', 't.invoice_no', 't.transaction_date', 't.repair_status', 't.final_total',
                't.location_id',
                'c.name as customer', 'c.mobile',
                'bl.name as location_name'
            )
            ->orderBy('t.transaction_date', 'desc')
            ->limit($length > 0 ? $length : 25)
            ->offset($start)
            ->get();

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
     * Cambia el técnico asignado a TODAS las líneas de una orden de reparación.
     */
    public function changeTechnician(Request $request, $id)
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'technician_id' => 'nullable|integer',
        ]);

        $business_id = $request->session()->get('user.business_id');

        try {
            $transaction = Transaction::where('business_id', $business_id)
                ->where('id', $id)
                ->whereNotNull('repair_status')
                ->firstOrFail();

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
