<?php

namespace Modules\PromoCode\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PromoCode\Entities\PromoCompany;
use Modules\PromoCode\Entities\PromoCategoryDiscount;

class PromoCodeController extends Controller
{
    /**
     * Verify a promo code (company name) and return applicable discounts.
     */
    public function verify(Request $request)
    {
        try {
            $request->validate([
                'company_name' => 'required|string',
            ]);

            $promoCompany = PromoCompany::findByCompanyName($request->company_name);

            if (!$promoCompany) {
                return response()->json([
                    'success' => false,
                    'message' => __('promocode::lang.promo_company_not_found'),
                ], 404);
            }

            // Get all category discounts for this company
            $categoryDiscounts = $promoCompany->categoryDiscounts()
                ->get()
                ->mapWithKeys(function ($discount) {
                    return [$discount->category_name => [
                        'type' => $discount->discount_type,
                        'value' => $discount->discount_value,
                    ]];
                });

            return response()->json([
                'success' => true,
                'promo_company_id' => $promoCompany->id,
                'company_name' => $promoCompany->company_name,
                'category_discounts' => $categoryDiscounts,
                'category_mapping' => PromoCategoryDiscount::getCategoryMapping(),
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __("messages.something_went_wrong"),
            ], 500);
        }
    }

    /**
     * Apply promo code to cart items and return calculated discounts.
     */
    public function calculate(Request $request)
    {
        try {
            $request->validate([
                'company_name' => 'required|string',
                'cart_items' => 'required|array',
            ]);

            $promoCompany = PromoCompany::findByCompanyName($request->company_name);

            if (!$promoCompany) {
                return response()->json([
                    'success' => false,
                    'message' => __('promocode::lang.promo_company_not_found'),
                ], 404);
            }

            $totalDiscount = 0;
            $itemDiscounts = [];

            foreach ($request->cart_items as $item) {
                $productCategory = $item['category'] ?? null;

                if (!$productCategory) {
                    continue;
                }

                // Map product category to promo category
                $promoCategory = PromoCategoryDiscount::mapProductCategory($productCategory);

                if (!$promoCategory) {
                    continue;
                }

                // Get discount for this category
                $categoryDiscount = $promoCompany->getDiscountForCategory($promoCategory);

                if (!$categoryDiscount) {
                    continue;
                }

                // Calculate discount for this item
                $itemPrice = $item['price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $lineTotal = $itemPrice * $quantity;

                $discountAmount = $categoryDiscount->calculateDiscount($lineTotal);
                $totalDiscount += $discountAmount;

                $itemDiscounts[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'variation_id' => $item['variation_id'] ?? null,
                    'category' => $productCategory,
                    'promo_category' => $promoCategory,
                    'discount_type' => $categoryDiscount->discount_type,
                    'discount_value' => $categoryDiscount->discount_value,
                    'discount_amount' => $discountAmount,
                ];
            }

            // Store promo code info in session
            $request->session()->put('promo_code', [
                'promo_company_id' => $promoCompany->id,
                'company_name' => $promoCompany->company_name,
                'total_discount' => $totalDiscount,
                'item_discounts' => $itemDiscounts,
            ]);

            return response()->json([
                'success' => true,
                'promo_company_id' => $promoCompany->id,
                'company_name' => $promoCompany->company_name,
                'total_discount' => $totalDiscount,
                'item_discounts' => $itemDiscounts,
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __("messages.something_went_wrong"),
            ], 500);
        }
    }

    /**
     * Calculate cellphone promo discounts based on price ranges.
     * - Price < $4,999: $100 discount
     * - Price >= $5,000: $300 discount
     */
    public function calculateCellphonePromo(Request $request)
    {
        try {
            $request->validate([
                'cart_items' => 'required|array',
            ]);

            // Price thresholds and discount amounts (fixed values)
            $priceThreshold = 4999;
            $discountLow = 100;   // For price < $4,999
            $discountHigh = 300;  // For price >= $5,000

            $totalDiscount = 0;
            $itemDiscounts = [];
            $cellphoneCount = 0;

            foreach ($request->cart_items as $item) {
                $productId = $item['product_id'] ?? null;

                // Check if product is a cellphone by:
                // 1. is_cellphone flag (from Cellphone module)
                // 2. Category name contains "Equipos" or similar
                // 3. Lookup category from database if not provided
                $isCellphone = $item['is_cellphone'] ?? false;
                $category = strtolower($item['category'] ?? '');

                // If category is empty, fetch from database
                if (empty($category) && $productId) {
                    $product = \DB::table('products')
                        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                        ->where('products.id', $productId)
                        ->select('categories.name as category_name', 'products.product_custom_field6')
                        ->first();

                    if ($product) {
                        $category = strtolower($product->category_name ?? '');
                        // Also check if it's a cellphone module product
                        if ($product->product_custom_field6 === 'CELLPHONE_MODULE') {
                            $isCellphone = true;
                        }
                    }
                }

                // Check category for cellphone-related names
                $cellphoneCategories = ['equipos', 'celulares', 'smartphones', 'cellphones', 'telefonos'];
                $isCellphoneCategory = false;
                foreach ($cellphoneCategories as $catName) {
                    if (strpos($category, $catName) !== false) {
                        $isCellphoneCategory = true;
                        break;
                    }
                }

                // Skip if not a cellphone (neither flag nor category match)
                if (($isCellphone === '0' || $isCellphone === false || $isCellphone === 0) && !$isCellphoneCategory) {
                    continue;
                }

                $cellphoneCount++;
                $itemPrice = floatval($item['price'] ?? 0);
                $quantity = floatval($item['quantity'] ?? 1);

                // Calculate discount based on price range
                $discountValue = 0;
                if ($itemPrice >= 5000) {
                    $discountValue = $discountHigh;
                } elseif ($itemPrice > 0 && $itemPrice < $priceThreshold) {
                    $discountValue = $discountLow;
                }

                if ($discountValue > 0) {
                    $discountAmount = $discountValue * $quantity;
                    $totalDiscount += $discountAmount;

                    $itemDiscounts[] = [
                        'product_id' => $item['product_id'] ?? null,
                        'variation_id' => $item['variation_id'] ?? null,
                        'promo_category' => 'celulares',
                        'discount_type' => 'fixed',
                        'discount_value' => $discountValue,
                        'discount_amount' => $discountAmount,
                    ];
                }
            }

            if ($cellphoneCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('promocode::lang.no_cellphones_in_cart'),
                ], 400);
            }

            // Store promo code info in session
            $request->session()->put('promo_code', [
                'promo_company_id' => null,
                'promo_type' => 'cellphone',
                'company_name' => 'Promo Celulares',
                'total_discount' => $totalDiscount,
                'item_discounts' => $itemDiscounts,
            ]);

            return response()->json([
                'success' => true,
                'promo_type' => 'cellphone',
                'promo_company_id' => 'cellphone_promo',
                'company_name' => 'Promo Celulares',
                'total_discount' => $totalDiscount,
                'item_discounts' => $itemDiscounts,
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). " Line:" . $e->getLine(). " Message:" . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __("messages.something_went_wrong"),
            ], 500);
        }
    }

    /**
     * Clear promo code from session.
     */
    public function clear(Request $request)
    {
        $request->session()->forget('promo_code');

        return response()->json([
            'success' => true,
            'message' => __('promocode::lang.promo_code_cleared'),
        ]);
    }

    /**
     * Get businesses for autocomplete (Select2).
     */
    public function getBusinesses(Request $request)
    {
        $term = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 10;

        $query = \DB::table('business')
            ->where('is_active', 1)
            ->select('id', 'name');

        if (!empty($term)) {
            $query->where('name', 'like', '%' . $term . '%');
        }

        $total = $query->count();
        $businesses = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = [];
        foreach ($businesses as $business) {
            $results[] = [
                'id' => $business->id,
                'text' => $business->name,
            ];
        }

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }
}
