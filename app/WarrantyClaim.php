<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'claim_date' => 'datetime',
        'refund_denomination_breakdown' => 'array',
        'price_difference_denomination_breakdown' => 'array',
    ];

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function originalSell()
    {
        return $this->belongsTo(\App\Transaction::class, 'original_sell_transaction_id');
    }

    public function expenseTransaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'expense_transaction_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'payment_transaction_id');
    }

    public static function claimTypeLabel($type)
    {
        return [
            'refund' => 'Reembolso en efectivo/tarjeta',
            'replacement_same' => 'Cambio por mismo equipo (garantía)',
            'replacement_higher' => 'Cambio por equipo de mayor valor',
            'replacement_lower' => 'Cambio por equipo de menor valor',
        ][$type] ?? $type;
    }
}
