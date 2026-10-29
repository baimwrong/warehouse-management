<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InboundOrder;
use App\Models\InventoryStock;
use App\Models\OutboundOrder;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $warehouse;
    protected WarehouseZone $zone;
    protected StorageLocation $location;
    protected Product $product;
    protected Supplier $supplier;
    protected Customer $customer;
    protected User $operator;
    protected User $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::create([
            'code' => 'WH-TEST-01',
            'name' => 'Main Test Warehouse',
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
            'location_code' => 'LOC-01-A1',
            'is_active' => true,
        ]);

        $category = Category::create([
            'code' => 'CAT-01',
            'name' => 'General',
        ]);

        $this->product = Product::create([
            'sku' => 'SKU-001',
            'name' => 'Test Widget',
            'category_id' => $category->id,
            'unit_cost' => 50.0,
            'unit_price' => 80.0,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-01',
            'name' => 'Acme Supplies',
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'code' => 'CUST-01',
            'name' => 'Global Logistics Inc',
            'status' => 'active',
        ]);

        $this->operator = User::create([
            'name' => 'Inbound Operator',
            'email' => 'operator@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->dispatcher = User::create([
            'name' => 'Outbound Dispatcher',
            'email' => 'dispatcher@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'dispatcher',
            'is_active' => true,
        ]);
    }

    public function test_inbound_receiving_increases_stock_and_updates_status(): void
    {
        $order = InboundOrder::create([
            'order_number' => 'PO-TEST-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'pending_receipt',
            'total_amount' => 500.0,
            'created_by' => $this->operator->id,
        ]);

        $item = $order->items()->create([
            'product_id' => $this->product->id,
            'quantity_expected' => 10,
            'quantity_received' => 0,
            'unit_cost' => 50.0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->operator)->post("/inbound/{$order->id}/receive", [
            'items' => [
                [
                    'item_id' => $item->id,
                    'received_qty' => 10,
                    'target_location_id' => $this->location->id,
                    'batch_number' => 'BATCH-X1',
                    'expiry_date' => Carbon::now()->addYear()->format('Y-m-d'),
                ]
            ]
        ]);

        $response->assertRedirect(route('inbound.show', $order));

        $order->refresh();
        $this->assertEquals('received', $order->status);

        $stock = InventoryStock::where('warehouse_id', $this->warehouse->id)
            ->where('storage_location_id', $this->location->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(10.0, (float) $stock->quantity_on_hand);
        $this->assertDatabaseHas('product_batches', [
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-X1',
        ]);
    }

    public function test_inbound_receiving_prevents_duplicate_receipt_on_received_order(): void
    {
        $order = InboundOrder::create([
            'order_number' => 'PO-TEST-002',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'received',
            'total_amount' => 500.0,
            'created_by' => $this->operator->id,
        ]);

        $item = $order->items()->create([
            'product_id' => $this->product->id,
            'quantity_expected' => 10,
            'quantity_received' => 10,
            'unit_cost' => 50.0,
            'status' => 'received',
        ]);

        $response = $this->actingAs($this->operator)->post("/inbound/{$order->id}/receive", [
            'items' => [
                [
                    'item_id' => $item->id,
                    'received_qty' => 10,
                    'target_location_id' => $this->location->id,
                ]
            ]
        ]);

        $response->assertSessionHasErrors(['error']);
    }

    public function test_outbound_pick_pack_and_ship_deducts_stock_and_updates_status(): void
    {
        // Put initial 15 units into stock
        app(InventoryService::class)->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->location->id,
            productId: $this->product->id,
            quantity: 15.0,
            unitCost: 50.0
        );

        $order = OutboundOrder::create([
            'order_number' => 'SO-TEST-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'confirmed',
            'total_amount' => 400.0,
            'created_by' => $this->dispatcher->id,
        ]);

        $item = $order->items()->create([
            'product_id' => $this->product->id,
            'quantity_ordered' => 5,
            'quantity_picked' => 0,
            'quantity_packed' => 0,
            'unit_price' => 80.0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->dispatcher)->post("/outbound/{$order->id}/pick-pack", [
            'action' => 'ship',
            'tracking_number' => 'TRACK-12345678',
            'items' => [
                [
                    'item_id' => $item->id,
                    'source_location_id' => (string) $this->location->id,
                    'picked_qty' => 5,
                ]
            ]
        ]);

        $response->assertRedirect(route('outbound.show', $order));

        $order->refresh();
        $this->assertEquals('shipped', $order->status);

        $stock = InventoryStock::where('storage_location_id', $this->location->id)->first();
        $this->assertEquals(10.0, (float) $stock->quantity_on_hand);
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => 'outbound',
            'reference_id' => $order->id,
            'quantity' => 5.0,
        ]);
    }

    public function test_outbound_prevents_double_deduction_on_shipped_order(): void
    {
        app(InventoryService::class)->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->location->id,
            productId: $this->product->id,
            quantity: 10.0,
            unitCost: 50.0
        );

        $order = OutboundOrder::create([
            'order_number' => 'SO-TEST-002',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'shipped',
            'total_amount' => 400.0,
            'created_by' => $this->dispatcher->id,
        ]);

        $item = $order->items()->create([
            'product_id' => $this->product->id,
            'quantity_ordered' => 5,
            'quantity_picked' => 5,
            'quantity_packed' => 5,
            'unit_price' => 80.0,
            'status' => 'shipped',
        ]);

        $response = $this->actingAs($this->dispatcher)->post("/outbound/{$order->id}/pick-pack", [
            'action' => 'ship',
            'items' => [
                [
                    'item_id' => $item->id,
                    'source_location_id' => (string) $this->location->id,
                    'picked_qty' => 5,
                ]
            ]
        ]);

        $response->assertSessionHasErrors(['error']);

        // Stock must still be 10, not deducted to 5
        $stock = InventoryStock::where('storage_location_id', $this->location->id)->first();
        $this->assertEquals(10.0, (float) $stock->quantity_on_hand);
    }

    public function test_packing_slip_and_grn_render_successfully(): void
    {
        $inbound = InboundOrder::create([
            'order_number' => 'PO-TEST-PRINT',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'received',
            'total_amount' => 500.0,
            'created_by' => $this->operator->id,
        ]);
        $inbound->items()->create([
            'product_id' => $this->product->id,
            'quantity_expected' => 10,
            'quantity_received' => 10,
            'unit_cost' => 50.0,
            'status' => 'received',
        ]);

        $outbound = OutboundOrder::create([
            'order_number' => 'SO-TEST-PRINT',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'confirmed',
            'total_amount' => 400.0,
            'created_by' => $this->dispatcher->id,
        ]);
        $outbound->items()->create([
            'product_id' => $this->product->id,
            'quantity_ordered' => 5,
            'unit_price' => 80.0,
            'status' => 'pending',
        ]);

        $grnResponse = $this->actingAs($this->operator)->get("/inbound/{$inbound->id}/print-grn");
        $grnResponse->assertStatus(200);
        $grnResponse->assertSee('Goods Receipt Note (GRN)');
        $grnResponse->assertSee('PO-TEST-PRINT');

        $slipResponse = $this->actingAs($this->dispatcher)->get("/outbound/{$outbound->id}/print-pack-slip");
        $slipResponse->assertStatus(200);
        $slipResponse->assertSee('Packing Slip &amp; BOL', false);
        $slipResponse->assertSee('SO-TEST-PRINT');
    }

    public function test_outbound_pick_pack_orders_by_fefo_expiry(): void
    {
        $batchExpiringSoon = ProductBatch::create([
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-EXP-SOON',
            'expiry_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ]);

        $batchExpiringLater = ProductBatch::create([
            'product_id' => $this->product->id,
            'batch_number' => 'BATCH-EXP-LATER',
            'expiry_date' => Carbon::now()->addDays(100)->format('Y-m-d'),
        ]);

        $locB = StorageLocation::create([
            'warehouse_id' => $this->warehouse->id,
            'warehouse_zone_id' => $this->zone->id,
            'location_code' => 'LOC-01-B1',
            'is_active' => true,
        ]);

        // Stock BATCH-EXP-LATER first
        app(InventoryService::class)->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->location->id,
            productId: $this->product->id,
            quantity: 10.0,
            batchId: $batchExpiringLater->id
        );

        // Stock BATCH-EXP-SOON second
        app(InventoryService::class)->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $locB->id,
            productId: $this->product->id,
            quantity: 10.0,
            batchId: $batchExpiringSoon->id
        );

        $order = OutboundOrder::create([
            'order_number' => 'SO-TEST-FEFO',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'confirmed',
            'total_amount' => 160.0,
            'created_by' => $this->dispatcher->id,
        ]);

        $item = $order->items()->create([
            'product_id' => $this->product->id,
            'quantity_ordered' => 2,
            'unit_price' => 80.0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->dispatcher)->get("/outbound/{$order->id}/pick-pack");
        $response->assertStatus(200);

        // First candidate stock should be the soonest expiring batch (FEFO)
        $pickLocations = $response->viewData('pickLocations');
        $this->assertNotEmpty($pickLocations[$item->id]);
        $firstStock = $pickLocations[$item->id]->first();
        $this->assertEquals($batchExpiringSoon->id, $firstStock->product_batch_id);
    }
}
