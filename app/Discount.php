<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function variations()
    {
        return $this->belongsToMany(\App\Variation::class, 'discount_variations', 'discount_id', 'variation_id');
    }

    public function locations()
    {
        return $this->belongsToMany(\App\BusinessLocation::class, 'discount_locations', 'discount_id', 'location_id');
    }
}
