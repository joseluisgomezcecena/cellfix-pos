<?php

namespace Modules\AffiliateDiscount\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Contact;
use App\Business;
use App\User;

class AffiliatedBusiness extends Model
{
    protected $table = 'affiliated_businesses';

    protected $fillable = [
        'contact_id',
        'business_id',
        'contact_phone',
        'contact_email',
        'contract_number',
        'start_date',
        'end_date',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with Contact
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * Relationship with Business
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Relationship with User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with discount options
     */
    public function discountOptions()
    {
        return $this->hasMany(AffiliatedDiscountOption::class, 'affiliated_business_id');
    }

    /**
     * Get active discount options
     */
    public function activeDiscountOptions()
    {
        return $this->hasMany(AffiliatedDiscountOption::class, 'affiliated_business_id')
                    ->where('is_active', 1)
                    ->orderBy('display_order');
    }

    /**
     * Scope to get only active affiliated businesses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope to filter by business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Get business name
     */
    public function getBusinessNameAttribute()
    {
        return $this->contact ? $this->contact->name : 'Unknown';
    }

    /**
     * Get discount options grouped by category
     */
    public function getDiscountOptionsByCategory()
    {
        return $this->activeDiscountOptions()
                    ->get()
                    ->groupBy('category_name');
    }
}
