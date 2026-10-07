<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SaleSource extends Model
{
    protected $fillable = ['business_id', 'name', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
