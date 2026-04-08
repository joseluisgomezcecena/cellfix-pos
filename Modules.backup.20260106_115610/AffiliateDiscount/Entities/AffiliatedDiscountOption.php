<?php

namespace Modules\AffiliateDiscount\Entities;

use Illuminate\Database\Eloquent\Model;

class AffiliatedDiscountOption extends Model
{
    protected $table = 'affiliated_discount_options';

    protected $fillable = [
        'affiliated_business_id',
        'category_name',
        'discount_type',
        'discount_value',
        'discount_label',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'discount_value' => 'decimal:2',
        'display_order' => 'integer',
    ];

    /**
     * Relationship with AffiliatedBusiness
     */
    public function affiliatedBusiness()
    {
        return $this->belongsTo(AffiliatedBusiness::class, 'affiliated_business_id');
    }

    /**
     * Scope to get only active options
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope to filter by category
     */
    public function scopeForCategory($query, $category)
    {
        return $query->where('category_name', $category);
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Calculate discount amount for a given subtotal
     */
    public function calculateDiscount($subtotal)
    {
        if ($this->discount_type === 'fixed') {
            return min($this->discount_value, $subtotal);
        } elseif ($this->discount_type === 'percentage') {
            return ($subtotal * $this->discount_value) / 100;
        }

        return 0;
    }

    /**
     * Get formatted discount label for display
     */
    public function getFormattedLabelAttribute()
    {
        if ($this->discount_label) {
            return $this->discount_label;
        }

        if ($this->discount_type === 'fixed') {
            return '$' . number_format($this->discount_value, 0) . ' PESOS';
        } else {
            return number_format($this->discount_value, 0) . '% DESC.';
        }
    }
}
