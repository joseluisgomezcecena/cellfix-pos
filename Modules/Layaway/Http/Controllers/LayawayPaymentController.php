<?php

namespace Modules\Layaway\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Layaway\Entities\Layaway;
use Modules\Layaway\Entities\LayawayPayment;
use App\Utils\TransactionUtil;
use App\CashRegister;
use DB;
use Yajra\DataTables\Facades\DataTables;

class LayawayPaymentController extends Controller
{
    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of payments
     * @return Renderable
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('layaway.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $payments = LayawayPayment::whereHas('layaway', function ($query) use ($business_id) {
                $query->where('business_id', $business_id);
            })->with(['layaway.contact', 'processedBy']);

            if ($request->has('layaway_id')) {
                $payments->where('layaway_id', $request->layaway_id);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $start = $request->start_date;
                $end = $request->end_date;
                $payments->whereDate('payment_date', '>=', $start)
                    ->whereDate('payment_date', '<=', $end);
            }

            return DataTables::of($payments)
                ->addColumn('layaway_number', function ($row) {
                    return '<a href="' . action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@show', [$row->layaway_id]) . '">' . $row->layaway->layaway_number . '</a>';
                })
                ->addColumn('customer', function ($row) {
                    return $row->layaway->contact ? $row->layaway->contact->name : '';
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->amount . '</span>';
                })
                ->editColumn('payment_date', function ($row) {
                    return \Carbon\Carbon::parse($row->payment_date)->format('d/m/Y H:i');
                })
                ->editColumn('payment_method', function ($row) {
                    return $row->formatted_method;
                })
                ->editColumn('processed_by', function ($row) {
                    return $row->processedBy ? $row->processedBy->first_name . ' ' . $row->processedBy->last_name : '';
                })
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (auth()->user()->can('layaway.view')) {
                        $html = '<a href="' . action('\\Modules\\Layaway\\Http\\Controllers\\LayawayPaymentController@printReceipt', [$row->id]) . '" target="_blank" class="btn btn-xs btn-primary">
                            <i class="fa fa-print"></i> ' . __("layaway::lang.print_receipt") . '</a>';
                    }
                    return $html;
                })
                ->rawColumns(['layaway_number', 'amount', 'action'])
                ->make(true);
        }

        return view('layaway::payments.index');
    }

    /**
     * Show form for making a payment
     * @param int $layaway_id
     * @return Renderable
     */
    public function create($layaway_id)
    {
        if (!auth()->user()->can('layaway.process_payment')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $layaway = Layaway::where('business_id', $business_id)
            ->with(['contact', 'payments'])
            ->findOrFail($layaway_id);

        if (!in_array($layaway->status, ['pending', 'active'])) {
            return redirect()->back()->with('error', __('layaway::lang.cannot_make_payment'));
        }

        $payment_methods = $this->transactionUtil->payment_types(null, true, $business_id);

        $registers = CashRegister::where('cash_registers.business_id', $business_id)
            ->where('cash_registers.status', 'open')
            ->join('users', 'cash_registers.user_id', '=', 'users.id')
            ->selectRaw("cash_registers.id, CONCAT(users.first_name, ' ', users.last_name) as cashier_name")
            ->pluck('cashier_name', 'id');

        return view('layaway::payments.create', compact('layaway', 'payment_methods', 'registers'));
    }

    /**
     * Store a newly created payment
     * @param Request $request
     * @param int $layaway_id
     * @return Response
     */
    public function store(Request $request, $layaway_id)
    {
        if (!auth()->user()->can('layaway.process_payment')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);

        $business_id_v = request()->session()->get('user.business_id');
        $payments_in = $request->input('payments', []);
        $total_amount = (float) $request->input('amount');

        // La suma de renglones debe igualar el monto a pagar (tolerancia 0.01).
        $rows_sum = 0.0;
        foreach ($payments_in as $p) {
            $rows_sum += (float) ($p['amount'] ?? 0);
        }
        if (abs($rows_sum - $total_amount) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'La suma de los métodos de pago (%s) no coincide con el monto a pagar (%s).',
                    number_format($rows_sum, 2), number_format($total_amount, 2)
                ),
            ], 422);
        }

        // Validaciones por renglón (equivalentes a las del POS).
        $has_terminals = \App\CardTerminal::where('business_id', $business_id_v)
            ->where('is_active', 1)->exists();
        foreach ($payments_in as $i => $p) {
            $method = $p['method'] ?? null;
            $amount = (float) ($p['amount'] ?? 0);
            if ($method === 'card' && $has_terminals && empty($p['card_terminal_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Renglón ' . ($i + 1) . ': debes seleccionar una terminal para el pago con tarjeta.',
                ], 422);
            }
            if ($method === 'cash') {
                $bd = $p['denomination_breakdown'] ?? null;
                $has_bd = false;
                if (!empty($bd)) {
                    $parsed = is_string($bd) ? json_decode($bd, true) : $bd;
                    $has_bd = is_array($parsed) && !empty($parsed);
                }
                if (!$has_bd) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Renglón ' . ($i + 1) . ': debes capturar el desglose de billetes en efectivo.',
                    ], 422);
                }
            }
        }

        try {
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $layaway = Layaway::where('business_id', $business_id)
                ->with('transaction')
                ->findOrFail($layaway_id);

            if (!in_array($layaway->status, ['pending', 'active'])) {
                throw new \Exception('Cannot make payment for this layaway.');
            }

            if ($request->amount > $layaway->balance_due) {
                throw new \Exception('Payment amount exceeds balance due.');
            }

            // Multi-pago: creamos un TransactionPayment + un LayawayPayment por cada
            // renglón. El "primer" LayawayPayment se guarda como referencia principal
            // para el recibo (payment_id que se regresa al JS).
            $first_layaway_payment = null;
            foreach ($payments_in as $p) {
                $method = $p['method'];
                $row_amount = (float) $p['amount'];

                $ref_count = $this->transactionUtil->setAndGetReferenceCount('payment', $business_id);
                $payment_ref_no = $this->transactionUtil->generateReferenceNumber('payment', $ref_count, $business_id);

                $payment_data = [
                    'transaction_id' => $layaway->transaction->id,
                    'business_id' => $business_id,
                    'is_return' => 0,
                    'amount' => $row_amount,
                    'method' => $method,
                    'payment_ref_no' => $payment_ref_no,
                    'paid_on' => $request->payment_date,
                    'created_by' => $user_id,
                    'payment_for' => $layaway->contact_id,
                    'note' => 'Layaway payment - ' . $layaway->layaway_number . ($request->notes ? ' - ' . $request->notes : ''),
                ];
                if ($method === 'card') {
                    $payment_data['card_type'] = $p['card_type'] ?? null;
                    $payment_data['card_terminal_id'] = $p['card_terminal_id'] ?? null;
                    $payment_data['card_number'] = $p['card_number'] ?? null;
                    $payment_data['card_holder_name'] = $p['card_holder_name'] ?? null;
                } elseif ($method === 'cheque') {
                    $payment_data['cheque_number'] = $p['cheque_number'] ?? null;
                    $payment_data['bank_account_number'] = $p['bank_account_number'] ?? null;
                } elseif ($method === 'bank_transfer') {
                    $payment_data['bank_account_number'] = $p['bank_account_number'] ?? null;
                } elseif ($method === 'other') {
                    $payment_data['transaction_no'] = $p['transaction_no'] ?? null;
                } elseif ($method === 'cash') {
                    $bd = $p['denomination_breakdown'] ?? null;
                    if (!empty($bd)) {
                        $payment_data['denomination_breakdown'] = is_string($bd) ? $bd : json_encode($bd);
                    }
                }

                $transaction_payment = \App\TransactionPayment::create($payment_data);

                // Cambio dado en efectivo: solo aplica al renglón cash cuando la
                // cajera capturó más billetes que el monto del renglón.
                if ($method === 'cash') {
                    $change_return_amount = (float) ($p['change_return_amount'] ?? 0);
                    if ($change_return_amount > 0.01) {
                        \App\TransactionPayment::create([
                            'transaction_id' => $layaway->transaction->id,
                            'business_id' => $business_id,
                            'is_return' => 1,
                            'amount' => $change_return_amount,
                            'method' => 'cash',
                            'payment_ref_no' => $payment_ref_no . '-CR',
                            'paid_on' => $request->payment_date,
                            'created_by' => $user_id,
                            'payment_for' => $layaway->contact_id,
                            'note' => 'Cambio dado en efectivo — layaway ' . $layaway->layaway_number,
                        ]);
                    }
                }

                $lp = LayawayPayment::create([
                    'layaway_id' => $layaway->id,
                    'amount' => $row_amount,
                    'payment_method' => $method,
                    'payment_date' => $request->payment_date,
                    'processed_by' => $user_id,
                    'cash_register_id' => $request->cash_register_id,
                    'transaction_payment_id' => $transaction_payment->id,
                    'notes' => $request->notes,
                ]);
                if ($first_layaway_payment === null) {
                    $first_layaway_payment = $lp;
                }
            }
            $payment = $first_layaway_payment;

            // Update balance and status
            $layaway->updateBalance();

            // Update status based on payment amount
            $total_paid = $layaway->payments()->sum('amount');

            if ($layaway->balance_due <= 0) {
                // Fully paid - mark as completed + set completed_at for the cut consolidation.
                // El cut diario usa completed_at para "concentrar" todos los pagos del apartado
                // en el día de entrega — en vez de sumarlos en los días de los abonos parciales.
                $layaway->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                // Update transaction payment status to paid
                $layaway->transaction->update(['payment_status' => 'paid']);
            } elseif ($layaway->status == 'pending' && $total_paid > 0) {
                // Any payment on pending layaway activates it
                $layaway->update(['status' => 'active']);
                // Update transaction payment status to partial
                $layaway->transaction->update(['payment_status' => 'partial']);
            } elseif ($total_paid > 0) {
                // Update transaction payment status to partial if any payment exists
                $layaway->transaction->update(['payment_status' => 'partial']);
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('layaway::lang.payment_added_successfully'),
                'payment_id' => $payment->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }

        if (request()->ajax()) {
            return $output;
        }

        return redirect()->action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@show', $layaway_id)
            ->with('status', $output);
    }

    /**
     * Print payment receipt
     * @param int $id
     * @return Response
     */
    public function printReceipt($id)
    {
        if (!auth()->user()->can('layaway.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $payment = LayawayPayment::whereHas('layaway', function ($query) use ($business_id) {
            $query->where('business_id', $business_id);
        })->with(['layaway.contact', 'layaway.location', 'processedBy'])
            ->findOrFail($id);

        $business = \App\Business::find($business_id);

        return view('layaway::payments.receipt', compact('payment', 'business'));
    }

    /**
     * Get payment history for a layaway
     * @param int $layaway_id
     * @return Response
     */
    public function history($layaway_id)
    {
        if (!auth()->user()->can('layaway.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $layaway = Layaway::where('business_id', $business_id)
            ->with(['payments.processedBy'])
            ->findOrFail($layaway_id);

        $payments = $layaway->payments()->orderBy('payment_date', 'desc')->get();

        return view('layaway::payments.history', compact('layaway', 'payments'));
    }
}