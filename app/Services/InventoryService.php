<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Increase stock in a specific location (Inbound / Receipt / Initial Balance)
     */
    public function increaseStock(
        int $warehouseId,
        int $locationId,
        int $productId,
        float $quantity,
        ?int $batchId = null,
        string $referenceType = 'inbound',
        ?int $referenceId = null,
        ?float $unitCost = null,
        ?int $userId = null,
        ?string $notes = null
    ): InventoryStock {
        return DB::transaction(function () use (
            $warehouseId,
            $locationId,
            $productId,
            $quantity,
            $batchId,
            $referenceType,
            $referenceId,
            $unitCost,
            $userId,
            $notes
        ) {
            $stock = InventoryStock::where('warehouse_id', $warehouseId)
                ->where('storage_location_id', $locationId)
                ->where('product_id', $productId)
                ->where('product_batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                try {
                    $stock = InventoryStock::create([
                        'warehouse_id' => $warehouseId,
                        'storage_location_id' => $locationId,
                        'product_id' => $productId,
                        'product_batch_id' => $batchId,
                        'quantity_on_hand' => 0.0,
                        'quantity_reserved' => 0.0,
                    ]);
                } catch (\Exception $e) {
                    $stock = InventoryStock::where('warehouse_id', $warehouseId)
                        ->where('storage_location_id', $locationId)
                        ->where('product_id', $productId)
                        ->where('product_batch_id', $batchId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $stock->quantity_on_hand += $quantity;
            $stock->save();

            // Mark location as occupied
            StorageLocation::where('id', $locationId)->update(['is_occupied' => true]);

            // Record immutable audit movement
            StockMovement::create([
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'product_id' => $productId,
                'product_batch_id' => $batchId,
                'from_location_id' => null,
                'to_location_id' => $locationId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'user_id' => $userId,
                'notes' => $notes ?? "Stock increased via {$referenceType}",
            ]);

            return $stock;
        });
    }

    /**
     * Decrease stock from a specific location (Outbound / Dispatch / Waste)
     */
    public function decreaseStock(
        int $warehouseId,
        int $locationId,
        int $productId,
        float $quantity,
        ?int $batchId = null,
        string $referenceType = 'outbound',
        ?int $referenceId = null,
        ?float $unitCost = null,
        ?int $userId = null,
        ?string $notes = null
    ): InventoryStock {
        return DB::transaction(function () use (
            $warehouseId,
            $locationId,
            $productId,
            $quantity,
            $batchId,
            $referenceType,
            $referenceId,
            $unitCost,
            $userId,
            $notes
        ) {
            $stock = InventoryStock::where('warehouse_id', $warehouseId)
                ->where('storage_location_id', $locationId)
                ->where('product_id', $productId)
                ->where('product_batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if (!$stock || $stock->quantity_on_hand < $quantity) {
                $available = $stock ? $stock->quantity_on_hand : 0;
                throw new InvalidArgumentException("Insufficient stock in selected bin. Requested: {$quantity}, Available: {$available}");
            }

            $stock->quantity_on_hand -= $quantity;
            $stock->save();

            // Check if location is now empty
            $hasRemainingStock = InventoryStock::where('storage_location_id', $locationId)
                ->where('quantity_on_hand', '>', 0)
                ->exists();

            if (!$hasRemainingStock) {
                StorageLocation::where('id', $locationId)->update(['is_occupied' => false]);
            }

            // Record movement
            StockMovement::create([
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'product_id' => $productId,
                'product_batch_id' => $batchId,
                'from_location_id' => $locationId,
                'to_location_id' => null,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'user_id' => $userId,
                'notes' => $notes ?? "Stock decreased via {$referenceType}",
            ]);

            return $stock;
        });
    }

    /**
     * Internal Stock Transfer between two storage locations (bins)
     */
    public function transferStock(
        int $warehouseId,
        int $fromLocationId,
        int $toLocationId,
        int $productId,
        float $quantity,
        ?int $batchId = null,
        ?int $userId = null,
        ?string $notes = null
    ): void {
        if ($fromLocationId === $toLocationId) {
            throw new InvalidArgumentException("Source and destination storage bins cannot be the same.");
        }

        DB::transaction(function () use (
            $warehouseId,
            $fromLocationId,
            $toLocationId,
            $productId,
            $quantity,
            $batchId,
            $userId,
            $notes
        ) {
            $sourceStock = InventoryStock::where('warehouse_id', $warehouseId)
                ->where('storage_location_id', $fromLocationId)
                ->where('product_id', $productId)
                ->where('product_batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if (!$sourceStock || $sourceStock->quantity_on_hand < $quantity) {
                $available = $sourceStock ? $sourceStock->quantity_on_hand : 0;
                throw new InvalidArgumentException("Insufficient stock in source bin. Requested: {$quantity}, Available: {$available}");
            }

            // Deduct from source
            $sourceStock->quantity_on_hand -= $quantity;
            $sourceStock->save();

            // Add to destination
            $destStock = InventoryStock::where('warehouse_id', $warehouseId)
                ->where('storage_location_id', $toLocationId)
                ->where('product_id', $productId)
                ->where('product_batch_id', $batchId)
                ->lockForUpdate()
                ->first();

            if (!$destStock) {
                try {
                    $destStock = InventoryStock::create([
                        'warehouse_id' => $warehouseId,
                        'storage_location_id' => $toLocationId,
                        'product_id' => $productId,
                        'product_batch_id' => $batchId,
                        'quantity_on_hand' => 0.0,
                        'quantity_reserved' => 0.0,
                    ]);
                } catch (\Exception $e) {
                    $destStock = InventoryStock::where('warehouse_id', $warehouseId)
                        ->where('storage_location_id', $toLocationId)
                        ->where('product_id', $productId)
                        ->where('product_batch_id', $batchId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $destStock->quantity_on_hand += $quantity;
            $destStock->save();

            // Update occupancy
            $hasRemainingSource = InventoryStock::where('storage_location_id', $fromLocationId)
                ->where('quantity_on_hand', '>', 0)
                ->exists();

            if (!$hasRemainingSource) {
                StorageLocation::where('id', $fromLocationId)->update(['is_occupied' => false]);
            }

            StorageLocation::where('id', $toLocationId)->update(['is_occupied' => true]);

            // Record movement
            StockMovement::create([
                'reference_type' => 'transfer',
                'reference_id' => null,
                'product_id' => $productId,
                'product_batch_id' => $batchId,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'quantity' => $quantity,
                'unit_cost' => null,
                'user_id' => $userId,
                'notes' => $notes ?? "Internal transfer from bin #{$fromLocationId} to #{$toLocationId}",
            ]);
        });
    }
}
