<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InboundOrder;
use App\Models\OutboundOrder;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementAndOrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $operator;
    protected User $dispatcher;
    protected Warehouse $warehouse;
    protected StorageLocation $location;
    protected Product $product;
    protected Supplier $supplier;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->operator = User::create([
            'name' => 'Operator User',
            'email' => 'operator@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->dispatcher = User::create([
            'name' => 'Dispatcher User',
            'email' => 'dispatcher@wms.internal',
            'password' => Hash::make('password'),
            'role' => 'dispatcher',
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-TEST-CANCEL',
            'name' => 'Cancel Test Facility',
            'status' => 'active',
        ]);

        $zone = WarehouseZone::create([
            'warehouse_id' => $this->warehouse->id,
            'code' => 'ZN-01',
            'name' => 'Storage Zone',
        ]);

        $this->location = StorageLocation::create([
            'warehouse_id' => $this->warehouse->id,
            'warehouse_zone_id' => $zone->id,
            'location_code' => 'LOC-TEST-01',
            'is_active' => true,
        ]);

        $cat = Category::create(['code' => 'CAT-01', 'name' => 'Test Cat']);
        $this->product = Product::create([
            'sku' => 'SKU-CANCEL-01',
            'name' => 'Cancel Test Item',
            'category_id' => $cat->id,
            'unit_cost' => 10.0,
            'unit_price' => 20.0,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUPP-01',
            'name' => 'Test Supplier',
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'code' => 'CUST-01',
            'name' => 'Test Customer',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_manage_staff_users(): void
    {
        // 1. Admin accesses index
        $response = $this->actingAs($this->admin)->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('Staff &amp; User Administration', false);

        // 2. Admin creates a new staff member
        $postData = [
            'name' => 'New Forklift Operator',
            'email' => 'forklift@wms.internal',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role' => 'operator',
            'warehouse_id' => $this->warehouse->id,
            'phone' => '+1-555-019-9999',
            'is_active' => 1,
        ];

        $createResponse = $this->actingAs($this->admin)->post('/admin/users', $postData);
        $createResponse->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'forklift@wms.internal',
            'role' => 'operator',
        ]);

        $newUser = User::where('email', 'forklift@wms.internal')->first();

        // 3. Admin edits staff member
        $editResponse = $this->actingAs($this->admin)->put("/admin/users/{$newUser->id}", [
            'name' => 'Promoted Senior Operator',
            'email' => 'forklift@wms.internal',
            'role' => 'dispatcher',
            'is_active' => 1,
        ]);
        $editResponse->assertRedirect(route('admin.users.index'));
        $this->assertEquals('Promoted Senior Operator', $newUser->fresh()->name);
        $this->assertEquals('dispatcher', $newUser->fresh()->role);

        // 4. Admin toggles active status
        $toggleResponse = $this->actingAs($this->admin)->patch("/admin/users/{$newUser->id}/toggle");
        $toggleResponse->assertRedirect(route('admin.users.index'));
        $this->assertFalse((bool) $newUser->fresh()->is_active);
    }

    public function test_non_admins_forbidden_from_staff_management(): void
    {
        $response = $this->actingAs($this->manager)->get('/admin/users');
        $response->assertStatus(403);

        $responseOp = $this->actingAs($this->operator)->get('/admin/users');
        $responseOp->assertStatus(403);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $response = $this->actingAs($this->admin)->patch("/admin/users/{$this->admin->id}/toggle");
        $response->assertSessionHasErrors(['error']);
        $this->assertTrue((bool) $this->admin->fresh()->is_active);
    }

    public function test_inbound_order_cancellation_workflow(): void
    {
        $order = InboundOrder::create([
            'order_number' => 'PO-CANCEL-TEST',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'pending_receipt',
            'total_amount' => 100.0,
            'created_by' => $this->operator->id,
        ]);
        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity_expected' => 10,
            'unit_cost' => 10.0,
            'status' => 'pending',
        ]);

        // Manager cancels the pending PO
        $response = $this->actingAs($this->manager)->post("/inbound/{$order->id}/cancel");
        $response->assertRedirect(route('inbound.show', $order));
        $this->assertEquals('cancelled', $order->fresh()->status);

        // Already received order cannot be cancelled
        $receivedOrder = InboundOrder::create([
            'order_number' => 'PO-RECEIVED-TEST',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'received',
            'total_amount' => 100.0,
            'created_by' => $this->operator->id,
        ]);

        $failResponse = $this->actingAs($this->manager)->post("/inbound/{$receivedOrder->id}/cancel");
        $failResponse->assertSessionHasErrors(['error']);
    }

    public function test_outbound_order_cancellation_workflow_and_reservation_release(): void
    {
        // Put 15 units into stock
        app(InventoryService::class)->increaseStock(
            warehouseId: $this->warehouse->id,
            locationId: $this->location->id,
            productId: $this->product->id,
            quantity: 15.0
        );

        $order = OutboundOrder::create([
            'order_number' => 'SO-CANCEL-TEST',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'confirmed',
            'total_amount' => 100.0,
            'created_by' => $this->dispatcher->id,
        ]);
        $item = $order->items()->create([
            'product_id' => $this->product->id,
            'quantity_ordered' => 5,
            'unit_price' => 20.0,
            'source_location_id' => $this->location->id,
            'quantity_packed' => 5,
            'status' => 'packed',
        ]);

        // Dispatcher cancels order
        $response = $this->actingAs($this->dispatcher)->post("/outbound/{$order->id}/cancel");
        $response->assertRedirect(route('outbound.show', $order));
        $this->assertEquals('cancelled', $order->fresh()->status);

        // Already shipped order cannot be cancelled
        $shippedOrder = OutboundOrder::create([
            'order_number' => 'SO-SHIPPED-TEST',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'shipped',
            'total_amount' => 100.0,
            'created_by' => $this->dispatcher->id,
        ]);

        $failResponse = $this->actingAs($this->dispatcher)->post("/outbound/{$shippedOrder->id}/cancel");
        $failResponse->assertSessionHasErrors(['error']);
    }
}
