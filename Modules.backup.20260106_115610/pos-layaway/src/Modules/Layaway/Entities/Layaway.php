<?php

namespace Modules\Layaway\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Contact;
use App\User;
use App\BusinessLocation;
use App\Business;
use App\Transaction;

class Layaway extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($layaway) {
            if (empty($layaway->layaway_number)) {
                $layaway->layaway_number = self::generateLayawayNumber($layaway->business_id);
            }
        });
    }

    protected $fillable = [
        'business_id',
        'contact_id',
        'business_location_id',
        'created_by',
        'layaway_number',
        'total_amount',
        'down_payment_percentage',
        'down_payment_amount',
        'balance_due',
        'payment_deadline',
        'status',
        'notes'
    ];

    protected $dates = [
        'payment_deadline',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'total_amount' => 'float',
        'down_payment_percentage' => 'float',
        'down_payment_amount' => 'float',
        'balance_due' => 'float',
    ];

    /**
     * Get the business that owns the layaway
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the customer contact for the layaway
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the business location for the layaway
     */
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    /**
     * Get the user who created the layaway
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the items for the layaway
     */
    public function items()
    {
        return $this->hasMany(LayawayItem::class);
    }

    /**
     * Get the payments for the layaway
     */
    public function payments()
    {
        return $this->hasMany(LayawayPayment::class);
    }

    /**
     * Get the associated transaction
     */
    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'layaway_id');
    }

    /**
     * Scope a query to only include active layaways
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include overdue layaways
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
                     ->where('payment_deadline', '<', now())
                     ->where('balance_due', '>', 0);
    }

    /**
     * Scope a query to only include completed layaways
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if layaway is overdue
     */
    public function isOverdue()
    {
        return $this->status == 'active'
               && $this->payment_deadline < now()
               && $this->balance_due > 0;
    }

    /**
     * Calculate total paid amount
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Generate unique layaway number with race condition protection
     */
    public static function generateLayawayNumber($business_id)
    {
        $prefix = 'LAY';
        $date = date('Ymd');
        $maxAttempts = 10;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // Use database transaction for atomicity
            $layawayNumber = \DB::transaction(function () use ($business_id, $prefix, $date) {
                // Lock the table to prevent race conditions
                $lastLayaway = self::where('business_id', $business_id)
                                   ->where('layaway_number', 'LIKE', $prefix . $date . '%')
                                   ->orderByRaw('CAST(SUBSTRING(layaway_number, -4) AS UNSIGNED) DESC')
                                   ->lockForUpdate()
                                   ->first();

                if ($lastLayaway) {
                    // Extract the sequence number from the last layaway number
                    $lastSequence = intval(substr($lastLayaway->layaway_number, -4));
                    $newSequence = $lastSequence + 1;
                } else {
                    $newSequence = 1;
                }

                $newLayawayNumber = $prefix . $date . str_pad($newSequence, 4, '0', STR_PAD_LEFT);

                // Double-check uniqueness before returning
                $exists = self::where('layaway_number', $newLayawayNumber)->exists();
                if ($exists) {
                    throw new \Exception('Layaway number already exists');
                }

                return $newLayawayNumber;
            });

            // If we successfully generated a unique number, return it
            if ($layawayNumber) {
                return $layawayNumber;
            }
        }

        // If all attempts failed, throw an exception
        throw new \Exception('Failed to generate unique layaway number after ' . $maxAttempts . ' attempts');
    }

    /**
     * Alternative method using atomic sequence generation
     * This method uses a dedicated sequence table for better performance
     */
    public static function generateLayawayNumberAtomic($business_id)
    {
        $prefix = 'LAY';
        $date = date('Ymd');

        return \DB::transaction(function () use ($business_id, $prefix, $date) {
            // Get or create sequence for today
            $sequenceKey = "layaway_{$business_id}_{$date}";

            // Use database-level atomic increment
            $sequence = \DB::table('sequences')
                          ->where('key', $sequenceKey)
                          ->lockForUpdate()
                          ->first();

            if ($sequence) {
                \DB::table('sequences')
                  ->where('key', $sequenceKey)
                  ->increment('value');
                $nextValue = $sequence->value + 1;
            } else {
                \DB::table('sequences')->insert([
                    'key' => $sequenceKey,
                    'value' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $nextValue = 1;
            }

            return $prefix . $date . str_pad($nextValue, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Update balance due after payment
     */
    public function updateBalance()
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->balance_due = $this->total_amount - $totalPaid;

        if ($this->balance_due <= 0) {
            $this->balance_due = 0;
            // Don't automatically set status here - let the controller handle it
        }

        $this->save();
    }
}