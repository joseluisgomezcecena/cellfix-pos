<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RepairProductCommission extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'commission_amount' => 'float',
    ];
}
