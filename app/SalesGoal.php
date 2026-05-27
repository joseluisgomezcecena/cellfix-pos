<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesGoal extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'target_qty' => 'integer',
        'target_amount' => 'float',
    ];
}
