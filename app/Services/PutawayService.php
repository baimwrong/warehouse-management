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
        $requiredVolume = $product ? ($product->volume_cbm * $quantity) : 0;
        $requiredWeight = $product ? ($product->weight_kg * $quantity) : 0;

        // Query active locations in this warehouse that have capacity
        return StorageLocation::with(['zone', 'stocks.product'])
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->whereHas('zone', function ($q) {
                $q->whereIn('zone_type', ['storage', 'receiving', 'cold_storage']);
            })
            ->get()
            ->filter(function (StorageLocation $loc) use ($requiredVolume, $requiredWeight) {
                // If unoccupied, it's a prime candidate
                if (!$loc->is_occupied) {
                    return true;
                }
                // If occupied, verify weight & volume headroom
                $availableVol = $loc->max_volume_cbm - $loc->current_volume;
                $availableWt = $loc->max_weight_kg - $loc->current_weight;

                return $availableVol >= $requiredVolume && $availableWt >= $requiredWeight;
            })
            ->take($limit);
    }
}
