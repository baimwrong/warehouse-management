<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'outbound_order_id',
        'product_id',
        'quantity_ordered',
        'quantity_picked',
        'quantity_packed',
        'unit_price',
        'source_location_id',
        'product_batch_id',
        'status',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:4',
        'quantity_picked' => 'decimal:4',
        'quantity_packed' => 'decimal:4',
        'unit_price' => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OutboundOrder::class, 'outbound_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'source_location_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }
}
