<?php

namespace Modules\PromoCode\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoCategoryDiscount extends Model
{
    use HasFactory;

    protected $table = 'promo_category_discounts';

    protected $fillable = [
        'promo_company_id',
        'category_name',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
    ];

    /**
     * Get the promo company that owns the category discount.
     */
    public function promoCompany()
    {
        return $this->belongsTo(PromoCompany::class, 'promo_company_id');
    }

    /**
     * Calculate the discount amount for a given product price.
     */
    public function calculateDiscount($productPrice)
    {
        if ($this->discount_type === 'percentage') {
            return ($productPrice * $this->discount_value) / 100;
        }

        // Fixed discount
        return min($this->discount_value, $productPrice);
    }

    /**
     * Get discount categories from config.
     */
    public static function getAvailableCategories()
    {
        return config('promocode.categories', [
            'equipos' => 'Equipos',
            'pantallas' => 'Pantallas',
            'accesorios' => 'Accesorios',
            'desbloqueos' => 'Desbloqueos',
        ]);
    }

    /**
     * Get category mapping from config.
     */
    public static function getCategoryMapping()
    {
        return config('promocode.category_mapping', []);
    }

    /**
     * Map a product category name to a promo category.
     */
    public static function mapProductCategory($productCategoryName)
    {
        $mapping = static::getCategoryMapping();

        foreach ($mapping as $promoCategory => $productCategories) {
            if (in_array($productCategoryName, $productCategories)) {
                return $promoCategory;
            }
        }

        return null;
    }
}
