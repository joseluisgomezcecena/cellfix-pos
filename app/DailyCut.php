<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DailyCut extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'cut_date' => 'date',
        'summary' => 'array',
        'generated_at' => 'datetime',
        'total_sales' => 'float',
        'total_cash' => 'float',
        'total_card' => 'float',
        'total_transfer' => 'float',
        'total_cheque' => 'float',
        'total_other' => 'float',
        'total_expenses' => 'float',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
