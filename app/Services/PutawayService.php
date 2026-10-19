<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StorageLocation;
use Illuminate\Database\Eloquent\Collection;

class PutawayService
{
    /**
     * Suggest optimal bins in warehouse for receiving a specific product & quantity
     */
    public function suggestBins(int $warehouseId, int $productId, float $quantity, int $limit = 5): Collection
    {
        $product = Product::find($productId);
        $requiredVolume = $product ? (float) ($product->volume_cbm * $quantity) : 0.0;
        $requiredWeight = $product ? (float) ($product->weight_kg * $quantity) : 0.0;

        // 1. First priority: Vacant locations with sufficient physical dimensions
        $vacantLocations = StorageLocation::with('zone')
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->where('is_occupied', false)
            ->where('max_volume_cbm', '>=', $requiredVolume)
            ->where('max_weight_kg', '>=', $requiredWeight)
            ->whereHas('zone', function ($q) {
                $q->whereIn('zone_type', ['storage', 'receiving', 'cold_storage']);
            })
            ->orderBy('location_code')
            ->take($limit)
            ->get();

        if ($vacantLocations->count() >= $limit) {
            return $vacantLocations;
        }

        $needed = $limit - $vacantLocations->count();

        // 2. Second priority: Occupied locations with sufficient remaining capacity (single SQL query)
        $occupiedLocations = StorageLocation::with('zone')
            ->where('storage_locations.warehouse_id', $warehouseId)
            ->where('storage_locations.is_active', true)
            ->where('storage_locations.is_occupied', true)
            ->whereHas('zone', function ($q) {
                $q->whereIn('zone_type', ['storage', 'receiving', 'cold_storage']);
            })
            ->leftJoin('inventory_stocks', 'storage_locations.id', '=', 'inventory_stocks.storage_location_id')
            ->leftJoin('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->select('storage_locations.*')
            ->selectRaw('COALESCE(SUM(inventory_stocks.quantity_on_hand * products.volume_cbm), 0) as used_vol')
            ->selectRaw('COALESCE(SUM(inventory_stocks.quantity_on_hand * products.weight_kg), 0) as used_wt')
            ->groupBy('storage_locations.id')
            ->havingRaw('(storage_locations.max_volume_cbm - COALESCE(SUM(inventory_stocks.quantity_on_hand * products.volume_cbm), 0)) >= ?', [$requiredVolume])
            ->havingRaw('(storage_locations.max_weight_kg - COALESCE(SUM(inventory_stocks.quantity_on_hand * products.weight_kg), 0)) >= ?', [$requiredWeight])
            ->orderBy('location_code')
            ->take($needed)
            ->get();

        return $vacantLocations->concat($occupiedLocations);
    }
}
