<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StockAdjustmentApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse;
    protected WarehouseZone $zone;
    protected StorageLocation $location;
    protected Product $product;
    protected User $manager1;
    protected User $manager2;
    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::create([
            'code' => 'WH-ADJ-01',
            'name' => 'Adjustment Test Warehouse',
            'status' => 'active',
        ]);

        $this->zone = WarehouseZone::create([
            'warehouse_id' => $this->warehouse->id,
            'code' => 'ZN-01',
            'name' => 'Zone 1',
        ]);

        $this->location = StorageLocation::create([
            'warehouse_id' => $this->warehouse->id,
            'warehouse_zone_id' => $this->zone->id,
            'location_code' => 'LOC-ADJ-01',
            'is_active' => true,
        ]);

        $category = Category::create([
            'code' => 'CAT-01',
            'name' => 'Category 1',
        ]);

        $this->product = Product::create([
            'sku' => 'SKU-ADJ-01',
            'name' => 'Adjustment Product',
            'category_id' => $category->id,
            'unit_cost' => 100.0,
            'unit_price' => 150.0,
            'is_active' => true,
        ]);

        $this->manager1 = User::create([
            'name' => 'Manager One',
            'email' => 'm1@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->manager2 = User::create([
            'name' => 'Manager Two',
            'email' => 'm2@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->operator = User::create([
            'name' => 'Operator',
            'email' => 'op@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    public function test_requester_manager_cannot_approve_own_adjustment_request(): void
    {
        // Manager 1 creates an adjustment request
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-2026-0001',
            'warehouse_id' => $this->warehouse->id,
            'status' => 'pending',
            'reason' => 'Cycle Count Discrepancy',
            'requested_by' => $this->manager1->id,
        ]);

        $adjustment->items()->create([
            'product_id' => $this->product->id,
            'storage_location_id' => $this->location->id,
            'previous_quantity' => 10,
            'new_quantity' => 15,
            'difference_quantity' => 5,
        ]);

        // Manager 1 tries to approve their own request
        $response = $this->actingAs($this->manager1)->post("/adjustments/{$adjustment->id}/approve");

        $response->assertSessionHasErrors(['error']);
        $adjustment->refresh();
        $this->assertEquals('pending', $adjustment->status);
    }

    public function test_different_manager_can_approve_adjustment_and_reconciles_live_balance(): void
    {
        // Initial stock is 10
        app(InventoryService::class)->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->location->id,
            productId: $this->product->id,
            quantity: 10.0,
            unitCost: 100.0
        );

        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-2026-0002',
            'warehouse_id' => $this->warehouse->id,
            'status' => 'pending',
            'reason' => 'Annual Audit',
            'requested_by' => $this->manager1->id,
        ]);

        $item = $adjustment->items()->create([
            'product_id' => $this->product->id,
            'storage_location_id' => $this->location->id,
            'previous_quantity' => 10,
            'new_quantity' => 15, // Desired final stock is 15
            'difference_quantity' => 5,
        ]);

        // Suppose between request time and approval time, another 2 units arrived
        app(InventoryService::class)->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->location->id,
            productId: $this->product->id,
            quantity: 2.0,
            unitCost: 100.0
        );
        // Current on-hand is now 12!

        // Manager 2 approves the adjustment
        $response = $this->actingAs($this->manager2)->post("/adjustments/{$adjustment->id}/approve");

        $response->assertRedirect();

        $adjustment->refresh();
        $this->assertEquals('approved', $adjustment->status);
        $this->assertEquals($this->manager2->id, $adjustment->approved_by);

        // Verification of dynamic reconciliation:
        // Current on-hand was 12, target new_quantity is 15.
        // Difference applied must be +3, bringing total exactly to 15!
        $stock = InventoryStock::where('storage_location_id', $this->location->id)->first();
        $this->assertEquals(15.0, (float) $stock->quantity_on_hand);

        $item->refresh();
        $this->assertEquals(12.0, (float) $item->previous_quantity);
        $this->assertEquals(3.0, (float) $item->difference_quantity);
    }

    public function test_operator_is_forbidden_from_approving_adjustment(): void
    {
        $adjustment = StockAdjustment::create([
            'adjustment_number' => 'ADJ-2026-0003',
            'warehouse_id' => $this->warehouse->id,
            'status' => 'pending',
            'reason' => 'Cycle count',
            'requested_by' => $this->manager1->id,
        ]);

        $response = $this->actingAs($this->operator)->post("/adjustments/{$adjustment->id}/approve");
        $response->assertStatus(403);
    }
}
