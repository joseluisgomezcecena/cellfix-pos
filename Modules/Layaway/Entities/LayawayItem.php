<?php

namespace Modules\Layaway\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Product;
use App\Variation;

class LayawayItem extends Model
{
    protected $fillable = [
        'layaway_id',
        'product_id',
        'variation_id',
        'quantity',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    /**
     * Get the layaway that owns the item
     */
    public function layaway()
    {
        return $this->belongsTo(Layaway::class);
    }

    /**
     * Get the product for the item
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variation for the item
     */
    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    /**
     * Get product name with variation
     */
    public function getFullNameAttribute()
    {
        $name = $this->product ? $this->product->name : '';

        if ($this->variation) {
            // Only add variation info if it's not a dummy variation
            $variation_name = (!empty($this->variation->name) && $this->variation->name != 'DUMMY') ? $this->variation->name : '';
            $product_variation_name = (!empty($this->variation->product_variation->name) && $this->variation->product_variation->name != 'DUMMY') ? $this->variation->product_variation->name : '';

            if (!empty($product_variation_name)) {
                $name .= ' - ' . $product_variation_name;
            }

            if (!empty($variation_name)) {
                $name .= ' - ' . $variation_name;
            }
        }

        return $name;
    }

    /**
     * Calculate line total
     */
    public function calculateTotal()
    {
        $this->total_price = $this->quantity * $this->unit_price;
        return $this->total_price;
    }
}