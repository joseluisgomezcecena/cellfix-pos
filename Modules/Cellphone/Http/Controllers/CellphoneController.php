<?php

namespace Modules\Cellphone\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\ProductVariation;
use App\PurchaseLine;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\Unit;
use App\Variation;
use App\VariationLocationDetails;
use App\Warranty;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Cellphone\Entities\Cellphone;
use Yajra\DataTables\Facades\DataTables;

class CellphoneController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('cellphone.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $cellphones = Cellphone::cellphones()
                ->where('business_id', $business_id)
                ->with(['brand', 'warranty', 'unit'])
                ->select('products.*');

            // Filter by active/inactive status (default: only active)
            $active_state = request()->get('active_state', 'active');
            if ($active_state == 'active') {
                $cellphones->where('products.is_inactive', 0);
            } elseif ($active_state == 'inactive') {
                $cellphones->where('products.is_inactive', 1);
            }
            // If 'all', don't filter by is_inactive

            // Apply filters
            $filters = request()->only(['marca', 'modelo', 'imei', 'estado', 'warranty_id', 'ubicacion']);
            if (!empty($filters)) {
                $cellphones->searchCellphones($filters);
            }

            return DataTables::of($cellphones)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">';
                    $html .= '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' . __('messages.actions') . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>';
                    $html .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">';

                    if (auth()->user()->can('cellphone.update')) {
                        $html .= '<li><a href="' . action('\Modules\Cellphone\Http\Controllers\CellphoneController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                    }

                    // Show Activate option for inactive products
                    if ($row->is_inactive == 1 && auth()->user()->can('cellphone.update')) {
                        $html .= '<li><a href="' . action('\Modules\Cellphone\Http\Controllers\CellphoneController@activate', [$row->id]) . '" class="activate_cellphone_button"><i class="fa fa-circle-o"></i> Activar</a></li>';
                    }

                    if (auth()->user()->can('cellphone.delete')) {
                        $html .= '<li><a data-href="' . action('\Modules\Cellphone\Http\Controllers\CellphoneController@destroy', [$row->id]) . '" class="delete_cellphone_button"><i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</a></li>';
                    }

                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('imei', function ($row) {
                    return $row->sku;
                })
                ->editColumn('marca', function ($row) {
                    return $row->marca;
                })
                ->editColumn('modelo', function ($row) {
                    return $row->modelo;
                })
                ->editColumn('ubicacion', function ($row) {
                    return $row->ubicacion;
                })
                ->editColumn('estado', function ($row) {
                    $estado_options = config('cellphone.estado_options');
                    return $estado_options[$row->estado] ?? $row->estado;
                })
                ->editColumn('warranty', function ($row) {
                    return $row->warranty ? $row->warranty->display_name : '-';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $warranties = Warranty::forDropdown($business_id);
        $estado_options = config('cellphone.estado_options');

        return view('cellphone::index', compact('warranties', 'estado_options'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        if (!auth()->user()->can('cellphone.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $brands = Brands::where('business_id', $business_id)->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)->pluck('actual_name', 'id');
        // Get only parent categories for dropdown
        $categories = Category::forDropdown($business_id, 'product');
        // Initialize empty subcategories array (will be populated via AJAX)
        $sub_categories = [];
        $tax_rates = TaxRate::where('business_id', $business_id)->pluck('name', 'id');
        $warranties = Warranty::forDropdown($business_id);
        $locations = BusinessLocation::forDropdown($business_id);

        $estado_options = config('cellphone.estado_options');
        $default_variations = config('cellphone.default_variations');

        return view('cellphone::create', compact(
            'brands',
            'units',
            'categories',
            'sub_categories',
            'tax_rates',
            'warranties',
            'locations',
            'estado_options',
            'default_variations'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('cellphone.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        try {
            // Validate request
            $request->validate([
                'imei' => 'required|string',
                'marca' => 'required|string',
                'modelo' => 'required|string',
                'name' => 'required|string',
                'unit_id' => 'required|integer',
            ]);

            // Validate IMEI format
            if (!Cellphone::validateImei($request->imei)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('cellphone::lang.imei_invalid')
                ]);
            }

            // Check IMEI uniqueness
            if (!Cellphone::isImeiUnique($request->imei)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('cellphone::lang.imei_duplicate')
                ]);
            }

            DB::beginTransaction();

            // Create cellphone (product)
            $cellphone = new Cellphone();
            $cellphone->name = $request->name;
            $cellphone->business_id = $business_id;
            $cellphone->type = config('cellphone.product_type');
            $cellphone->sku = $request->imei; // IMEI stored as SKU
            $cellphone->unit_id = $request->unit_id;
            $cellphone->brand_id = $request->brand_id ?? null;
            $cellphone->category_id = $request->category_id ?? null;
            $cellphone->sub_category_id = $request->sub_category_id ?? null;
            $cellphone->tax = $request->tax ?? null;
            $cellphone->tax_type = $request->tax_type ?? 'exclusive';
            $cellphone->warranty_id = $request->warranty_id ?? null;
            $cellphone->created_by = auth()->user()->id;
            $cellphone->barcode_type = $request->barcode_type ?? 'C128';

            // CRITICAL: Enable stock tracking for POS visibility
            // Without this, the product won't appear in POS even if stock exists
            $cellphone->enable_stock = 1;

            // Set cellphone-specific fields using accessors
            $cellphone->marca = $request->marca;
            $cellphone->modelo = $request->modelo;
            $cellphone->ubicacion = $request->ubicacion;
            $cellphone->estado = $request->estado ?? 'nuevo';
            $cellphone->observaciones = $request->observaciones;

            // IMPORTANT: Mark this product as a cellphone
            // This ensures it only appears in the Cellphone module
            $cellphone->markAsCellphone();

            $cellphone->save();

            // Create default variation (required for all products)
            $product_variation = ProductVariation::create([
                'product_id' => $cellphone->id,
                'name' => 'DUMMY',
                'is_dummy' => 1
            ]);

            // Calculate sell price with tax if needed
            $sell_price = $request->sell_price ?? 0;
            $sell_price_inc_tax = $sell_price;

            if ($request->tax && $request->tax_type == 'exclusive') {
                $tax_rate = TaxRate::find($request->tax);
                if ($tax_rate) {
                    $tax_percent = $tax_rate->amount;
                    $sell_price_inc_tax = $sell_price + ($sell_price * ($tax_percent / 100));
                }
            }

            // Create variation with pricing
            $variation = Variation::create([
                'product_id' => $cellphone->id,
                'product_variation_id' => $product_variation->id,
                'name' => 'DUMMY',
                'sub_sku' => $request->imei,
                'default_purchase_price' => $request->purchase_price ?? 0,
                'dpp_inc_tax' => $request->purchase_price ?? 0,
                'profit_percent' => 0,
                'default_sell_price' => $sell_price,
                'sell_price_inc_tax' => $sell_price_inc_tax,
            ]);

            // Create stock if enabled
            if ($request->enable_stock == '1' && $request->location_id) {
                VariationLocationDetails::create([
                    'variation_id' => $variation->id,
                    'product_id' => $cellphone->id,
                    'product_variation_id' => $product_variation->id,
                    'location_id' => $request->location_id,
                    'qty_available' => $request->opening_stock ?? 0,
                ]);

                // Create purchase_transfer for initial stock
                if (($request->opening_stock ?? 0) > 0) {
                    $this->createPurchaseTransferTransaction(
                        $cellphone->id,
                        $variation->id,
                        $request->location_id,
                        $request->opening_stock,
                        $variation->default_purchase_price
                    );
                }

                // CRITICAL: Link product to location in product_locations pivot table
                // Without this, the product won't appear in the POS for this location
                // even if stock exists in variation_location_details
                $cellphone->product_locations()->sync([$request->location_id]);
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('cellphone::lang.created_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('\Modules\Cellphone\Http\Controllers\CellphoneController@index')
            ->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        if (!auth()->user()->can('cellphone.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $cellphone = Cellphone::cellphones()
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->with(['brand', 'warranty', 'unit', 'category'])
            ->firstOrFail();

        return view('cellphone::show', compact('cellphone'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        if (!auth()->user()->can('cellphone.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $cellphone = Cellphone::cellphones()
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->firstOrFail();

        $brands = Brands::where('business_id', $business_id)->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)->pluck('actual_name', 'id');
        // Get only parent categories for dropdown
        $categories = Category::forDropdown($business_id, 'product');
        // Get subcategories for the current cellphone's category
        $sub_categories = [];
        if (!empty($cellphone->category_id)) {
            $sub_categories = Category::where('business_id', $business_id)
                ->where('parent_id', $cellphone->category_id)
                ->pluck('name', 'id')
                ->toArray();
        }
        $tax_rates = TaxRate::where('business_id', $business_id)->pluck('name', 'id');
        $warranties = Warranty::forDropdown($business_id);
        $locations = BusinessLocation::forDropdown($business_id);

        $estado_options = config('cellphone.estado_options');
        $default_variations = config('cellphone.default_variations');

        // Get stock details for this cellphone
        $stock_details = DB::table('variations as v')
            ->join('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
            ->join('business_locations as bl', 'vld.location_id', '=', 'bl.id')
            ->where('v.product_id', $cellphone->id)
            ->select('bl.name as location_name', 'vld.qty_available', 'vld.location_id')
            ->get();

        return view('cellphone::edit', compact(
            'cellphone',
            'brands',
            'units',
            'categories',
            'sub_categories',
            'tax_rates',
            'warranties',
            'locations',
            'estado_options',
            'default_variations',
            'stock_details'
        ));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('cellphone.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        try {
            // Validate request
            $request->validate([
                'imei' => 'required|string',
                'marca' => 'required|string',
                'modelo' => 'required|string',
                'name' => 'required|string',
                'unit_id' => 'required|integer',
            ]);

            // Validate IMEI format
            if (!Cellphone::validateImei($request->imei)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('cellphone::lang.imei_invalid')
                ]);
            }

            // Check IMEI uniqueness (excluding current product)
            if (!Cellphone::isImeiUnique($request->imei, $id)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('cellphone::lang.imei_duplicate')
                ]);
            }

            DB::beginTransaction();

            $cellphone = Cellphone::cellphones()
                ->where('business_id', $business_id)
                ->where('id', $id)
                ->firstOrFail();

            $cellphone->name = $request->name;
            $cellphone->sku = $request->imei;
            $cellphone->unit_id = $request->unit_id;
            $cellphone->brand_id = $request->brand_id ?? null;
            $cellphone->category_id = $request->category_id ?? null;
            $cellphone->sub_category_id = $request->sub_category_id ?? null;
            $cellphone->tax = $request->tax ?? null;
            $cellphone->tax_type = $request->tax_type ?? 'exclusive';
            $cellphone->warranty_id = $request->warranty_id ?? null;

            // Update cellphone-specific fields
            $cellphone->marca = $request->marca;
            $cellphone->modelo = $request->modelo;
            $cellphone->ubicacion = $request->ubicacion;
            $cellphone->estado = $request->estado ?? 'nuevo';
            $cellphone->observaciones = $request->observaciones;

            // IMPORTANT: Ensure the cellphone flag is maintained
            // This ensures it continues to appear only in the Cellphone module
            $cellphone->markAsCellphone();

            // IMPORTANT: Ensure enable_stock flag is set
            // This is critical for POS visibility
            $cellphone->enable_stock = 1;

            $cellphone->save();

            // Ensure variation exists (for cellphones created before variation support was added)
            $product_variation = ProductVariation::where('product_id', $cellphone->id)->first();

            if (!$product_variation) {
                // Create default variation if it doesn't exist
                $product_variation = ProductVariation::create([
                    'product_id' => $cellphone->id,
                    'name' => 'DUMMY',
                    'is_dummy' => 1
                ]);

                // Create variation record
                Variation::create([
                    'product_id' => $cellphone->id,
                    'product_variation_id' => $product_variation->id,
                    'name' => 'DUMMY',
                    'sub_sku' => $request->imei,
                    'default_purchase_price' => 0,
                    'dpp_inc_tax' => 0,
                    'profit_percent' => 0,
                    'default_sell_price' => 0,
                    'sell_price_inc_tax' => 0,
                ]);
            }

            // Get the variation for price and stock updates
            $variation = Variation::where('product_variation_id', $product_variation->id)->first();

            if ($variation) {
                // Update variation sub_sku if IMEI changed
                if ($variation->sub_sku != $request->imei) {
                    $variation->sub_sku = $request->imei;
                }

                // Update prices if provided
                if ($request->has('purchase_price')) {
                    $purchase_price = $this->convertToFloat($request->purchase_price);
                    $variation->default_purchase_price = $purchase_price;
                    $variation->dpp_inc_tax = $purchase_price;
                }

                if ($request->has('sell_price')) {
                    $sell_price = $this->convertToFloat($request->sell_price);
                    $variation->default_sell_price = $sell_price;

                    // Calculate sell price including tax
                    $sell_price_inc_tax = $sell_price;
                    if ($cellphone->tax && $cellphone->tax_type == 'exclusive') {
                        $tax_rate = TaxRate::find($cellphone->tax);
                        if ($tax_rate) {
                            $tax_percent = $tax_rate->amount;
                            $sell_price_inc_tax = $sell_price + ($sell_price * ($tax_percent / 100));
                        }
                    }
                    $variation->sell_price_inc_tax = $sell_price_inc_tax;
                }

                if ($request->has('profit_percent')) {
                    $variation->profit_percent = $this->convertToFloat($request->profit_percent);
                }

                $variation->save();
            }

            // Handle stock adjustments for existing locations
            if ($request->has('stock_adjustments')) {
                $stock_adjustments = $request->stock_adjustments;

                foreach ($stock_adjustments as $location_id => $adjustment) {
                    if (empty($adjustment['quantity']) || $adjustment['quantity'] == 0) {
                        continue;
                    }

                    $quantity = $this->convertToFloat($adjustment['quantity']);
                    $type = $adjustment['type'] ?? 'add';

                    // Find existing stock record
                    $stock_detail = VariationLocationDetails::where('variation_id', $variation->id)
                        ->where('location_id', $location_id)
                        ->first();

                    if ($stock_detail) {
                        // Update existing stock
                        if ($type === 'add') {
                            $stock_detail->qty_available += $quantity;
                        } else if ($type === 'subtract') {
                            $stock_detail->qty_available -= $quantity;
                            // Prevent negative stock
                            if ($stock_detail->qty_available < 0) {
                                $stock_detail->qty_available = 0;
                            }
                        } else if ($type === 'set') {
                            // Direct set
                            $stock_detail->qty_available = $quantity;
                        }
                        $stock_detail->save();
                    }
                }
            }

            // Handle adding stock to new locations
            if ($request->has('new_location_stock')) {
                $new_stocks = $request->new_location_stock;
                $product_locations = $cellphone->product_locations()->pluck('id')->toArray();

                foreach ($new_stocks as $location_data) {
                    if (empty($location_data['location_id']) || empty($location_data['quantity'])) {
                        continue;
                    }

                    $location_id = $location_data['location_id'];
                    $quantity = $this->convertToFloat($location_data['quantity']);

                    // Check if stock already exists for this location
                    $existing_stock = VariationLocationDetails::where('variation_id', $variation->id)
                        ->where('location_id', $location_id)
                        ->first();

                    if (!$existing_stock) {
                        // Create new stock record
                        VariationLocationDetails::create([
                            'variation_id' => $variation->id,
                            'product_id' => $cellphone->id,
                            'product_variation_id' => $product_variation->id,
                            'location_id' => $location_id,
                            'qty_available' => $quantity,
                        ]);

                        // Create purchase_transfer transaction for new location
                        $this->createPurchaseTransferTransaction(
                            $cellphone->id,
                            $variation->id,
                            $location_id,
                            $quantity,
                            $variation->default_purchase_price
                        );

                        // Add location to product_locations if not already there
                        if (!in_array($location_id, $product_locations)) {
                            $product_locations[] = $location_id;
                        }
                    }
                }

                // Sync product locations
                if (!empty($product_locations)) {
                    $cellphone->product_locations()->sync($product_locations);
                }
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('cellphone::lang.updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('\Modules\Cellphone\Http\Controllers\CellphoneController@index')
            ->with('status', $output);
    }

    /**
     * Convert string to float, handling locale-specific formats
     * @param mixed $value
     * @return float
     */
    private function convertToFloat($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove thousand separators and convert decimal separator
        $value = str_replace(',', '', $value);
        return (float) $value;
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('cellphone.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        try {
            $can_be_deleted = true;
            $error_msg = '';

            // Check if any purchase exists
            $count = PurchaseLine::join(
                'transactions as T',
                'purchase_lines.transaction_id',
                '=',
                'T.id'
            )
                ->whereIn('T.type', ['purchase'])
                ->where('T.business_id', $business_id)
                ->where('purchase_lines.product_id', $id)
                ->count();

            if ($count > 0) {
                $can_be_deleted = false;
                $error_msg = __('lang_v1.purchase_already_exist');
            }

            // Check if any purchase_transfer exists (location transfers)
            if ($can_be_deleted) {
                $count = PurchaseLine::join(
                    'transactions as T',
                    'purchase_lines.transaction_id',
                    '=',
                    'T.id'
                )
                    ->whereIn('T.type', ['purchase_transfer'])
                    ->where('T.business_id', $business_id)
                    ->where('purchase_lines.product_id', $id)
                    ->count();

                if ($count > 0) {
                    $can_be_deleted = false;
                    $error_msg = 'El producto no se puede eliminar porque tiene transferencias entre ubicaciones';
                }
            }

            if ($can_be_deleted) {
                // Check if any opening stock sold
                $count = PurchaseLine::join(
                    'transactions as T',
                    'purchase_lines.transaction_id',
                    '=',
                    'T.id'
                )
                    ->where('T.type', 'opening_stock')
                    ->where('T.business_id', $business_id)
                    ->where('purchase_lines.product_id', $id)
                    ->where('purchase_lines.quantity_sold', '>', 0)
                    ->count();

                if ($count > 0) {
                    $can_be_deleted = false;
                    $error_msg = __('lang_v1.opening_stock_sold');
                } else {
                    // Check if any stock is adjusted
                    $count = PurchaseLine::join(
                        'transactions as T',
                        'purchase_lines.transaction_id',
                        '=',
                        'T.id'
                    )
                        ->where('T.business_id', $business_id)
                        ->where('purchase_lines.product_id', $id)
                        ->where('purchase_lines.quantity_adjusted', '>', 0)
                        ->count();

                    if ($count > 0) {
                        $can_be_deleted = false;
                        $error_msg = __('lang_v1.stock_adjusted');
                    }
                }
            }

            $cellphone = Cellphone::cellphones()
                ->where('business_id', $business_id)
                ->where('id', $id)
                ->firstOrFail();

            // Check for enable_stock = 0 cellphone (cellphones without stock tracking)
            if ($cellphone->enable_stock == 0) {
                $t_count = TransactionSellLine::join(
                    'transactions as T',
                    'transaction_sell_lines.transaction_id',
                    '=',
                    'T.id'
                )
                    ->where('T.business_id', $business_id)
                    ->where('transaction_sell_lines.product_id', $id)
                    ->count();

                if ($t_count > 0) {
                    $can_be_deleted = false;
                    $error_msg = "No se puede eliminar: el celular ya fue vendido en una transacción";
                }
            }

            if ($can_be_deleted) {
                if (!empty($cellphone)) {
                    DB::beginTransaction();

                    // Get all variation IDs for this product
                    $variation_ids = Variation::where('product_id', $id)->pluck('id')->toArray();

                    // Step 1: Delete variation_location_details (references variations)
                    if (!empty($variation_ids)) {
                        VariationLocationDetails::whereIn('variation_id', $variation_ids)->delete();
                    }

                    // Step 2: Delete variations (references product_variations)
                    Variation::where('product_id', $id)->delete();

                    // Step 3: Delete product_variations (references products)
                    ProductVariation::where('product_id', $id)->delete();

                    // Step 4: Delete product_locations pivot records
                    DB::table('product_locations')->where('product_id', $id)->delete();

                    // Step 5: Finally delete the cellphone/product
                    $cellphone->delete();

                    DB::commit();
                }

                $output = [
                    'success' => true,
                    'msg' => __('cellphone::lang.deleted_success')
                ];
            } else {
                $output = [
                    'success' => false,
                    'msg' => $error_msg
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    /**
     * Create purchase_transfer transaction and purchase_lines at destination location
     * This is critical for POS selling to work correctly
     */
    private function createPurchaseTransferTransaction($product_id, $variation_id, $location_id, $quantity, $unit_cost)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = auth()->id();

        // Create purchase_transfer transaction at destination location
        $transaction = Transaction::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'purchase_transfer',
            'status' => 'received',
            'payment_status' => 'paid',
            'transaction_date' => now(),
            'total_before_tax' => $unit_cost * $quantity,
            'final_total' => $unit_cost * $quantity,
            'created_by' => $user_id,
        ]);

        // Create purchase_line for this transaction
        PurchaseLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'quantity' => $quantity,
            'pp_without_discount' => $unit_cost,
            'purchase_price' => $unit_cost,
            'purchase_price_inc_tax' => $unit_cost,
            'item_tax' => 0,
            'tax_id' => null,
            'quantity_sold' => 0,
            'quantity_adjusted' => 0,
            'quantity_returned' => 0,
            'mfg_quantity_used' => 0,
        ]);
    }

    /**
     * Mass deactivate cellphones
     */
    public function massDeactivate(Request $request)
    {
        try {
            if (!empty($request->input('selected_products'))) {
                $business_id = $request->session()->get('user.business_id');

                $selected_products = explode(',', $request->input('selected_products'));

                DB::beginTransaction();

                $products = Product::where('business_id', $business_id)
                    ->whereIn('id', $selected_products)
                    ->update(['is_inactive' => 1]);

                DB::commit();
            }

            $output = [
                'success' => true,
                'msg' => 'Productos desactivados correctamente'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Activate a cellphone
     */
    public function activate($id)
    {
        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $product = Product::where('business_id', $business_id)
                    ->where('id', $id)
                    ->update(['is_inactive' => 0]);

                $output = [
                    'success' => true,
                    'msg' => 'Producto activado correctamente'
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ];
            }

            return $output;
        }
    }
}
