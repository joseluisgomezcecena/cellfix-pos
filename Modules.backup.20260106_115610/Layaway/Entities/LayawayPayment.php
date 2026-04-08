<?php

namespace Modules\Layaway\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\CashRegister;
use App\Transaction;
use App\TransactionPayment;

class LayawayPayment extends Model
{
    protected $fillable = [
        'layaway_id',
        'amount',
        'payment_method',
        'payment_date',
        'processed_by',
        'cash_register_id',
        'transaction_payment_id',
        'notes'
    ];

    protected $dates = [
        'payment_date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    /**
     * Get the layaway that owns the payment
     */
    public function layaway()
    {
        return $this->belongsTo(Layaway::class);
    }

    /**
     * Get the user who processed the payment
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the cash register used for payment
     */
    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * Get the associated transaction payment
     */
    public function transactionPayment()
    {
        return $this->belongsTo(TransactionPayment::class, 'transaction_payment_id');
    }

    /**
     * Scope a query to only include payments for a specific period
     */
    public function scopeForPeriod($query, $start_date, $end_date)
    {
        return $query->whereBetween('payment_date', [$start_date, $end_date]);
    }

    /**
     * Scope a query to only include payments by method
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Get formatted payment method
     */
    public function getFormattedMethodAttribute()
    {
        $methods = [
            'cash' => 'Cash',
            'card' => 'Credit/Debit Card',
            'cheque' => 'Cheque',
            'bank_transfer' => 'Bank Transfer',
            'other' => 'Other'
        ];

        return isset($methods[$this->payment_method]) ? $methods[$this->payment_method] : $this->payment_method;
    }
}