<?php

namespace Modules\PromoCode\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoCompany extends Model
{
    use HasFactory;

    protected $table = 'promo_companies';

    protected $fillable = [
        'business_id',
        'company_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the business that owns the promo company.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Get the category discounts for the promo company.
     */
    public function categoryDiscounts()
    {
        return $this->hasMany(PromoCategoryDiscount::class, 'promo_company_id');
    }

    /**
     * Get the usage records for the promo company.
     */
    public function usageRecords()
    {
        return $this->hasMany(PromoCodeUsage::class, 'promo_company_id');
    }

    /**
     * Scope a query to only include active promo companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Find a promo company by exact company name (case-insensitive).
     */
    public static function findByCompanyName($companyName)
    {
        return static::where('company_name', $companyName)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Get the discount for a specific category.
     */
    public function getDiscountForCategory($categoryName)
    {
        return $this->categoryDiscounts()
            ->where('category_name', $categoryName)
            ->first();
    }
}
