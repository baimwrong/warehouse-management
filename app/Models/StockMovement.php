<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_type',
        'reference_id',
        'product_id',
        'product_batch_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'unit_cost',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    /**
     * Enforce audit ledger immutability: movements cannot be altered or purged
     */
    protected static function booted()
    {
        static::updating(function () {
            throw new \RuntimeException('StockMovement records are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new \RuntimeException('StockMovement records are immutable and cannot be deleted.');
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'to_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
