<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ExpenseCategory;
use App\Product;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\VariationLocationDetails;
use App\WarrantyClaim;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class WarrantyClaimController extends Controller
{
    protected $productUtil;
    protected $transactionUtil;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /** Chequeo de permiso: cualquiera con acceso a crear ventas puede crear garantías. */
    private function canUse()
    {
        $u = auth()->user();
        return $u->can('sell.create')
            || $u->can('direct_sell.access')
            || $u->can('business_settings.access')
            || $u->can('celfix.warranty.access');
    }

    public function index(Request $request)
    {
        if (!$this->canUse()) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $q = WarrantyClaim::where('warranty_claims.business_id', $business_id)
                ->leftJoin('contacts as c', 'c.id', '=', 'warranty_claims.contact_id')
                ->leftJoin('business_locations as bl', 'bl.id', '=', 'warranty_claims.location_id')
                ->leftJoin('users as u', 'u.id', '=', 'warranty_claims.created_by')
                ->leftJoin('transactions as t', 't.id', '=', 'warranty_claims.original_sell_transaction_id')
                ->select(
                    'warranty_claims.id',
                    'warranty_claims.ref_no',
                    'warranty_claims.claim_date',
                    'warranty_claims.claim_type',
                    'warranty_claims.status',
                    'warranty_claims.original_product_name',
                    'warranty_claims.replacement_product_name',
                    'warranty_claims.refund_amount',
                    'warranty_claims.price_difference',
                    't.invoice_no as original_invoice',
                    'bl.name as location_name',
                    'c.name as customer_name',
                    DB::raw("CONCAT(COALESCE(u.surname,''),' ',COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as created_by_name")
                );

            if ($request->filled('location_id')) {
                $q->where('warranty_claims.location_id', $request->location_id);
            }
            if ($request->filled('claim_type')) {
                $q->where('warranty_claims.claim_type', $request->claim_type);
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $q->whereBetween('warranty_claims.claim_date', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            }

            return DataTables::of($q)
                ->editColumn('claim_date', function ($r) {
                    return Carbon::parse($r->claim_date)->format('d/m/Y H:i');
                })
                ->editColumn('claim_type', function ($r) {
                    $label = WarrantyClaim::claimTypeLabel($r->claim_type);
                    $color = [
                        'refund' => 'bg-red',
                        'replacement_same' => 'bg-blue',
                        'replacement_higher' => 'bg-green',
                        'replacement_lower' => 'bg-yellow',
                    ][$r->claim_type] ?? 'bg-gray';
                    return '<span class="label ' . $color . '">' . $label . '</span>';
                })
                ->editColumn('status', function ($r) {
                    return $r->status === 'cancelled'
                        ? '<span class="label label-default">Cancelada</span>'
                        : '<span class="label label-success">Completada</span>';
                })
                ->addColumn('amount', function ($r) {
                    if (!is_null($r->refund_amount)) {
                        return '-$' . number_format((float) $r->refund_amount, 2);
                    }
                    if (!is_null($r->price_difference)) {
                        $sign = $r->price_difference >= 0 ? '+' : '';
                        return $sign . '$' . number_format((float) $r->price_difference, 2);
                    }
                    return '—';
                })
                ->addColumn('action', function ($r) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-xs btn-info dropdown-toggle" data-toggle="dropdown">Acciones <span class="caret"></span></button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                            <li><a href="' . route('warranty-claims.show', $r->id) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> Ver</a></li>
                            <li><a href="' . route('warranty-claims.print', $r->id) . '" target="_blank"><i class="fa fa-print"></i> Imprimir ticket</a></li>';
                    if ($r->status !== 'cancelled') {
                        $html .= '<li><a href="#" class="cancel-warranty" data-href="' . route('warranty-claims.cancel', $r->id) . '"><i class="fa fa-times"></i> Cancelar</a></li>';
                    }
                    $html .= '</ul></div>';
                    return $html;
                })
                ->rawColumns(['claim_type', 'status', 'action'])
                ->make(true);
        }

        $locations = BusinessLocation::forDropdown($business_id);
        return view('warranty_claim.index', compact('locations'));
    }

    public function create()
    {
        if (!$this->canUse()) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($business_id);

        // Terminales para pagos con tarjeta
        $card_terminals = \App\CardTerminal::forDropdown($business_id);

        return view('warranty_claim.create', compact('locations', 'card_terminals'));
    }

    /**
     * Devuelve las líneas de una venta (para mostrar en el dropdown "elige el equipo con problema").
     */
    public function getSellProducts(Request $request)
    {
        if (!$this->canUse()) {
            abort(403);
        }
        $business_id = $request->session()->get('user.business_id');
        $invoice = trim((string) $request->get('invoice_no'));

        $sale = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where('invoice_no', $invoice)
            ->with([
                'contact:id,name,mobile',
                'sell_lines.product:id,name',
                'sell_lines.variations:id,name,sub_sku,product_id',
            ])
            ->first();

        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'No se encontró una venta finalizada con ese folio.']);
        }

        $lines = [];
        foreach ($sale->sell_lines as $line) {
            if (empty($line->variation_id)) continue;
            $lines[] = [
                'variation_id' => $line->variation_id,
                'product_name' => $line->product->name ?? '—',
                'sub_sku' => $line->variations->sub_sku ?? '',
                'quantity' => (float) $line->quantity,
                'unit_price_inc_tax' => (float) $line->unit_price_inc_tax,
            ];
        }

        return response()->json([
            'success' => true,
            'sale_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'transaction_date' => Carbon::parse($sale->transaction_date)->format('d/m/Y'),
            'customer' => [
                'id' => $sale->contact->id ?? null,
                'name' => $sale->contact->name ?? '—',
                'mobile' => $sale->contact->mobile ?? '',
            ],
            'lines' => $lines,
        ]);
    }

    /**
     * Búsqueda de productos para el reemplazo (variaciones con stock en la sucursal dada).
     */
    public function searchReplacementProduct(Request $request)
    {
        if (!$this->canUse()) {
            abort(403);
        }
        $business_id = $request->session()->get('user.business_id');
        $term = trim((string) $request->get('q'));
        $location_id = $request->get('location_id');

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $q = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
            ->leftJoin('variation_location_details as vld', function ($j) use ($location_id) {
                $j->on('vld.variation_id', '=', 'variations.id');
                if ($location_id) {
                    $j->where('vld.location_id', '=', $location_id);
                }
            })
            ->where('p.business_id', $business_id)
            ->where(function ($w) use ($term) {
                $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($tokens as $t) {
                    $w->where(function ($x) use ($t) {
                        $x->where('p.name', 'like', '%' . $t . '%')
                            ->orWhere('p.sku', 'like', '%' . $t . '%')
                            ->orWhere('variations.sub_sku', 'like', '%' . $t . '%');
                    });
                }
            })
            ->select(
                'variations.id as variation_id',
                'p.name as product_name',
                'variations.sub_sku',
                'variations.sell_price_inc_tax',
                DB::raw('COALESCE(vld.qty_available, 0) as qty_available')
            )
            ->orderBy('qty_available', 'desc')
            ->limit(50)
            ->get();

        return response()->json($q);
    }

    public function store(Request $request)
    {
        if (!$this->canUse()) {
            abort(403);
        }

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');

        // Nuevo contrato: acepta un array de variation_ids (multi-artículo).
        // Backward-compatible: si el form envía `original_variation_id` (single),
        // lo convertimos a array de 1 elemento.
        $request->validate([
            'location_id' => 'required|integer',
            'original_sell_transaction_id' => 'required|integer',
            'original_variation_ids' => 'sometimes|array|min:1',
            'original_variation_ids.*' => 'integer',
            'original_variation_id' => 'sometimes|integer',
            'motivo' => 'required|string|min:5',
            'claim_type' => 'required|in:refund,replacement_same,replacement_higher,replacement_lower',
        ]);

        $type = $request->input('claim_type');
        $location_id = (int) $request->input('location_id');
        $original_tx_id = (int) $request->input('original_sell_transaction_id');
        $motivo = (string) $request->input('motivo');

        // Resolver lista de variation_ids: array nuevo o single legacy
        $var_ids = $request->input('original_variation_ids');
        if (empty($var_ids)) {
            $legacy_single = $request->input('original_variation_id');
            if ($legacy_single) {
                $var_ids = [(int) $legacy_single];
            }
        }
        $var_ids = collect($var_ids ?? [])->map(fn($v) => (int) $v)->filter()->unique()->values()->all();

        if (empty($var_ids)) {
            return $this->err('Selecciona al menos un artículo con problema.');
        }

        // Los tipos de Cambio requieren un solo artículo (semánticamente no tiene
        // sentido cambiar un teléfono Y un accesorio por UN mismo reemplazo).
        if (in_array($type, ['replacement_same', 'replacement_higher', 'replacement_lower']) && count($var_ids) !== 1) {
            return $this->err('Para Cambio (mismo/mayor/menor valor) selecciona exactamente un artículo. Si necesitas cambiar varios, crea una garantía por cada uno.');
        }

        $original_tx = Transaction::where('business_id', $business_id)->findOrFail($original_tx_id);

        // Cargar TODAS las líneas seleccionadas de la venta en una sola query.
        $original_lines_by_var = TransactionSellLine::where('transaction_id', $original_tx_id)
            ->whereIn('variation_id', $var_ids)
            ->get()
            ->keyBy('variation_id');

        if ($original_lines_by_var->count() !== count($var_ids)) {
            return $this->err('Algunos artículos seleccionados no pertenecen a esta venta.');
        }

        // Suma de valor total de los artículos seleccionados (para distribución proporcional del reembolso).
        $total_orig_value = 0.0;
        foreach ($original_lines_by_var as $line) {
            $total_orig_value += (float) $line->unit_price_inc_tax * (float) ($line->quantity ?? 1);
        }
        if ($total_orig_value <= 0) {
            // Fallback: reparto igualitario si no tenemos precios (raro)
            $total_orig_value = count($var_ids);
        }

        // Datos del reemplazo (si aplica)
        $replacement_var_id = null;
        $replacement_product_name = null;
        $replacement_price = 0.0;
        if (in_array($type, ['replacement_same', 'replacement_higher', 'replacement_lower'])) {
            $replacement_var_id = (int) $request->input('replacement_variation_id');
            if (!$replacement_var_id) {
                return $this->err('Selecciona el equipo de reemplazo.');
            }
            $var = Variation::with('product')->find($replacement_var_id);
            if (!$var) {
                return $this->err('El equipo de reemplazo no existe.');
            }
            $replacement_product_name = $var->product->name ?? '—';
            $replacement_price = (float) $var->sell_price_inc_tax;

            $avail = (float) (VariationLocationDetails::where('variation_id', $replacement_var_id)
                ->where('location_id', $location_id)->value('qty_available') ?? 0);
            if ($avail < 1) {
                return $this->err('No hay stock del equipo de reemplazo en esta sucursal (' . $avail . ').');
            }
        }

        // Cálculo de diferencia / reembolso según tipo
        $refund_amount = null;
        $price_difference = null;

        if ($type === 'refund') {
            $refund_amount = (float) $request->input('refund_amount');
            if ($refund_amount <= 0) {
                return $this->err('El monto del reembolso debe ser mayor a 0.');
            }
        } elseif ($type === 'replacement_higher') {
            $price_difference = (float) $request->input('price_difference');
            if ($price_difference <= 0) {
                return $this->err('La diferencia (mayor valor) debe ser positiva; el cliente paga.');
            }
        } elseif ($type === 'replacement_lower') {
            $price_difference = -1 * abs((float) $request->input('price_difference'));
            if ($price_difference >= 0) {
                return $this->err('La diferencia (menor valor) debe ser negativa; el negocio devuelve.');
            }
        }

        // Validaciones por método de pago (terminal para tarjeta, desglose para cash)
        $err = $this->validateMethodFields($request, $type, $business_id);
        if ($err) return $this->err($err);

        DB::beginTransaction();
        try {
            // Precomputamos ref_nos para todas las líneas (una GAR por línea).
            $created_claim_ids = [];
            $first_ref_no = null;

            // Nombres para el "note" del expense/income compartido
            $items_summary = $original_lines_by_var->map(function ($line) {
                return Product::where('id', $line->product_id)->value('name') ?? '—';
            })->take(3)->implode(', ') . ($original_lines_by_var->count() > 3 ? '…' : '');

            // 1) Reduce stock del reemplazo (una sola vez, aplica solo en single-item por regla).
            if ($replacement_var_id) {
                $this->productUtil->decreaseProductQuantity(
                    Variation::find($replacement_var_id)->product_id,
                    $replacement_var_id,
                    $location_id,
                    1
                );
            }

            // 2) Crea UNA sola transacción de expense (o income) compartida entre todos
            //    los claims del batch — así en /expenses y en el corte aparece un solo
            //    "Reembolso por Garantía" por el total, no N entradas.
            $shared_expense_tx_id = null;
            $shared_payment_tx_id = null;
            $shared_expense_ref = 'GAR-BATCH-' . now()->format('YmdHis');

            if ($type === 'refund' && $refund_amount > 0) {
                $exp_tx = $this->createWarrantyExpense(
                    $business_id, $location_id, $user_id,
                    (float) $refund_amount,
                    $request->input('refund_method', 'cash'),
                    $this->parseBd($request->input('refund_denomination_breakdown')),
                    $request->input('refund_card_terminal_id'),
                    $shared_expense_ref, 0,
                    $motivo . ' | Artículos: ' . $items_summary
                );
                $shared_expense_tx_id = $exp_tx->id;
            } elseif ($type === 'replacement_lower' && $price_difference < 0) {
                $exp_tx = $this->createWarrantyExpense(
                    $business_id, $location_id, $user_id,
                    abs((float) $price_difference),
                    $request->input('price_difference_method', 'cash'),
                    $this->parseBd($request->input('price_difference_denomination_breakdown')),
                    $request->input('price_difference_card_terminal_id'),
                    $shared_expense_ref, 0,
                    $motivo . ' | Diferencia'
                );
                $shared_expense_tx_id = $exp_tx->id;
            } elseif ($type === 'replacement_higher' && $price_difference > 0) {
                $pay_tx = $this->createWarrantyIncome(
                    $business_id, $location_id, $user_id,
                    (float) $price_difference,
                    $request->input('price_difference_method', 'cash'),
                    $this->parseBd($request->input('price_difference_denomination_breakdown')),
                    $request->input('price_difference_card_terminal_id'),
                    $shared_expense_ref, 0, $original_tx->contact_id
                );
                $shared_payment_tx_id = $pay_tx->id;
            }

            // 3) Loop: crea un warranty_claim por cada artículo seleccionado.
            //    Cada uno con su propio ref_no, penalización al técnico y monto
            //    proporcional del reembolso (para refund multi-item).
            foreach ($var_ids as $var_id) {
                $orig_line = $original_lines_by_var->get($var_id);
                $ref_no = $this->nextRefNo($business_id);
                if ($first_ref_no === null) $first_ref_no = $ref_no;

                $line_product_name = Product::where('id', $orig_line->product_id)->value('name') ?? 'Producto';

                // Penalización al técnico (por línea, no global)
                $line_tech_id = null;
                $line_tech_penalty = null;
                if (!empty($orig_line->technician_id) && \Schema::hasColumn('warranty_claims', 'original_technician_id')) {
                    $line_tech_id = (int) $orig_line->technician_id;
                    if (!is_null($orig_line->technician_commission_override)) {
                        $line_tech_penalty = (float) $orig_line->technician_commission_override;
                    } else {
                        $line_tech_penalty = (float) (\DB::table('repair_product_commissions')
                            ->where('business_id', $business_id)
                            ->where('product_id', $orig_line->product_id)
                            ->value('commission_amount') ?? 0);
                    }
                }

                // Reparto proporcional del reembolso (solo para refund)
                $line_refund = null;
                if ($type === 'refund' && $refund_amount > 0) {
                    $line_value = (float) $orig_line->unit_price_inc_tax * (float) ($orig_line->quantity ?? 1);
                    $line_refund = round($refund_amount * ($line_value / $total_orig_value), 2);
                }

                $claim_data = [
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'ref_no' => $ref_no,
                    'claim_date' => Carbon::now(),
                    'created_by' => $user_id,
                    'contact_id' => $original_tx->contact_id,
                    'original_sell_transaction_id' => $original_tx_id,
                    'original_variation_id' => $var_id,
                    'original_product_name' => $line_product_name,
                    'claim_type' => $type,
                    'motivo' => $motivo,
                    'replacement_variation_id' => $replacement_var_id,
                    'replacement_product_name' => $replacement_product_name,
                    'refund_amount' => $line_refund,
                    'refund_method' => $type === 'refund' ? $request->input('refund_method', 'cash') : null,
                    'refund_card_terminal_id' => $type === 'refund' ? $request->input('refund_card_terminal_id') : null,
                    'refund_denomination_breakdown' => $type === 'refund' ? $this->parseBd($request->input('refund_denomination_breakdown')) : null,
                    'price_difference' => $price_difference,
                    'price_difference_method' => in_array($type, ['replacement_higher', 'replacement_lower']) ? $request->input('price_difference_method', 'cash') : null,
                    'price_difference_card_terminal_id' => in_array($type, ['replacement_higher', 'replacement_lower']) ? $request->input('price_difference_card_terminal_id') : null,
                    'price_difference_denomination_breakdown' => in_array($type, ['replacement_higher', 'replacement_lower']) ? $this->parseBd($request->input('price_difference_denomination_breakdown')) : null,
                    'status' => 'completed',
                    'expense_transaction_id' => $shared_expense_tx_id,
                    'payment_transaction_id' => $shared_payment_tx_id,
                ];
                if (\Schema::hasColumn('warranty_claims', 'original_technician_id')) {
                    $claim_data['original_technician_id'] = $line_tech_id;
                    $claim_data['technician_commission_penalty'] = $line_tech_penalty;
                }
                $claim = WarrantyClaim::create($claim_data);
                $created_claim_ids[] = $claim->id;
            }

            DB::commit();
            $count = count($created_claim_ids);
            return response()->json([
                'success' => true,
                'message' => $count > 1
                    ? "Se registraron {$count} garantías (folios desde {$first_ref_no})."
                    : "Garantía registrada correctamente: {$first_ref_no}",
                'id' => $created_claim_ids[0],
                'ids' => $created_claim_ids,
                'print_url' => route('warranty-claims.print', $created_claim_ids[0]),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('WarrantyClaim store: ' . $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crea la transacción de gasto (Reembolso por Garantía) + su TransactionPayment
     * con desglose/terminal. Aparece en el corte diario y en /expenses.
     */
    private function createWarrantyExpense($business_id, $location_id, $user_id, $amount, $method, $denom_bd, $terminal_id, $ref_no, $claim_id, $motivo)
    {
        // Categoría "Reembolso por Garantía" — se crea la primera vez y se reutiliza.
        $cat = ExpenseCategory::firstOrCreate(
            ['business_id' => $business_id, 'name' => 'Reembolso por Garantía'],
            ['code' => 'GAR', 'description' => 'Gastos generados por reembolsos de garantía']
        );

        $expense_ref_count = $this->transactionUtil->setAndGetReferenceCount('expense', $business_id);
        $expense_ref_no = $this->transactionUtil->generateReferenceNumber('expense', $expense_ref_count, $business_id);

        $tx = Transaction::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'expense',
            'status' => 'final',
            'ref_no' => $expense_ref_no,
            'transaction_date' => Carbon::now(),
            'expense_category_id' => $cat->id,
            'final_total' => $amount,
            'total_before_tax' => $amount,
            'created_by' => $user_id,
            'payment_status' => 'paid',
            'additional_notes' => 'Garantía ' . $ref_no . ' — ' . mb_substr($motivo, 0, 200),
        ]);

        $pay_ref_count = $this->transactionUtil->setAndGetReferenceCount('sell_payment', $business_id);
        $pay_ref_no = $this->transactionUtil->generateReferenceNumber('sell_payment', $pay_ref_count, $business_id);

        TransactionPayment::create([
            'transaction_id' => $tx->id,
            'business_id' => $business_id,
            'is_return' => 0,
            'amount' => $amount,
            'method' => $method,
            'paid_on' => Carbon::now(),
            'created_by' => $user_id,
            'payment_ref_no' => $pay_ref_no,
            'card_terminal_id' => $method === 'card' ? $terminal_id : null,
            'denomination_breakdown' => $method === 'cash' && !empty($denom_bd) ? json_encode($denom_bd) : null,
            'note' => 'Reembolso por garantía ' . $ref_no,
        ]);

        return $tx;
    }

    /**
     * Crea la transacción tipo 'sell' con is_warranty_exchange=1 para registrar la
     * diferencia positiva que el cliente pagó por un equipo de mayor valor. Cuenta
     * como ingreso en el corte pero se excluye de "equipos vendidos" y de comisiones.
     */
    private function createWarrantyIncome($business_id, $location_id, $user_id, $amount, $method, $denom_bd, $terminal_id, $ref_no, $claim_id, $contact_id)
    {
        $sell_ref_count = $this->transactionUtil->setAndGetReferenceCount('sell', $business_id);
        $invoice_no = $this->transactionUtil->generateReferenceNumber('sell', $sell_ref_count, $business_id);

        $tx = Transaction::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'contact_id' => $contact_id,
            'type' => 'sell',
            'is_warranty_exchange' => 1,
            'status' => 'final',
            'invoice_no' => $invoice_no,
            'ref_no' => 'GAR-DIFF-' . $ref_no,
            'transaction_date' => Carbon::now(),
            'final_total' => $amount,
            'total_before_tax' => $amount,
            'created_by' => $user_id,
            'payment_status' => 'paid',
            'additional_notes' => 'Diferencia por garantía ' . $ref_no,
        ]);

        $pay_ref_count = $this->transactionUtil->setAndGetReferenceCount('sell_payment', $business_id);
        $pay_ref_no = $this->transactionUtil->generateReferenceNumber('sell_payment', $pay_ref_count, $business_id);

        TransactionPayment::create([
            'transaction_id' => $tx->id,
            'business_id' => $business_id,
            'is_return' => 0,
            'amount' => $amount,
            'method' => $method,
            'paid_on' => Carbon::now(),
            'created_by' => $user_id,
            'payment_for' => $contact_id,
            'payment_ref_no' => $pay_ref_no,
            'card_terminal_id' => $method === 'card' ? $terminal_id : null,
            'denomination_breakdown' => $method === 'cash' && !empty($denom_bd) ? json_encode($denom_bd) : null,
            'note' => 'Diferencia por garantía ' . $ref_no,
        ]);

        return $tx;
    }

    private function validateMethodFields(Request $request, $type, $business_id)
    {
        $has_terminals = \App\CardTerminal::where('business_id', $business_id)
            ->where('is_active', 1)->exists();

        // Refund: si es card → terminal obligatoria; si es cash → desglose obligatorio.
        if ($type === 'refund') {
            $m = $request->input('refund_method', 'cash');
            if ($m === 'card' && $has_terminals && empty($request->input('refund_card_terminal_id'))) {
                return 'Debes seleccionar una terminal para el reembolso con tarjeta.';
            }
            if ($m === 'cash') {
                $bd = $this->parseBd($request->input('refund_denomination_breakdown'));
                if (empty($bd)) {
                    return 'Debes capturar el desglose de billetes para el reembolso en efectivo.';
                }
                $refund_amount = (float) $request->input('refund_amount');
                [$ok, $msg] = \App\Utils\TransactionUtil::checkDenominationMatchesAmount($bd, $refund_amount);
                if (!$ok) return $msg;
            }
        }
        if (in_array($type, ['replacement_higher', 'replacement_lower'])) {
            $m = $request->input('price_difference_method', 'cash');
            if ($m === 'card' && $has_terminals && empty($request->input('price_difference_card_terminal_id'))) {
                return 'Debes seleccionar una terminal para el pago/reembolso de la diferencia con tarjeta.';
            }
            if ($m === 'cash') {
                $bd = $this->parseBd($request->input('price_difference_denomination_breakdown'));
                if (empty($bd)) {
                    return 'Debes capturar el desglose de billetes para la diferencia en efectivo.';
                }
                $diff = abs((float) $request->input('price_difference'));
                [$ok, $msg] = \App\Utils\TransactionUtil::checkDenominationMatchesAmount($bd, $diff);
                if (!$ok) return $msg;
            }
        }
        return null;
    }

    private function parseBd($raw)
    {
        if (empty($raw)) return null;
        if (is_array($raw)) return $raw;
        $parsed = json_decode((string) $raw, true);
        return is_array($parsed) && !empty($parsed) ? $parsed : null;
    }

    private function nextRefNo($business_id)
    {
        $year = date('Y');
        $prefix = 'GAR' . $year . '/';
        $last = WarrantyClaim::where('business_id', $business_id)
            ->where('ref_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')->value('ref_no');
        $next = 1;
        if ($last) {
            $parts = explode('/', $last);
            $next = (int) end($parts) + 1;
        }
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function err($msg)
    {
        return response()->json(['success' => false, 'message' => $msg], 422);
    }

    public function show($id)
    {
        if (!$this->canUse()) {
            abort(403);
        }
        $business_id = request()->session()->get('user.business_id');
        $claim = WarrantyClaim::where('business_id', $business_id)
            ->with(['contact', 'location', 'createdBy', 'originalSell', 'expenseTransaction', 'paymentTransaction'])
            ->findOrFail($id);
        return view('warranty_claim.show', compact('claim'));
    }

    public function printTicket($id)
    {
        if (!$this->canUse()) {
            abort(403);
        }
        $business_id = request()->session()->get('user.business_id');
        $claim = WarrantyClaim::where('business_id', $business_id)
            ->with(['contact', 'location', 'createdBy', 'originalSell'])
            ->findOrFail($id);
        $business = \App\Business::find($business_id);
        // IMEIs: los equipos tienen su IMEI guardado como sub_sku de la variation.
        // El original es el defectuoso que entrega el cliente; el replacement es el
        // nuevo que sale del stock. Se muestran en el ticket bajo cada equipo.
        $original_imei = null;
        if ($claim->original_variation_id) {
            $original_imei = Variation::where('id', $claim->original_variation_id)->value('sub_sku');
        }
        $replacement_imei = null;
        if ($claim->replacement_variation_id) {
            $replacement_imei = Variation::where('id', $claim->replacement_variation_id)->value('sub_sku');
        }
        return view('warranty_claim.ticket', compact('claim', 'business', 'original_imei', 'replacement_imei'));
    }

    public function cancel($id)
    {
        if (!$this->canUse()) {
            abort(403);
        }
        $business_id = request()->session()->get('user.business_id');
        $claim = WarrantyClaim::where('business_id', $business_id)->findOrFail($id);
        if ($claim->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Ya está cancelada.']);
        }

        DB::beginTransaction();
        try {
            // Revierte inventario del reemplazo (si aplica)
            if ($claim->replacement_variation_id) {
                $var = Variation::find($claim->replacement_variation_id);
                if ($var) {
                    $this->productUtil->updateProductQuantity(
                        $claim->location_id, $var->product_id, $claim->replacement_variation_id,
                        1, 0, null, false
                    );
                }
            }
            // Marca transacciones asociadas como canceladas
            if ($claim->expense_transaction_id) {
                Transaction::where('id', $claim->expense_transaction_id)->update(['status' => 'cancelled']);
            }
            if ($claim->payment_transaction_id) {
                Transaction::where('id', $claim->payment_transaction_id)->update(['status' => 'cancelled']);
            }
            $claim->status = 'cancelled';
            $claim->save();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Garantía cancelada.']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('WarrantyClaim cancel: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        return $this->cancel($id);
    }
}
