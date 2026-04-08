<?php

namespace Modules\InventoryMultiLocation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'reference_no',
        'from_location_id',
        'to_location_id',
        'created_by',
        'status',
        'notes',
        'total_amount',
        'transferred_at',
        'received_at'
    ];

    protected $dates = [
        'transferred_at',
        'received_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
        'transferred_at' => 'datetime',
        'received_at' => 'datetime'
    ];

    public function fromLocation()
    {
        return $this->belongsTo('App\BusinessLocation', 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo('App\BusinessLocation', 'to_location_id');
    }

    public function business()
    {
        return $this->belongsTo('App\Business', 'business_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryTransferItem::class, 'transfer_id');
    }

    protected static function newFactory()
    {
        return \Modules\InventoryMultiLocation\Database\factories\InventoryTransferFactory::new();
    }
}
