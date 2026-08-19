<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppBenefit extends Model
{
    protected $fillable = [
        'business_id', 'title', 'description',
        'value_type', 'value', 'value_text',
        'min_purchase', 'conditions', 'target_location_id',
        'is_active', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'target_location_id');
    }

    /**
     * Etiqueta visible del valor: "$100", "50%", o el texto libre.
     */
    public function displayValue(): string
    {
        return match ($this->value_type) {
            'amount'  => '$' . number_format((float) $this->value, 0),
            'percent' => rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.') . '%',
            'text'    => (string) $this->value_text,
            default   => '',
        };
    }
}
