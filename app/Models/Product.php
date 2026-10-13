<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'description',
        'category_id',
        'uom',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'unit_cost',
        'unit_price',
        'weight_kg',
        'volume_cbm',
        'is_active',
    ];

    protected $casts = [
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'weight_kg' => 'decimal:3',
        'volume_cbm' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function inboundOrderItems(): HasMany
    {
        return $this->hasMany(InboundOrderItem::class);
    }

    public function outboundOrderItems(): HasMany
    {
        return $this->hasMany(OutboundOrderItem::class);
    }

    public function getTotalQuantityOnHandAttribute(): float
    {
        return (float) $this->stocks()->sum('quantity_on_hand');
    }

    public function getTotalQuantityReservedAttribute(): float
    {
        return (float) $this->stocks()->sum('quantity_reserved');
    }

    public function getAvailableQuantityAttribute(): float
    {
        return max(0.0, $this->total_quantity_on_hand - $this->total_quantity_reserved);
    }

    public function getStockStatusAttribute(): string
    {
        $total = $this->total_quantity_on_hand;
        if ($total <= 0) {
            return 'out_of_stock';
        }
        if ($total <= $this->min_stock_level) {
            return 'critical_low';
        }
        if ($total <= $this->reorder_point) {
            return 'reorder_needed';
        }
        return 'in_stock';
    }

    public function getTotalValuationAttribute(): float
    {
        return (float) round($this->total_quantity_on_hand * $this->unit_cost, 2);
    }
}
