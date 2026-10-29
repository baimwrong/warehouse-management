<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'warehouse_zone_id',
        'location_code',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'max_weight_kg',
        'max_volume_cbm',
        'is_active',
        'is_occupied',
        'barcode',
    ];

    protected $casts = [
        'max_weight_kg' => 'decimal:2',
        'max_volume_cbm' => 'decimal:2',
        'is_active' => 'boolean',
        'is_occupied' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'warehouse_zone_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function getCurrentVolumeAttribute(): float
    {
        if ($this->relationLoaded('stocks')) {
            return (float) $this->stocks->sum(function ($stock) {
                return $stock->quantity_on_hand * ($stock->product->volume_cbm ?? 0);
            });
        }

        return (float) $this->stocks()
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(inventory_stocks.quantity_on_hand * products.volume_cbm) as total_volume')
            ->value('total_volume') ?? 0.0;
    }

    public function getCurrentWeightAttribute(): float
    {
        if ($this->relationLoaded('stocks')) {
            return (float) $this->stocks->sum(function ($stock) {
                return $stock->quantity_on_hand * ($stock->product->weight_kg ?? 0);
            });
        }

        return (float) $this->stocks()
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(inventory_stocks.quantity_on_hand * products.weight_kg) as total_weight')
            ->value('total_weight') ?? 0.0;
    }
}
