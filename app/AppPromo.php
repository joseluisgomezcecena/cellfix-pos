<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppPromo extends Model
{
    protected $fillable = [
        'business_id', 'title', 'description', 'image_path',
        'starts_at', 'ends_at', 'target_location_id', 'category',
        'is_active', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'target_location_id');
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) return false;
        $today = now()->toDateString();
        if ($this->starts_at && $this->starts_at->toDateString() > $today) return false;
        if ($this->ends_at && $this->ends_at->toDateString() < $today) return false;
        return true;
    }
}
