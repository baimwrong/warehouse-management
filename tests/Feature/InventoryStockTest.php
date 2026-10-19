<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use App\Services\InventoryService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryStockTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;
    protected Warehouse $warehouse;
    protected WarehouseZone $zone;
    protected StorageLocation $locA;
    protected StorageLocation $locB;
    protected Category $category;
    protected Product $product;
    protected ProductBatch $batch;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-TEST-01',
            'name' => 'Main Test Warehouse',
            'status' => 'active',
        ]);

        $this->zone = WarehouseZone::create([
            'warehouse_id' => $this->warehouse->id,
            'code' => 'ZN-STORAGE',
            'name' => 'Storage Area',
            'zone_type' => 'storage',
        ]);

        $this->locA = StorageLocation::create([
            'warehouse_id' => $this->warehouse->id,
            'warehouse_zone_id' => $this->zone->id,
            'location_code' => 'WH01-ZN-A-R01-S01-B01',
            'is_active' => true,
        ]);

        $this->locB = StorageLocation::create([
            'warehouse_id' => $this->warehouse->id,
            'warehouse_zone_id' => $this->zone->id,
            'location_code' => 'WH01-ZN-A-R01-S01-B02',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'code' => 'CAT-ELEC',
            'name' => 'Electronics',
        ]);

        $this->product = Product::create([
            'sku' => 'SKU-DRONE-01',
            'name' => 'Surveillance Drone',
            'category_id' => $this->category->id,
            'unit_cost' => 500.0,
            'unit_price' => 750.0,
            'min_stock_level' => 5,
            'max_stock_level' => 100,
            'is_active' => true,
        ]);

        $this->batch = ProductBatch::create([
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-2026-001',
        ]);

        $this->operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    public function test_unique_constraint_on_inventory_stocks_prevents_duplicate_records(): void
    {
        InventoryStock::create([
            'warehouse_id' => $this->warehouse->id,
            'storage_location_id' => $this->locA->id,
            'product_id' => $this->product->id,
            'product_batch_id' => $this->batch->id,
            'quantity_on_hand' => 10,
        ]);

        $this->expectException(QueryException::class);

        InventoryStock::create([
            'warehouse_id' => $this->warehouse->id,
            'storage_location_id' => $this->locA->id,
            'product_id' => $this->product->id,
            'product_batch_id' => $this->batch->id,
            'quantity_on_hand' => 20,
        ]);
    }

    public function test_increase_stock_updates_quantity_and_records_movement(): void
    {
        // First inbound receipt
        $this->inventoryService->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->locA->id,
            productId: $this->product->id,
            quantity: 10.0,
            batchId: $this->batch->id,
            referenceType: 'inbound',
            userId: $this->operator->id
        );

        $stock = InventoryStock::where('warehouse_id', $this->warehouse->id)
            ->where('storage_location_id', $this->locA->id)
            ->where('product_id', $this->product->id)
            ->where('product_batch_id', $this->batch->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(10.0, (float) $stock->quantity_on_hand);

        // Second inbound receipt should increment the same row
        $this->inventoryService->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->locA->id,
            productId: $this->product->id,
            quantity: 15.0,
            batchId: $this->batch->id,
            referenceType: 'inbound',
            userId: $this->operator->id
        );

        $this->assertEquals(1, InventoryStock::where('storage_location_id', $this->locA->id)->count());
        $stock->refresh();
        $this->assertEquals(25.0, (float) $stock->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_decrease_stock_deducts_and_prevents_negative_inventory(): void
    {
        $this->inventoryService->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->locA->id,
            productId: $this->product->id,
            quantity: 20.0,
            batchId: $this->batch->id,
        );

        // Decrease 5
        $this->inventoryService->decreaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->locA->id,
            productId: $this->product->id,
            quantity: 5.0,
            batchId: $this->batch->id,
        );

        $stock = InventoryStock::where('storage_location_id', $this->locA->id)->first();
        $this->assertEquals(15.0, (float) $stock->quantity_on_hand);

        // Attempting to decrease more than on-hand throws exception
        $this->expectException(InvalidArgumentException::class);
        $this->inventoryService->decreaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->locA->id,
            productId: $this->product->id,
            quantity: 50.0,
            batchId: $this->batch->id,
        );
    }

    public function test_transfer_stock_between_bins_is_atomic(): void
    {
        $this->inventoryService->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->locA->id,
            productId: $this->product->id,
            quantity: 20.0,
            batchId: $this->batch->id,
        );

        // Transfer 8 units from LocA to LocB
        $this->inventoryService->transferStock(
            warehouseId: $this->warehouse->id,
            fromLocationId: $this->locA->id,
            toLocationId: $this->locB->id,
            productId: $this->product->id,
            quantity: 8.0,
            batchId: $this->batch->id,
            userId: $this->operator->id
        );

        $stockA = InventoryStock::where('storage_location_id', $this->locA->id)->first();
        $stockB = InventoryStock::where('storage_location_id', $this->locB->id)->first();

        $this->assertEquals(12.0, (float) $stockA->quantity_on_hand);
        $this->assertEquals(8.0, (float) $stockB->quantity_on_hand);
    }

    public function test_transfer_fails_if_bins_belong_to_different_warehouses(): void
    {
        $warehouse2 = Warehouse::create([
            'code' => 'WH-TEST-02',
            'name' => 'Secondary Warehouse',
            'status' => 'active',
        ]);
        $zone2 = WarehouseZone::create([
            'warehouse_id' => $warehouse2->id,
            'code' => 'ZN-WH2',
            'name' => 'WH2 Zone',
        ]);
        $locOther = StorageLocation::create([
            'warehouse_id' => $warehouse2->id,
            'warehouse_zone_id' => $zone2->id,
            'location_code' => 'WH02-ZN-A-R01-S01-B01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->operator)->post('/movements/transfer', [
            'warehouse_id' => $this->warehouse->id,
            'from_location_id' => $this->locA->id,
            'to_location_id' => $locOther->id, // Location belongs to WH2, not WH1
            'product_id' => $this->product->id,
            'product_batch_id' => $this->batch->id,
            'quantity' => 5,
        ]);

        $response->assertSessionHasErrors(['to_location_id']);
    }
}
