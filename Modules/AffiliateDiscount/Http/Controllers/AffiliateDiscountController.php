<?php

namespace Modules\AffiliateDiscount\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Contact;
use Illuminate\Http\Request;
use Modules\AffiliateDiscount\Entities\AffiliatedBusiness;
use Modules\AffiliateDiscount\Entities\AffiliatedDiscountOption;
use Modules\AffiliateDiscount\Entities\TransactionAffiliateDiscount;
use Yajra\DataTables\Facades\DataTables;

class AffiliateDiscountController extends Controller
{
    /**
     * Display a listing of affiliated businesses
     */
    public function index()
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $affiliates = AffiliatedBusiness::leftJoin('contacts', 'affiliated_businesses.contact_id', '=', 'contacts.id')
                ->leftJoin('users', 'affiliated_businesses.created_by', '=', 'users.id')
                ->where('affiliated_businesses.business_id', $business_id)
                ->select(
                    'affiliated_businesses.*',
                    'contacts.name as contact_name',
                    'users.username as creator_name',
                    \DB::raw('(SELECT COUNT(*) FROM affiliated_discount_options WHERE affiliated_discount_options.affiliated_business_id = affiliated_businesses.id AND affiliated_discount_options.is_active = 1) as discount_count')
                );

            return DataTables::of($affiliates)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">';
                    $html .= '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' . __('messages.actions') . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>';
                    $html .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">';

                    $html .= '<li><a href="' . route('affiliate-discounts.edit', $row->id) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';

                    $html .= '<li><a href="' . route('affiliate-discounts.options', $row->id) . '"><i class="fa fa-percent"></i> Manage Discounts</a></li>';

                    $html .= '<li class="divider"></li>';
                    $html .= '<li><a href="#" class="delete-affiliate" data-href="' . route('affiliate-discounts.destroy', $row->id) . '"><i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</a></li>';

                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('contact_name', function ($row) {
                    return $row->contact_name ?: 'N/A';
                })
                ->editColumn('is_active', function ($row) {
                    if ($row->is_active) {
                        return '<span class="label label-success">Active</span>';
                    } else {
                        return '<span class="label label-default">Inactive</span>';
                    }
                })
                ->addColumn('creator_username', function ($row) {
                    return $row->creator_name ?: 'N/A';
                })
                ->rawColumns(['action', 'is_active'])
                ->make(true);
        }

        return view('affiliatediscount::admin.index');
    }

    /**
     * Show the form for creating a new affiliated business
     */
    public function create()
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get only business-type contacts
        $contacts = Contact::where('business_id', $business_id)
            ->where('contact_type', 'business')
            ->where('type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('affiliatediscount::admin.create', compact('contacts'));
    }

    /**
     * Store a newly created affiliated business
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $input = $request->only([
                'contact_id',
                'contact_phone',
                'contact_email',
                'contract_number',
                'start_date',
                'end_date',
                'notes'
            ]);
            $input['business_id'] = $business_id;
            $input['created_by'] = auth()->user()->id;
            $input['is_active'] = $request->has('is_active') ? 1 : 0;

            AffiliatedBusiness::create($input);

            $output = [
                'success' => true,
                'msg' => 'Affiliated business added successfully'
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile(). ' Line:' . $e->getLine(). ' Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->route('affiliate-discounts.index')->with('status', $output);
    }

    /**
     * Show the form for editing an affiliated business
     */
    public function edit($id)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $affiliate = AffiliatedBusiness::where('business_id', $business_id)
            ->findOrFail($id);

        $contacts = Contact::where('business_id', $business_id)
            ->where('contact_type', 'business')
            ->where('type', 'customer')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('affiliatediscount::admin.edit', compact('affiliate', 'contacts'));
    }

    /**
     * Update an affiliated business
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $affiliate = AffiliatedBusiness::where('business_id', $business_id)
                ->findOrFail($id);

            $input = $request->only([
                'contact_id',
                'contact_phone',
                'contact_email',
                'contract_number',
                'start_date',
                'end_date',
                'notes'
            ]);
            $input['is_active'] = $request->has('is_active') ? 1 : 0;

            $affiliate->update($input);

            $output = [
                'success' => true,
                'msg' => 'Affiliated business updated successfully'
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile(). ' Line:' . $e->getLine(). ' Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->route('affiliate-discounts.index')->with('status', $output);
    }

    /**
     * Remove an affiliated business
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $affiliate = AffiliatedBusiness::where('business_id', $business_id)
                ->findOrFail($id);

            $affiliate->delete();

            $output = [
                'success' => true,
                'msg' => 'Affiliated business deleted successfully'
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile(). ' Line:' . $e->getLine(). ' Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->route('affiliate-discounts.index')->with('status', $output);
    }

    /**
     * Manage discount options for an affiliated business
     */
    public function manageOptions($id)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $affiliate = AffiliatedBusiness::where('business_id', $business_id)
            ->with('discountOptions')
            ->findOrFail($id);

        $categories = config('affiliatediscount.categories');
        $discount_types = config('affiliatediscount.discount_types');

        // Group options by category
        $optionsByCategory = $affiliate->discountOptions->groupBy('category_name');

        return view('affiliatediscount::admin.discount_options', compact(
            'affiliate',
            'categories',
            'discount_types',
            'optionsByCategory'
        ));
    }

    /**
     * Store a new discount option
     */
    public function storeOption(Request $request, $id)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $affiliate = AffiliatedBusiness::where('business_id', $business_id)
                ->findOrFail($id);

            $input = $request->only([
                'category_name',
                'discount_type',
                'discount_value',
                'discount_label',
                'display_order'
            ]);
            $input['affiliated_business_id'] = $affiliate->id;
            $input['is_active'] = $request->has('is_active') ? 1 : 0;

            // Create the affiliated discount option
            $option = AffiliatedDiscountOption::create($input);

            // Also create corresponding discount in core discounts table
            $this->createCoreDiscount($option, $affiliate, $business_id);

            $output = [
                'success' => true,
                'msg' => 'Discount option added successfully'
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile(). ' Line:' . $e->getLine(). ' Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return response()->json($output);
    }

    /**
     * Create corresponding discounts in core discounts table
     * Creates one discount for each mapped product category
     */
    protected function createCoreDiscount($option, $affiliate, $business_id)
    {
        try {
            // Get category IDs that should receive this discount
            $categoryIds = $this->getCategoryIdsForDiscountCategory($option->category_name, $business_id);

            if (empty($categoryIds)) {
                \Log::warning('AffiliateDiscount: No categories found for ' . $option->category_name . ' in business ' . $business_id);
                return;
            }

            // Get first active location for this business
            $location = \DB::table('business_locations')
                ->where('business_id', $business_id)
                ->where('is_active', 1)
                ->first();

            if (!$location) {
                \Log::warning('AffiliateDiscount: No active location found for business ' . $business_id);
                return;
            }

            // Get contact name for discount name
            $contact = \DB::table('contacts')
                ->where('id', $affiliate->contact_id)
                ->first();

            $contactName = $contact ? ($contact->supplier_business_name ?: $contact->name) : 'Affiliated Business';

            // Set very long date range (100 years) so it's always active
            $startsAt = now()->subYear();
            $endsAt = now()->addYears(100);

            $createdDiscounts = [];

            // Create a discount for each matching category
            foreach ($categoryIds as $categoryId) {
                $categoryName = \DB::table('categories')
                    ->where('id', $categoryId)
                    ->value('name');

                $discountName = $contactName . ' - ' . strtoupper($categoryName);
                if ($option->discount_label) {
                    $discountName .= ' (' . $option->discount_label . ')';
                }

                $discountId = \DB::table('discounts')->insertGetId([
                    'name' => $discountName,
                    'business_id' => $business_id,
                    'brand_id' => null,
                    'category_id' => $categoryId,
                    'location_id' => $location->id,
                    'priority' => 10, // High priority so affiliate discounts override others
                    'discount_type' => $option->discount_type,
                    'discount_amount' => $option->discount_value,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'is_active' => $option->is_active,
                    'spg' => null, // Not tied to selling price group
                    'applicable_in_cg' => 0, // Not tied to customer group
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdDiscounts[] = $discountId;
                \Log::info('AffiliateDiscount: Created core discount ID ' . $discountId . ' for category ' . $categoryName . ' (ID: ' . $categoryId . ')');
            }

            // Link the first created discount to the option (as primary reference)
            if (!empty($createdDiscounts)) {
                $option->update(['discount_id' => $createdDiscounts[0]]);
                \Log::info('AffiliateDiscount: Linked option ID ' . $option->id . ' to discount ID ' . $createdDiscounts[0] . ' (created ' . count($createdDiscounts) . ' total discounts)');
            }

        } catch (\Exception $e) {
            \Log::error('AffiliateDiscount: Failed to create core discount: ' . $e->getMessage());
        }
    }

    /**
     * Get category IDs that should receive this discount based on category mapping
     */
    protected function getCategoryIdsForDiscountCategory($discountCategory, $businessId)
    {
        $categoryMapping = config('affiliatediscount.category_mapping', []);
        $categoryNames = $categoryMapping[strtolower($discountCategory)] ?? [];

        if (empty($categoryNames)) {
            \Log::warning('AffiliateDiscount: No category mapping found for: ' . $discountCategory);
            return [];
        }

        // Get category IDs for these names
        $categoryIds = \DB::table('categories')
            ->where('business_id', $businessId)
            ->whereIn('name', $categoryNames)
            ->pluck('id')
            ->toArray();

        \Log::info('AffiliateDiscount: Mapped ' . $discountCategory . ' to ' . count($categoryIds) . ' category IDs: ' . implode(', ', $categoryIds));

        return $categoryIds;
    }

    /**
     * Update corresponding discounts in core discounts table
     * Updates all discounts associated with this option
     */
    protected function updateCoreDiscount($option)
    {
        try {
            // Get affiliated business for contact name and business_id
            $affiliate = AffiliatedBusiness::find($option->affiliated_business_id);
            if (!$affiliate) {
                return;
            }

            $contact = \DB::table('contacts')
                ->where('id', $affiliate->contact_id)
                ->first();

            $contactName = $contact ? ($contact->supplier_business_name ?: $contact->name) : 'Affiliated Business';

            // Find ALL discounts for this option (they share the same discount value/type)
            // We identify them by business_id, discount_type, discount_amount, and priority
            $discounts = \DB::table('discounts')
                ->where('business_id', $affiliate->business_id)
                ->where('discount_type', $option->discount_type)
                ->where('discount_amount', $option->discount_value)
                ->where('priority', 10)
                ->get();

            if ($discounts->isEmpty()) {
                \Log::warning('AffiliateDiscount: No discounts found to update for option ' . $option->id);
                return;
            }

            $updatedCount = 0;
            foreach ($discounts as $discount) {
                // Get category name for this discount
                $categoryName = \DB::table('categories')
                    ->where('id', $discount->category_id)
                    ->value('name');

                // Update discount name with new label
                $discountName = $contactName . ' - ' . strtoupper($categoryName);
                if ($option->discount_label) {
                    $discountName .= ' (' . $option->discount_label . ')';
                }

                \DB::table('discounts')
                    ->where('id', $discount->id)
                    ->update([
                        'name' => $discountName,
                        'discount_type' => $option->discount_type,
                        'discount_amount' => $option->discount_value,
                        'is_active' => $option->is_active,
                        'updated_at' => now(),
                    ]);

                $updatedCount++;
            }

            \Log::info('AffiliateDiscount: Updated ' . $updatedCount . ' core discounts for option ' . $option->id);

        } catch (\Exception $e) {
            \Log::error('AffiliateDiscount: Failed to update core discount: ' . $e->getMessage());
        }
    }

    /**
     * Update a discount option
     */
    public function updateOption(Request $request, $optionId)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $option = AffiliatedDiscountOption::findOrFail($optionId);

            $input = $request->only([
                'discount_type',
                'discount_value',
                'discount_label',
                'display_order'
            ]);
            $input['is_active'] = $request->has('is_active') ? 1 : 0;

            $option->update($input);

            // Also update the core discount if it exists
            $this->updateCoreDiscount($option);

            $output = [
                'success' => true,
                'msg' => 'Discount option updated successfully'
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile(). ' Line:' . $e->getLine(). ' Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return response()->json($output);
    }

    /**
     * Delete a discount option and all associated core discounts
     */
    public function destroyOption($optionId)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $option = AffiliatedDiscountOption::findOrFail($optionId);

            // Get affiliated business to find all related discounts
            $affiliate = AffiliatedBusiness::find($option->affiliated_business_id);

            if ($affiliate) {
                // Delete ALL core discounts for this option (by matching business_id, type, amount, priority)
                $deletedCount = \DB::table('discounts')
                    ->where('business_id', $affiliate->business_id)
                    ->where('discount_type', $option->discount_type)
                    ->where('discount_amount', $option->discount_value)
                    ->where('priority', 10)
                    ->delete();

                \Log::info('AffiliateDiscount: Deleted ' . $deletedCount . ' core discounts for option ID ' . $option->id);
            }

            $option->delete();

            $output = [
                'success' => true,
                'msg' => 'Discount option deleted successfully'
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile(). ' Line:' . $e->getLine(). ' Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return response()->json($output);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        if (!auth()->user()->can('manage_affiliate_discounts')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $affiliate = AffiliatedBusiness::where('business_id', $business_id)
                ->findOrFail($id);

            $affiliate->is_active = !$affiliate->is_active;
            $affiliate->save();

            $output = [
                'success' => true,
                'msg' => 'Status updated successfully'
            ];
        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return response()->json($output);
    }

    /**
     * Show reports page
     */
    public function reports()
    {
        if (!auth()->user()->can('view_affiliate_reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $affiliates = AffiliatedBusiness::where('business_id', $business_id)
            ->with('contact')
            ->active()
            ->get();

        return view('affiliatediscount::reports.affiliated_sales', compact('affiliates'));
    }

    /**
     * Get reports data
     */
    public function reportsData(Request $request)
    {
        if (!auth()->user()->can('view_affiliate_reports')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $affiliate_id = $request->get('affiliate_id');

        $query = TransactionAffiliateDiscount::with(['transaction', 'affiliatedBusiness.contact'])
            ->whereHas('affiliatedBusiness', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            });

        if ($start_date && $end_date) {
            $query->dateRange($start_date, $end_date);
        }

        if ($affiliate_id) {
            $query->where('affiliated_business_id', $affiliate_id);
        }

        if ($request->ajax()) {
            return DataTables::of($query)
                ->editColumn('created_at', function($row) {
                    return \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i:s');
                })
                ->addColumn('business_name', function($row) {
                    return $row->affiliatedBusiness->contact->name ?? 'N/A';
                })
                ->addColumn('invoice_no', function($row) {
                    return $row->transaction->invoice_no ?? 'N/A';
                })
                ->make(true);
        }

        return response()->json(['error' => 'Invalid request']);
    }
}
