<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Technician extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function locations()
    {
        return $this->belongsToMany(BusinessLocation::class, 'technician_locations', 'technician_id', 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public static function forDropdown($business_id, $location_id = null, $only_active = true)
    {
        $query = self::where('business_id', $business_id);

        if ($only_active) {
            $query->active();
        }

        if (!empty($location_id)) {
            $query->whereHas('locations', function ($q) use ($location_id) {
                $q->where('business_locations.id', $location_id);
            });
        }

        return $query->orderBy('name')->pluck('name', 'id');
    }
}
