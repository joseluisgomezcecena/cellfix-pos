<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CardTerminal extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public static function forDropdown($business_id, $only_active = true)
    {
        $query = self::where('business_id', $business_id);
        if ($only_active) {
            $query->active();
        }

        return $query->orderBy('name')->pluck('name', 'id');
    }
}
