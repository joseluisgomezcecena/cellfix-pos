<?php

namespace Modules\InventoryMultiLocation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'variation_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'quantity_received',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'quantity_received' => 'decimal:4'
    ];

    public function transfer()
    {
        return $this->belongsTo(InventoryTransfer::class, 'transfer_id');
    }

    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo('App\Variation', 'variation_id');
    }

    protected static function newFactory()
    {
        return \Modules\InventoryMultiLocation\Database\factories\InventoryTransferItemFactory::new();
    }
}
