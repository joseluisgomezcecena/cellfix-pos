<?php

namespace Modules\Layaway\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Layaway\Entities\Layaway;
use Modules\Layaway\Entities\LayawayItem;
use Modules\Layaway\Entities\LayawayPayment;
use App\Contact;
use App\Product;
use App\Variation;
use App\BusinessLocation;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use DB;
use Yajra\DataTables\Facades\DataTables;

class LayawayController extends Controller
{
    protected $productUtil;
    protected $transactionUtil;
    protected $businessUtil;

    /**
     * Constructor
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Reporte de equipos apartados (reservados en apartados activos) por sucursal, con IMEI.
     */
    public function reservedEquiposReport(Request $request)
    {
        if (! auth()->user()->can('layaway.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');

        $equipos_brand = DB::table('brands')->where('business_id', $business_id)
            ->whereRaw("LOWER(name) = 'equipos'")->value('id');

        $query = DB::table('layaway_items as li')
            ->join('layaways as l', 'l.id', '=', 'li.layaway_id')
            ->join('products as p', 'p.id', '=', 'li.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'li.variation_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'l.contact_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'l.business_location_id')
            // El apartador es el created_by de la transacción del apartado (no del layaway)
            ->leftJoin('transactions as t', 't.layaway_id', '=', 'l.id')
            ->leftJoin('users as u', 'u.id', '=', 't.created_by')
            ->where('l.business_id', $business_id)
            ->whereIn('l.status', ['pending', 'active']);

        if (! empty($equipos_brand)) {
            $query->where('p.brand_id', $equipos_brand);
        }
        if (! empty($location_id)) {
            $query->where('l.business_location_id', $location_id);
        }

        $rows = $query->select([
            'l.id as layaway_id',
            'bl.name as location_name',
            'p.name as product_name',
            DB::raw('COALESCE(v.sub_sku, p.sku) as imei'),
            'c.name as customer',
            'li.quantity',
            'l.layaway_number',
            'l.total_amount',
            'l.down_payment_amount',
            'l.balance_due',
            'l.payment_deadline',
            'l.status',
            'l.created_at as apartado_at',
            DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as apartador"),
            // Total pagado: suma de todos los abonos hasta la fecha
            DB::raw('(SELECT COALESCE(SUM(lp.amount),0) FROM layaway_payments lp WHERE lp.layaway_id = l.id) as total_paid'),
            DB::raw('DATEDIFF(NOW(), l.created_at) as dias_transcurridos'),
        ])->orderBy('bl.name')->orderBy('l.created_at', 'desc')->get();

        $by_location = [];
        $totals = ['n_equipos' => 0, 'total_valor' => 0, 'total_pagado' => 0, 'total_por_cobrar' => 0];
        foreach ($rows as $r) {
            $by_location[$r->location_name ?: 'Sin sucursal'][] = $r;
            $totals['n_equipos'] += (float) $r->quantity;
            $totals['total_valor'] += (float) $r->total_amount;
            $totals['total_pagado'] += (float) $r->total_paid;
            $totals['total_por_cobrar'] += (float) $r->balance_due;
        }

        $locations = BusinessLocation::forDropdown($business_id);

        return view('layaway::reserved_equipos', compact('by_location', 'totals', 'locations', 'location_id'));
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('layaway.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $layaways = Layaway::where('business_id', $business_id)
                ->with(['contact', 'location', 'createdBy']);

            if ($request->has('status')) {
                if ($request->status == 'overdue') {
                    $layaways->overdue();
                } else {
                    $layaways->where('status', $request->status);
                }
            }

            if ($request->has('location_id')) {
                $layaways->where('business_location_id', $request->location_id);
            }

            if ($request->has('contact_id')) {
                $layaways->where('contact_id', $request->contact_id);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $start = $request->start_date;
                $end = $request->end_date;
                $layaways->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end);
            }

            return DataTables::of($layaways)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                            data-toggle="dropdown" aria-expanded="false">
                            ' . __("messages.action") . '
                            <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">';

                    if (auth()->user()->can('layaway.view')) {
                        $html .= '<li><a href="' . route('layaway.show', [$row->id]) . '">
                            <i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                    }

                    if (auth()->user()->can('layaway.update') && in_array($row->status, ['pending', 'active'])) {
                        $html .= '<li><a href="' . route('layaway.edit', [$row->id]) . '">
                            <i class="fa fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                    }

                    if (auth()->user()->can('layaway.process_payment') && $row->balance_due > 0) {
                        $html .= '<li><a href="#" class="make-payment" data-href="' . route('layaway.payments.create', [$row->id]) . '">
                            <i class="fa fa-money"></i> ' . __("layaway::lang.make_payment") . '</a></li>';
                    }

                    if ($row->status == 'active') {
                        $html .= '<li><a href="' . route('layaway.print', [$row->id]) . '" target="_blank">
                            <i class="fa fa-print"></i> ' . __("layaway::lang.print_receipt") . '</a></li>';
                    }

                    if (auth()->user()->can('layaway.delete') && in_array($row->status, ['pending', 'cancelled'])) {
                        $html .= '<li><a href="#" class="delete-layaway" data-href="' . route('layaway.destroy', [$row->id]) . '">
                            <i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                    }

                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('layaway_number', function ($row) {
                    return '<a href="' . route('layaway.show', [$row->id]) . '">' . $row->layaway_number . '</a>';
                })
                ->editColumn('contact', function ($row) {
                    return $row->contact ? $row->contact->name : '';
                })
                ->editColumn('total_amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->total_amount . '</span>';
                })
                ->editColumn('balance_due', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->balance_due . '</span>';
                })
                ->editColumn('status', function ($row) {
                    $status_colors = [
                        'pending' => 'bg-yellow',
                        'active' => 'bg-blue',
                        'completed' => 'bg-green',
                        'cancelled' => 'bg-red',
                        'expired' => 'bg-gray'
                    ];
                    $color = isset($status_colors[$row->status]) ? $status_colors[$row->status] : 'bg-gray';
                    return '<span class="label ' . $color . '">' . __('layaway::lang.status_' . $row->status) . '</span>';
                })
                ->editColumn('payment_deadline', function ($row) {
                    if ($row->isOverdue()) {
                        return '<span class="text-danger">' . \Carbon\Carbon::parse($row->payment_deadline)->format('d/m/Y') . ' <i class="fa fa-exclamation-triangle"></i></span>';
                    }
                    return \Carbon\Carbon::parse($row->payment_deadline)->format('d/m/Y');
                })
                ->rawColumns(['action', 'layaway_number', 'total_amount', 'balance_due', 'status', 'payment_deadline'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);

        return view('layaway::index', compact('business_locations', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if (!auth()->user()->can('layaway.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        // Ya no cargamos ~40k contactos aquí: el select ahora es AJAX contra
        // /contacts/customers y arranca vacío. Antes esto congelaba el navegador.
        $customers = [];
        $default_location = null;

        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = $id;
            }
        }

        $payment_line = $this->dummyPaymentLine;

        return view('layaway::create', compact('business_locations', 'customers', 'default_location', 'payment_line'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('layaway.create')) {
            abort(403, 'Unauthorized action.');
        }

        $date_format = session('business.date_format', config('constants.default_date_format'));

        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'business_location_id' => 'required|exists:business_locations,id',
            'payment_deadline' => 'required|date_format:' . $date_format . '|after:today',
            'down_payment_amount' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variation_id' => 'nullable|exists:variations,id',
            'products.*.quantity' => 'required|numeric|min:0.01',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $total_amount = 0;
            $layaway_items = [];

            foreach ($request->products as $product) {
                $product_model = Product::find($product['product_id']);
                if (!$product_model) {
                    throw new \Exception('Product not found for ID: ' . $product['product_id']);
                }

                $variation_id = $product['variation_id'] ?? null;
                $variation = null;

                // Get the default variation if none specified
                if (empty($variation_id)) {
                    $product_variation = $product_model->product_variations()->first();
                    if ($product_variation) {
                        $variation = $product_variation->variations()->first();
                        if ($variation) {
                            $variation_id = $variation->id;
                        } else {
                            throw new \Exception('No variations found for product: ' . $product_model->name);
                        }
                    } else {
                        throw new \Exception('No product variations found for product: ' . $product_model->name);
                    }
                } else {
                    $variation = Variation::find($variation_id);
                    if (!$variation) {
                        throw new \Exception('Variation not found for ID: ' . $variation_id);
                    }
                }

                // Validate stock availability before processing
                if (!$variation_id) {
                    throw new \Exception('Variation ID is required for product: ' . $product_model->name);
                }

                // Leemos qty_available directo desde variation_location_details para EVITAR
                // un bug en ProductUtil::getDetailsFromVariation que no respeta location_id
                // cuando se llama con check_qty=false y la variación tiene VLD en varias
                // sucursales (MySQL devuelve una fila al azar y reporta qty=0 aunque haya stock).
                $qty_available = (float) (\DB::table('variation_location_details')
                    ->where('variation_id', $variation_id)
                    ->where('location_id', $request->business_location_id)
                    ->value('qty_available') ?? 0);

                if ($product['quantity'] > $qty_available) {
                    throw new \Exception('Insufficient stock for product: ' . $product_model->name . '. Available: ' . $qty_available . ', Requested: ' . $product['quantity']);
                }

                // Use the unit price from the form (this comes from the database via JavaScript)
                $unit_price = $product['unit_price'];
                $line_total = $product['quantity'] * $unit_price;
                $total_amount += $line_total;

                // Decrease product quantity (mark as sold/reserved for layaway)
                $this->productUtil->decreaseProductQuantity(
                    $product['product_id'],
                    $variation_id,
                    $request->business_location_id,
                    $product['quantity']
                );

                $layaway_items[] = [
                    'product_id' => $product['product_id'],
                    'variation_id' => $variation_id,
                    'quantity' => $product['quantity'],
                    'unit_price' => $unit_price,
                    'total_price' => $line_total
                ];
            }

            $down_payment_amount = min((float)$request->down_payment_amount, $total_amount);
            $down_payment_percentage = $total_amount > 0 ? ($down_payment_amount / $total_amount) * 100 : 0;
            $balance_due = $total_amount - $down_payment_amount;

            // Convert date from business format to Y-m-d for database storage
            $date_format = session('business.date_format', config('constants.default_date_format'));
            $payment_deadline = \Carbon\Carbon::createFromFormat($date_format, $request->payment_deadline)->format('Y-m-d');

            $layaway = Layaway::create([
                'business_id' => $business_id,
                'contact_id' => $request->contact_id,
                'business_location_id' => $request->business_location_id,
                'created_by' => $user_id,
                'layaway_number' => Layaway::generateLayawayNumber($business_id),
                'total_amount' => $total_amount,
                'down_payment_percentage' => $down_payment_percentage,
                'down_payment_amount' => $down_payment_amount,
                'balance_due' => $balance_due,
                'payment_deadline' => $payment_deadline,
                'status' => 'pending',
                'notes' => $request->notes
            ]);

            foreach ($layaway_items as $item) {
                $item['layaway_id'] = $layaway->id;
                LayawayItem::create($item);
            }

            // Create transaction for the layaway as an immediate sale
            $transaction_input = [
                'contact_id' => $request->contact_id,
                'location_id' => $request->business_location_id,
                'status' => 'final',
                'transaction_date' => now()->format('Y-m-d H:i:s'),
                'final_total' => $total_amount,
                'discount_amount' => 0,
                'discount_type' => null,
                'additional_notes' => 'Layaway Sale - ' . $layaway->layaway_number . ($request->notes ? ' - ' . $request->notes : ''),
                'shipping_charges' => 0,
                'is_direct_sale' => 0
            ];

            $invoice_total = [
                'total_before_tax' => $total_amount,
                'tax' => 0
            ];

            $transaction = $this->transactionUtil->createSellTransaction($business_id, $transaction_input, $invoice_total, $user_id, false);

            // Create transaction sell lines for each layaway item
            foreach ($layaway_items as $item) {
                \App\TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'],
                    'quantity' => $item['quantity'],
                    'unit_price_before_discount' => $item['unit_price'],
                    'unit_price' => $item['unit_price'],
                    'unit_price_inc_tax' => $item['unit_price'],
                    'line_discount_type' => null,
                    'line_discount_amount' => 0,
                    'item_tax' => 0,
                    'tax_id' => null,
                    'lot_no_line_id' => null
                ]);
            }

            // Link layaway to transaction
            $transaction->update(['layaway_id' => $layaway->id]);

            // Determine payment status based on down payment
            $payment_status = 'due';
            if ($down_payment_amount > 0) {
                $payment_status = ($down_payment_amount >= $total_amount) ? 'paid' : 'partial';
            }

            $transaction->update(['payment_status' => $payment_status]);

            // Create initial down payment if provided
            if ($down_payment_amount > 0) {
                // Generate reference number for layaway payment
                $ref_count = $this->transactionUtil->setAndGetReferenceCount('layaway_payment', $business_id);
                $payment_ref_no = $this->transactionUtil->generateReferenceNumber('layaway_payment', $ref_count, $business_id);

                // Create transaction payment for down payment
                $transaction_payment = \App\TransactionPayment::create([
                    'transaction_id' => $transaction->id,
                    'business_id' => $business_id,
                    'is_return' => 0,
                    'amount' => $down_payment_amount,
                    'method' => 'cash', // Default to cash, can be made configurable
                    'payment_ref_no' => $payment_ref_no,
                    'paid_on' => now(),
                    'created_by' => $user_id,
                    'payment_for' => $layaway->contact_id,
                    'note' => 'Layaway down payment'
                ]);

                $layaway_payment = \Modules\Layaway\Entities\LayawayPayment::create([
                    'layaway_id' => $layaway->id,
                    'amount' => $down_payment_amount,
                    'payment_method' => 'cash', // Default to cash, can be made configurable
                    'payment_date' => now(),
                    'processed_by' => $user_id,
                    'transaction_payment_id' => $transaction_payment->id,
                    'notes' => 'Initial down payment'
                ]);

                // Update balance and set status to active
                $layaway->updateBalance();
                $layaway->refresh();
                if ($layaway->balance_due <= 0) {
                    // Caso raro: el enganche cubrió el total al crear el apartado.
                    // Lo marcamos como completed y registramos completed_at para que el
                    // cut diario lo consolide en este día.
                    $layaway->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    if ($layaway->transaction) {
                        $layaway->transaction->update(['payment_status' => 'paid']);
                    }
                } else {
                    $layaway->update(['status' => 'active']);
                }
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('layaway::lang.layaway_created_successfully'),
                'layaway_id' => $layaway->id
            ];

            return redirect()->route('layaway.show', $layaway->id)
                ->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log detailed error information
            \Log::error("Layaway creation failed", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'business_id' => $business_id ?? 'N/A'
            ]);

            $output = [
                'success' => false,
                'msg' => 'Layaway creation failed: ' . $e->getMessage()
            ];

            return redirect()->route('layaway.create')
                ->with('status', $output)
                ->withInput();
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if (!auth()->user()->can('layaway.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $layaway = Layaway::where('business_id', $business_id)
            ->with(['contact', 'location', 'createdBy', 'items.product', 'items.variation', 'payments.processedBy'])
            ->findOrFail($id);

        $payment_methods = $this->transactionUtil->payment_types(null, true, $business_id);

        return view('layaway::show', compact('layaway', 'payment_methods'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if (!auth()->user()->can('layaway.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $layaway = Layaway::where('business_id', $business_id)
            ->with(['items'])
            ->findOrFail($id);

        if (!in_array($layaway->status, ['pending', 'active'])) {
            abort(403, 'Cannot edit this layaway.');
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);

        return view('layaway::edit', compact('layaway', 'business_locations', 'customers'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('layaway.update')) {
            abort(403, 'Unauthorized action.');
        }

        $date_format = session('business.date_format', config('constants.default_date_format'));

        $request->validate([
            'payment_deadline' => 'required|date_format:' . $date_format . '|after:today',
            'down_payment_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        try {
            $business_id = request()->session()->get('user.business_id');

            $layaway = Layaway::where('business_id', $business_id)->findOrFail($id);

            if (!in_array($layaway->status, ['pending', 'active'])) {
                throw new \Exception('Cannot edit this layaway.');
            }

            // Convert date format from business format to Y-m-d
            $date_format = session('business.date_format', config('constants.default_date_format'));
            $payment_deadline = \Carbon\Carbon::createFromFormat($date_format, $request->payment_deadline)->format('Y-m-d');

            // Recalculate amounts from the entered fixed amount
            $down_payment_amount = min((float)$request->down_payment_amount, $layaway->total_amount);
            $down_payment_percentage = $layaway->total_amount > 0 ? ($down_payment_amount / $layaway->total_amount) * 100 : 0;
            $balance_due = $layaway->total_amount - $down_payment_amount;

            $layaway->update([
                'payment_deadline' => $payment_deadline,
                'down_payment_percentage' => $down_payment_percentage,
                'down_payment_amount' => $down_payment_amount,
                'balance_due' => $balance_due,
                'notes' => $request->notes
            ]);

            $output = [
                'success' => true,
                'msg' => __('layaway::lang.layaway_updated_successfully')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->route('layaway.index')
            ->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('layaway.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $layaway = Layaway::where('business_id', $business_id)->findOrFail($id);

            if (!in_array($layaway->status, ['pending', 'cancelled'])) {
                throw new \Exception('Cannot delete this layaway.');
            }

            $layaway->delete();

            $output = [
                'success' => true,
                'msg' => __('layaway::lang.layaway_deleted_successfully')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        if (request()->ajax()) {
            return $output;
        }

        return redirect()->route('layaway.index')
            ->with('status', $output);
    }

    /**
     * Cancel a layaway
     * @param int $id
     * @return Response
     */
    public function cancel($id)
    {
        if (!auth()->user()->can('layaway.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');

            $layaway = Layaway::where('business_id', $business_id)->with('items')->findOrFail($id);

            if (!in_array($layaway->status, ['pending', 'active'])) {
                throw new \Exception('Cannot cancel this layaway.');
            }

            // Return products to stock
            $productUtil = new ProductUtil();
            foreach ($layaway->items as $item) {
                $productUtil->updateProductQuantity(
                    $layaway->business_location_id,
                    $item->product_id,
                    $item->variation_id,
                    $item->quantity,
                    0,
                    null,
                    false
                );
            }

            $layaway->update(['status' => 'cancelled']);

            // Update associated transaction status to cancelled
            if ($layaway->transaction) {
                $layaway->transaction->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled'
                ]);
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('layaway::lang.layaway_cancelled_successfully')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->route('layaway.index')
            ->with('status', $output);
    }

    /**
     * Activate a pending layaway after down payment
     * @param int $id
     * @return Response
     */
    public function activate($id)
    {
        if (!auth()->user()->can('layaway.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');

            $layaway = Layaway::where('business_id', $business_id)->findOrFail($id);

            if ($layaway->status != 'pending') {
                throw new \Exception('Layaway is not in pending status.');
            }

            $total_paid = $layaway->payments()->sum('amount');

            if ($total_paid < $layaway->down_payment_amount) {
                throw new \Exception('Down payment not completed.');
            }

            $layaway->update(['status' => 'active']);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('layaway::lang.layaway_activated_successfully')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage()
            ];
        }

        return redirect()->action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@show', $id)
            ->with('status', $output);
    }

    /**
     * Print receipt for layaway
     * @param int $id
     * @return Response
     */
    public function printReceipt($id)
    {
        if (!auth()->user()->can('layaway.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $layaway = Layaway::where('business_id', $business_id)
            ->with(['contact', 'location', 'createdBy', 'items.product', 'items.variation', 'payments.processedBy'])
            ->findOrFail($id);

        $business = \App\Business::find($business_id);

        return view('layaway::receipt', compact('layaway', 'business'));
    }

    /**
     * Get product row for layaway creation
     * @param Request $request
     * @return Response
     */
    public function getProductRow($row_index, Request $request)
    {
        // Temporarily removed permission check for testing
        // if (!auth()->user()->can('layaway.create')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = request()->session()->get('user.business_id');

        $products = Product::where('business_id', $business_id)
            ->active()
            ->with(['variations'])
            ->get()
            ->pluck('name', 'id');

        return view('layaway::partials.product_row', compact('products', 'row_index'));
    }

    /**
     * Get product details for Ajax
     * @param int $id
     * @return Response
     */
    public function getProductDetails($id)
    {
        // Temporarily removed permission check for testing
        // if (!auth()->user()->can('layaway.create')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = request()->session()->get('user.business_id');

        $product = Product::where('business_id', $business_id)
            ->with(['product_variations.variations'])
            ->findOrFail($id);

        $variations = $product->product_variations->first();
        $first_variation = $variations ? $variations->variations->first() : null;

        $data = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sell_price' => $first_variation ? $first_variation->sell_price_inc_tax : 0,
            'unit_name' => 'Piece' // Default unit, you may want to fetch from the unit table
        ];

        return response()->json($data);
    }

    /**
     * Get customer details for Ajax
     * @param int $id
     * @return Response
     */
    public function getCustomerDetails($id)
    {
        if (!auth()->user()->can('layaway.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $customer = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->findOrFail($id);

        return response()->json($customer);
    }

    /**
     * Calculate payment for Ajax
     * @param Request $request
     * @return Response
     */
    public function calculatePayment(Request $request)
    {
        if (!auth()->user()->can('layaway.create')) {
            abort(403, 'Unauthorized action.');
        }

        $total_amount = $request->get('total_amount', 0);
        $down_payment_percentage = $request->get('down_payment_percentage', 20);

        $down_payment_amount = ($total_amount * $down_payment_percentage) / 100;
        $balance_due = $total_amount - $down_payment_amount;

        $data = [
            'total_amount' => $total_amount,
            'down_payment_amount' => $down_payment_amount,
            'balance_due' => $balance_due
        ];

        return response()->json($data);
    }

    /**
     * Dummy property for payment line
     */
    protected $dummyPaymentLine = [
        'method' => 'cash',
        'amount' => 0,
        'note' => '',
        'card_transaction_number' => '',
        'card_number' => '',
        'card_type' => '',
        'card_holder_name' => '',
        'card_month' => '',
        'card_year' => '',
        'card_security' => '',
        'cheque_number' => '',
        'bank_account_number' => '',
        'is_return' => 0,
        'transaction_no' => ''
    ];
}