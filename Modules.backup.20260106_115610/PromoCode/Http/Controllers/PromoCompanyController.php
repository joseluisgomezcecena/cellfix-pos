<?php

namespace Modules\PromoCode\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PromoCode\Entities\PromoCompany;
use Modules\PromoCode\Entities\PromoCategoryDiscount;
use Yajra\DataTables\Facades\DataTables;

class PromoCompanyController extends Controller
{
    /**
     * Display a listing of promo companies.
     */
    public function index()
    {
        if (!auth()->user()->can('promocode.view') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $promo_companies = PromoCompany::where('business_id', $business_id)
                ->with('categoryDiscounts')
                ->select(['id', 'company_name', 'description', 'is_active', 'created_at']);

            return DataTables::of($promo_companies)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">';

                    $html .= '<li><a href="' . action([\Modules\PromoCode\Http\Controllers\PromoCompanyController::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';

                    $html .= '<li><a data-href="' . action([\Modules\PromoCode\Http\Controllers\PromoCompanyController::class, 'destroy'], [$row->id]) . '" class="delete_promo_company_button"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>';

                    $html .= '</ul></div>';

                    return $html;
                })
                ->editColumn('is_active', function ($row) {
                    if ($row->is_active == 1) {
                        return '<span class="label label-success">' . __('promocode::lang.active') . '</span>';
                    } else {
                        return '<span class="label label-danger">' . __('promocode::lang.inactive') . '</span>';
                    }
                })
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('promocode::promo_companies.index');
    }

    /**
     * Show the form for creating a new promo company.
     */
    public function create()
    {
        if (!auth()->user()->can('promocode.create') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Use AJAX autocomplete for contacts (no need to load all contacts upfront)
        $categories = PromoCategoryDiscount::getAvailableCategories();

        return view('promocode::promo_companies.create', compact('categories'));
    }

    /**
     * Store a newly created promo company.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('promocode.create') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'company_name' => 'required|string|max:191|unique:promo_companies,company_name',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('user.business_id');

            $promoCompany = PromoCompany::create([
                'business_id' => $business_id,
                'company_name' => $request->company_name,
                'description' => $request->description,
                'is_active' => $request->is_active,
            ]);

            // Create category discounts
            foreach ($request->categories ?? [] as $category => $discount) {
                if (!empty($discount['enabled']) && !empty($discount['value'])) {
                    PromoCategoryDiscount::create([
                        'promo_company_id' => $promoCompany->id,
                        'category_name' => $category,
                        'discount_type' => $discount['type'],
                        'discount_value' => $discount['value'],
                    ]);
                }
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('promocode::lang.promo_company_added_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->route('promo-companies.index')->with('status', $output);
    }

    /**
     * Show the form for editing a promo company.
     */
    public function edit($id)
    {
        if (!auth()->user()->can('promocode.update') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $promoCompany = PromoCompany::where('business_id', $business_id)
            ->with('categoryDiscounts')
            ->findOrFail($id);

        // Use AJAX autocomplete for contacts (no need to load all contacts upfront)
        $categories = PromoCategoryDiscount::getAvailableCategories();

        // Transform category discounts into an array
        $categoryDiscounts = [];
        foreach ($promoCompany->categoryDiscounts as $discount) {
            $categoryDiscounts[$discount->category_name] = [
                'enabled' => true,
                'type' => $discount->discount_type,
                'value' => $discount->discount_value,
            ];
        }

        return view('promocode::promo_companies.edit', compact('promoCompany', 'categories', 'categoryDiscounts'));
    }

    /**
     * Update the specified promo company.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('promocode.update') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $promoCompany = PromoCompany::where('business_id', $business_id)->findOrFail($id);

        $request->validate([
            'company_name' => 'required|string|max:191|unique:promo_companies,company_name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $promoCompany->update([
                'company_name' => $request->company_name,
                'description' => $request->description,
                'is_active' => $request->is_active,
            ]);

            // Delete existing category discounts
            $promoCompany->categoryDiscounts()->delete();

            // Create new category discounts
            foreach ($request->categories ?? [] as $category => $discount) {
                if (!empty($discount['enabled']) && !empty($discount['value'])) {
                    PromoCategoryDiscount::create([
                        'promo_company_id' => $promoCompany->id,
                        'category_name' => $category,
                        'discount_type' => $discount['type'],
                        'discount_value' => $discount['value'],
                    ]);
                }
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('promocode::lang.promo_company_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->route('promo-companies.index')->with('status', $output);
    }

    /**
     * Remove the specified promo company.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('promocode.delete') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $promoCompany = PromoCompany::where('business_id', $business_id)->findOrFail($id);
                $promoCompany->delete();

                $output = [
                    'success' => true,
                    'msg' => __('promocode::lang.promo_company_deleted_success')
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }
}
