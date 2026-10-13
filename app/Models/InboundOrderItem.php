<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_order_id',
        'product_id',
        'quantity_expected',
        'quantity_received',
        'unit_cost',
        'target_location_id',
        'batch_number',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'quantity_expected' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(InboundOrder::class, 'inbound_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function targetLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'target_location_id');
    }
}
