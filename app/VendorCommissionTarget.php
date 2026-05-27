<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorCommissionTarget extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'meta_units' => 'integer',
        'commission_per_unit' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }
}
