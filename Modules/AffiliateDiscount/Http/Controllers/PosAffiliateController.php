<?php

namespace Modules\AffiliateDiscount\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AffiliateDiscount\Entities\AffiliatedBusiness;
use Modules\AffiliateDiscount\Entities\AffiliatedDiscountOption;
use Modules\AffiliateDiscount\Entities\TransactionAffiliateDiscount;
use Illuminate\Support\Facades\DB;

class PosAffiliateController extends Controller
{
    /**
     * Get list of active affiliated businesses
     */
    public function getBusinesses(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $businesses = AffiliatedBusiness::with('contact')
            ->where('business_id', $business_id)
            ->active()
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->contact->name ?? 'Unknown',
                    'contact_id' => $item->contact_id,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $businesses
        ]);
    }

    /**
     * Get discount options for a specific affiliated business
     */
    public function getOptions($businessId)
    {
        try {
            $affiliate = AffiliatedBusiness::findOrFail($businessId);

            // Get options grouped by category
            $optionsByCategory = $affiliate->activeDiscountOptions()
                ->ordered()
                ->get()
                ->groupBy('category_name')
                ->map(function($options, $category) {
                    return [
                        'category' => $category,
                        'category_label' => config('affiliatediscount.categories')[$category] ?? strtoupper($category),
                        'options' => $options->map(function($option) {
                            return [
                                'id' => $option->id,
                                'label' => $option->formatted_label,
                                'discount_type' => $option->discount_type,
                                'discount_value' => $option->discount_value,
                                'display_order' => $option->display_order,
                            ];
                        })->values()
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $optionsByCategory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Business not found'
            ], 404);
        }
    }

    /**
     * Save discount selections to session
     */
    public function saveSelection(Request $request)
    {
        try {
            $businessId = $request->input('business_id');
            $selections = $request->input('selections', []); // Array of discount option IDs

            // Store in session
            $sessionKey = config('affiliatediscount.session_key');
            $request->session()->put($sessionKey, [
                'business_id' => $businessId,
                'selections' => $selections,
                'timestamp' => now()->timestamp
            ]);

            return response()->json([
                'success' => true,
                'msg' => 'Discount selections saved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Failed to save selections'
            ], 500);
        }
    }

    /**
     * Calculate discounts for cart items
     */
    public function calculateDiscounts(Request $request)
    {
        try {
            $sessionKey = config('affiliatediscount.session_key');
            $sessionData = $request->session()->get($sessionKey);

            \Log::info('[AffiliateDiscount] Calculate request received', [
                'has_session' => !empty($sessionData),
                'cart_items_count' => count($request->input('cart_items', []))
            ]);

            if (!$sessionData || !isset($sessionData['business_id'])) {
                \Log::warning('[AffiliateDiscount] No active session found');
                return response()->json([
                    'success' => false,
                    'msg' => 'No active discount session'
                ], 400);
            }

            $cartItems = $request->input('cart_items', []);
            $selectedOptionIds = $sessionData['selections'] ?? [];

            \Log::info('[AffiliateDiscount] Session data', [
                'business_id' => $sessionData['business_id'],
                'selected_option_ids' => $selectedOptionIds
            ]);

            // Get selected discount options
            $discountOptions = AffiliatedDiscountOption::whereIn('id', $selectedOptionIds)
                ->get()
                ->keyBy('category_name');

            \Log::info('[AffiliateDiscount] Loaded discount options', [
                'count' => $discountOptions->count(),
                'categories' => $discountOptions->keys()->toArray()
            ]);

            $calculations = [];
            $totalDiscount = 0;

            // Get all product IDs from cart
            $productIds = array_filter(array_column($cartItems, 'product_id'));

            \Log::info('[AffiliateDiscount] Processing products', [
                'product_ids' => $productIds
            ]);

            // Fetch product categories from database in one query
            $productCategories = DB::table('products')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->whereIn('products.id', $productIds)
                ->select('products.id as product_id', 'categories.name as category_name')
                ->get()
                ->keyBy('product_id');

            \Log::info('[AffiliateDiscount] Product categories from DB', [
                'categories' => $productCategories->mapWithKeys(function($item) {
                    return [$item->product_id => $item->category_name];
                })->toArray()
            ]);

            foreach ($cartItems as $item) {
                // Get category from database lookup
                $productId = $item['product_id'] ?? null;

                if (!$productId || !isset($productCategories[$productId])) {
                    \Log::warning('[AffiliateDiscount] Product not found or no category', [
                        'product_id' => $productId
                    ]);
                    continue;
                }

                $productCategoryName = $productCategories[$productId]->category_name;

                // Map product category to discount category
                $discountCategory = $this->mapProductCategory($productCategoryName);

                \Log::info('[AffiliateDiscount] Category mapping', [
                    'product_id' => $productId,
                    'product_category' => $productCategoryName,
                    'mapped_to_discount_category' => $discountCategory
                ]);

                if ($discountCategory && isset($discountOptions[$discountCategory])) {
                    $option = $discountOptions[$discountCategory];
                    $subtotal = $item['quantity'] * $item['unit_price'];
                    $discount = $option->calculateDiscount($subtotal);

                    \Log::info('[AffiliateDiscount] Discount calculated', [
                        'product_id' => $productId,
                        'category' => $discountCategory,
                        'subtotal' => $subtotal,
                        'discount_type' => $option->discount_type,
                        'discount_value' => $option->discount_value,
                        'calculated_discount' => $discount
                    ]);

                    $calculations[] = [
                        'product_id' => $productId,
                        'category' => $discountCategory,
                        'product_category' => $productCategoryName,
                        'subtotal' => $subtotal,
                        'discount' => $discount,
                        'final_price' => $subtotal - $discount
                    ];

                    $totalDiscount += $discount;
                } else {
                    \Log::info('[AffiliateDiscount] No discount applied', [
                        'product_id' => $productId,
                        'product_category' => $productCategoryName,
                        'discount_category' => $discountCategory,
                        'has_discount_option' => isset($discountOptions[$discountCategory])
                    ]);
                }
            }

            \Log::info('[AffiliateDiscount] Final calculation result', [
                'items_with_discount' => count($calculations),
                'total_discount' => round($totalDiscount, 4)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'calculations' => $calculations,
                    'total_discount' => round($totalDiscount, 4),
                    'business_id' => $sessionData['business_id']
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('[AffiliateDiscount] Calculation error: ' . $e->getMessage());
            \Log::error('[AffiliateDiscount] Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'msg' => 'Failed to calculate discounts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick add new discount option from POS
     */
    public function addOption(Request $request)
    {
        try {
            $businessId = $request->input('business_id');
            $category = $request->input('category');
            $type = $request->input('discount_type');
            $value = $request->input('discount_value');
            $label = $request->input('discount_label');

            $affiliate = AffiliatedBusiness::findOrFail($businessId);

            // Get next display order
            $maxOrder = $affiliate->discountOptions()
                ->where('category_name', $category)
                ->max('display_order');

            $option = AffiliatedDiscountOption::create([
                'affiliated_business_id' => $businessId,
                'category_name' => $category,
                'discount_type' => $type,
                'discount_value' => $value,
                'discount_label' => $label,
                'is_active' => 1,
                'display_order' => ($maxOrder ?? 0) + 1
            ]);

            return response()->json([
                'success' => true,
                'msg' => 'Discount option added',
                'data' => [
                    'id' => $option->id,
                    'label' => $option->formatted_label
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Failed to add option'
            ], 500);
        }
    }

    /**
     * Get current session discounts
     */
    public function getActiveSession(Request $request)
    {
        $sessionKey = config('affiliatediscount.session_key');
        $sessionData = $request->session()->get($sessionKey);

        if (!$sessionData) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        // Get business and option details
        $business = AffiliatedBusiness::with('contact')->find($sessionData['business_id']);
        $options = AffiliatedDiscountOption::whereIn('id', $sessionData['selections'] ?? [])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'business_id' => $sessionData['business_id'],
                'business_name' => $business->contact->name ?? 'Unknown',
                'selections' => $options->map(function($opt) {
                    return [
                        'id' => $opt->id,
                        'category' => $opt->category_name,
                        'label' => $opt->formatted_label
                    ];
                })
            ]
        ]);
    }

    /**
     * Clear session discounts
     */
    public function clearSession(Request $request)
    {
        $sessionKey = config('affiliatediscount.session_key');
        $request->session()->forget($sessionKey);

        return response()->json([
            'success' => true,
            'msg' => 'Session cleared'
        ]);
    }

    /**
     * Check if selected customer is affiliated business and auto-activate discounts
     */
    public function checkCustomerAffiliation(Request $request)
    {
        try {
            $contactId = $request->input('contact_id');
            $business_id = $request->session()->get('user.business_id');

            if (!$contactId) {
                return response()->json([
                    'success' => false,
                    'msg' => 'No contact ID provided'
                ], 400);
            }

            // Check if this contact is an affiliated business
            $affiliate = AffiliatedBusiness::where('business_id', $business_id)
                ->where('contact_id', $contactId)
                ->active()
                ->first();

            if (!$affiliate) {
                // Not an affiliated business - clear any active session
                $sessionKey = config('affiliatediscount.session_key');
                $request->session()->forget($sessionKey);

                return response()->json([
                    'success' => true,
                    'is_affiliated' => false,
                    'msg' => 'Customer is not an affiliated business'
                ]);
            }

            // Get all active discount options for this business
            $options = $affiliate->activeDiscountOptions()->get();

            if ($options->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'is_affiliated' => true,
                    'has_discounts' => false,
                    'msg' => 'Affiliated business has no active discounts'
                ]);
            }

            // Auto-activate all discounts for this affiliated business
            $sessionKey = config('affiliatediscount.session_key');
            $request->session()->put($sessionKey, [
                'business_id' => $affiliate->id,
                'selections' => $options->pluck('id')->toArray(),
                'timestamp' => now()->timestamp,
                'auto_activated' => true
            ]);

            \Log::info('[AffiliateDiscount] Auto-activated discounts for contact ' . $contactId, [
                'affiliated_business_id' => $affiliate->id,
                'discount_count' => $options->count()
            ]);

            return response()->json([
                'success' => true,
                'is_affiliated' => true,
                'has_discounts' => true,
                'data' => [
                    'business_id' => $affiliate->id,
                    'business_name' => $affiliate->contact->name ?? 'Unknown',
                    'discount_count' => $options->count(),
                    'categories' => $options->pluck('category_name')->unique()->values()
                ],
                'msg' => 'Discounts auto-activated for ' . ($affiliate->contact->name ?? 'affiliated business')
            ]);

        } catch (\Exception $e) {
            \Log::error('[AffiliateDiscount] Check customer affiliation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => 'Failed to check customer affiliation'
            ], 500);
        }
    }

    /**
     * Save transaction discounts (called when finalizing sale)
     */
    public function saveTransaction(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $calculations = $request->input('calculations', []);
            $businessId = $request->input('business_id');

            DB::beginTransaction();

            foreach ($calculations as $calc) {
                if ($calc['discount'] > 0) {
                    TransactionAffiliateDiscount::create([
                        'transaction_id' => $transactionId,
                        'affiliated_business_id' => $businessId,
                        'discount_option_id' => $calc['discount_option_id'] ?? null,
                        'category_name' => $calc['category'],
                        'items_affected' => $calc['items_count'] ?? 1,
                        'total_discount_applied' => $calc['discount']
                    ]);
                }
            }

            DB::commit();

            // Clear session after saving
            $sessionKey = config('affiliatediscount.session_key');
            $request->session()->forget($sessionKey);

            return response()->json([
                'success' => true,
                'msg' => 'Transaction discounts saved'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Save transaction error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => 'Failed to save transaction'
            ], 500);
        }
    }

    /**
     * Map product category to discount category
     */
    private function mapProductCategory($productCategory)
    {
        // Map product categories to discount categories
        // Updated with actual database category names
        $mapping = [
            // Direct discount category names
            'equipos' => 'equipos',
            'pantallas' => 'pantallas',
            'accesorios' => 'accesorios',
            'desbloqueos' => 'desbloqueos',
            'servicios' => 'servicios',
            'reparaciones' => 'reparaciones',
            
            // Actual database categories mapped to accesorios
            'protectores' => 'accesorios',
            'protector' => 'accesorios',
            'cargadores' => 'accesorios',
            'bocinas' => 'accesorios',
            'audifonos' => 'accesorios',
            'audífonos' => 'accesorios',
            'cables' => 'accesorios',
            'adaptadores' => 'accesorios',
            'soportes' => 'accesorios',
            'micas' => 'accesorios',
            'fundas' => 'accesorios',
            'carcasas' => 'accesorios',
        ];

        $lower = strtolower($productCategory);

        // Direct match
        if (isset($mapping[$lower])) {
            return $mapping[$lower];
        }

        // Partial match
        foreach ($mapping as $key => $value) {
            if (str_contains($lower, $key)) {
                return $value;
            }
        }

        return null;
    }
}
