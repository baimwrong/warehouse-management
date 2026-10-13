<?php

namespace Database\Seeders;

use App\Models\BulkImportLog;
use App\Models\Customer;
use App\Models\InboundOrder;
use App\Models\OutboundOrder;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH-CENTRAL')->first();
        $admin = User::where('role', 'admin')->first();
        $supplier = Supplier::first();
        $customer = Customer::first();
        $products = Product::take(4)->get();

        if (!$warehouse || !$admin || !$supplier || !$customer || $products->isEmpty()) {
            return;
        }

        // 1. Inbound Purchase Order (Pending Receipt)
        $inboundOrder = InboundOrder::updateOrCreate(
            ['order_number' => 'PO-20261010-001'],
            [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'pending_receipt',
                'expected_date' => Carbon::now()->addDays(3),
                'total_amount' => 12500.00,
                'notes' => 'Expedited restocking shipment for Q4 peak volume',
                'created_by' => $admin->id,
            ]
        );

        foreach ($products as $product) {
            $inboundOrder->items()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity_expected' => 20.0,
                    'quantity_received' => 0.0,
                    'unit_cost' => $product->unit_cost,
                    'status' => 'pending',
                ]
            );
        }

        // 2. Outbound Sales Order (Confirmed - Ready for Pick/Pack)
        $outboundOrder = OutboundOrder::updateOrCreate(
            ['order_number' => 'SO-20261010-001'],
            [
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'confirmed',
                'priority' => 'high',
                'shipping_carrier' => 'DHL Express Global',
                'scheduled_ship_date' => Carbon::now()->addDay(),
                'total_amount' => 8400.00,
                'notes' => 'Direct to hangar delivery. Call receiving dock 30 mins prior.',
                'created_by' => $admin->id,
            ]
        );

        foreach ($products->take(2) as $product) {
            $outboundOrder->items()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'quantity_ordered' => 4.0,
                    'quantity_picked' => 0.0,
                    'quantity_packed' => 0.0,
                    'unit_price' => $product->unit_price,
                    'status' => 'pending',
                ]
            );
        }

        // 3. Sample Stock Adjustment (Pending approval)
        $location = StorageLocation::where('warehouse_id', $warehouse->id)->first();
        if ($location) {
            $adj = StockAdjustment::updateOrCreate(
                ['adjustment_number' => 'ADJ-20261010-001'],
                [
                    'warehouse_id' => $warehouse->id,
                    'reason' => 'cycle_count',
                    'status' => 'pending',
                    'requested_by' => $admin->id,
                    'notes' => 'Monthly automated cycle count audit verification',
                ]
            );

            $adj->items()->updateOrCreate(
                [
                    'product_id' => $products->first()->id,
                    'storage_location_id' => $location->id,
                ],
                [
                    'previous_quantity' => 18.0,
                    'new_quantity' => 20.0,
                    'difference_quantity' => 2.0,
                ]
            );
        }

        // 4. Sample Bulk Import Log
        BulkImportLog::updateOrCreate(
            ['file_name' => 'initial_catalog_onboarding.csv'],
            [
                'user_id' => $admin->id,
                'import_type' => 'products',
                'file_size_kb' => 45,
                'total_rows' => 9,
                'successful_rows' => 9,
                'failed_rows' => 0,
                'status' => 'completed',
                'error_log' => [],
            ]
        );
    }
}
