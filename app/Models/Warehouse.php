<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'contact_email',
        'contact_phone',
        'capacity_cbm',
        'status',
    ];

    protected $casts = [
        'capacity_cbm' => 'decimal:2',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(WarehouseZone::class);
    }

    public function storageLocations(): HasMany
    {
        return $this->hasMany(StorageLocation::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inboundOrders(): HasMany
    {
        return $this->hasMany(InboundOrder::class);
    }

    public function outboundOrders(): HasMany
    {
        return $this->hasMany(OutboundOrder::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getOccupiedCapacityAttribute(): float
    {
        return (float) $this->inventoryStocks()
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(inventory_stocks.quantity_on_hand * products.volume_cbm) as total_volume')
            ->value('total_volume') ?? 0.0;
    }

    public function getCapacityUtilizationPercentAttribute(): float
    {
        if ($this->capacity_cbm <= 0) {
            return 0.0;
        }
        return min(100.0, round(($this->occupied_capacity / $this->capacity_cbm) * 100, 1));
    }
}
