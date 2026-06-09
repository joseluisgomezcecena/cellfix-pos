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

        return view('repair_order.admin_index', compact('technicians'));
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
        $term = trim($request->get('term', ''));
        $status = $request->get('status', '');

        $q = DB::table('transactions as t')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->whereNotNull('t.repair_status');

        if (in_array($status, ['pending', 'delivered'])) {
            $q->where('t.repair_status', $status);
        }

        if ($term !== '') {
            $q->where(function ($w) use ($term) {
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

        $orders = $q->select(
            't.id', 't.invoice_no', 't.transaction_date', 't.repair_status', 't.final_total',
            'c.name as customer', 'c.mobile'
        )->orderBy('t.transaction_date', 'desc')->limit(50)->get();

        $result = [];
        foreach ($orders as $o) {
            // Producto(s) y técnico(s) actualmente asignados (puede ser uno o varios distintos)
            $lines = DB::table('transaction_sell_lines as tsl')
                ->join('products as p', 'p.id', '=', 'tsl.product_id')
                ->leftJoin('technicians as tc', 'tc.id', '=', 'tsl.technician_id')
                ->where('tsl.transaction_id', $o->id)
                ->select('p.name as product_name', 'tc.id as technician_id', 'tc.name as technician_name')
                ->get();

            $products = $lines->pluck('product_name')->unique()->implode(', ');
            $technicians = $lines->pluck('technician_name')->filter()->unique()->implode(', ');
            $current_technician_id = $lines->pluck('technician_id')->filter()->unique();
            // Si todas las líneas tienen el mismo técnico, devolvemos ese id; si difieren, null.
            $current_technician_id = $current_technician_id->count() === 1 ? $current_technician_id->first() : null;

            $result[] = [
                'id' => $o->id,
                'invoice_no' => $o->invoice_no,
                'date' => Carbon::parse($o->transaction_date)->format('d/m/Y H:i'),
                'customer' => $o->customer ?: 'Walk-In',
                'mobile' => $o->mobile,
                'products' => $products,
                'technician' => $technicians ?: '— sin asignar —',
                'current_technician_id' => $current_technician_id,
                'repair_status' => $o->repair_status,
                'total' => (float) $o->final_total,
            ];
        }

        return response()->json(['orders' => $result]);
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
