<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockCorrection extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'float',
        'qty_before' => 'float',
        'qty_after' => 'float',
    ];

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
