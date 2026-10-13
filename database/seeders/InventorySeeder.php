<?php

namespace Database\Seeders;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH-CENTRAL')->first();
        if (!$warehouse) {
            return;
        }

        $products = Product::with('batches')->get();
        $locations = StorageLocation::where('warehouse_id', $warehouse->id)->get();

        $locIndex = 0;
        foreach ($products as $index => $product) {
            if ($locIndex >= $locations->count()) {
                break;
            }

            $location = $locations[$locIndex];
            $batch = $product->batches->first();

            $initialQty = match ($product->sku) {
                'ELEC-DRN-001' => 18.0,
                'ELEC-LIDAR-002' => 45.0,
                'ELEC-BAT-003' => 22.0,
                'TOOL-WRN-101' => 85.0,
                'TOOL-CAL-102' => 38.0,
                'SAFE-RESP-201' => 14.0,
                'SAFE-FAK-202' => 50.0,
                'COLD-VAC-301' => 60.0,
                'FLTR-HEPA-401' => 40.0,
                default => 25.0,
            };

            $stock = InventoryStock::updateOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'storage_location_id' => $location->id,
                    'product_id' => $product->id,
                    'product_batch_id' => $batch?->id,
                ],
                [
                    'quantity_on_hand' => $initialQty,
                    'quantity_reserved' => 0.0,
                ]
            );

            // Mark bin as occupied
            $location->update(['is_occupied' => true]);

            // Record initial movement in ledger
            StockMovement::updateOrCreate(
                [
                    'reference_type' => 'initial_balance',
                    'product_id' => $product->id,
                    'to_location_id' => $location->id,
                ],
                [
                    'quantity' => $initialQty,
                    'unit_cost' => $product->unit_cost,
                    'notes' => 'System launch initial inventory onboarding balance',
                ]
            );

            $locIndex++;
        }
    }
}
