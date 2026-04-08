<?php

namespace Modules\Cellphone\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\TaxRate;
use App\Unit;
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
        $categories = Category::where('business_id', $business_id)
            ->whereNull('parent_id')
            ->pluck('name', 'id');
        $tax_rates = TaxRate::where('business_id', $business_id)->pluck('name', 'id');
        $warranties = Warranty::forDropdown($business_id);
        $locations = BusinessLocation::forDropdown($business_id);

        $estado_options = config('cellphone.estado_options');
        $default_variations = config('cellphone.default_variations');

        return view('cellphone::create', compact(
            'brands',
            'units',
            'categories',
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
            $cellphone->tax = $request->tax ?? null;
            $cellphone->tax_type = $request->tax_type ?? 'exclusive';
            $cellphone->warranty_id = $request->warranty_id ?? null;
            $cellphone->created_by = auth()->user()->id;
            $cellphone->barcode_type = $request->barcode_type ?? 'C128';

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
        $categories = Category::where('business_id', $business_id)
            ->whereNull('parent_id')
            ->pluck('name', 'id');
        $tax_rates = TaxRate::where('business_id', $business_id)->pluck('name', 'id');
        $warranties = Warranty::forDropdown($business_id);
        $locations = BusinessLocation::forDropdown($business_id);

        $estado_options = config('cellphone.estado_options');
        $default_variations = config('cellphone.default_variations');

        return view('cellphone::edit', compact(
            'cellphone',
            'brands',
            'units',
            'categories',
            'tax_rates',
            'warranties',
            'locations',
            'estado_options',
            'default_variations'
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

            $cellphone->save();

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
            $cellphone = Cellphone::cellphones()
                ->where('business_id', $business_id)
                ->where('id', $id)
                ->firstOrFail();

            $cellphone->delete();

            $output = [
                'success' => true,
                'msg' => __('cellphone::lang.deleted_success')
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
