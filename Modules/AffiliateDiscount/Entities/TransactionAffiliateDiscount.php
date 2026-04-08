<?php

namespace Modules\AffiliateDiscount\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Transaction;

class TransactionAffiliateDiscount extends Model
{
    protected $table = 'transaction_affiliate_discounts';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'affiliated_business_id',
        'discount_option_id',
        'category_name',
        'items_affected',
        'total_discount_applied',
    ];

    protected $casts = [
        'items_affected' => 'integer',
        'total_discount_applied' => 'decimal:4',
    ];

    /**
     * Relationship with Transaction
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Relationship with AffiliatedBusiness
     */
    public function affiliatedBusiness()
    {
        return $this->belongsTo(AffiliatedBusiness::class, 'affiliated_business_id');
    }

    /**
     * Relationship with DiscountOption
     */
    public function discountOption()
    {
        return $this->belongsTo(AffiliatedDiscountOption::class, 'discount_option_id');
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by affiliated business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('affiliated_business_id', $businessId);
    }

    /**
     * Scope to filter by category
     */
    public function scopeForCategory($query, $category)
    {
        return $query->where('category_name', $category);
    }
}
