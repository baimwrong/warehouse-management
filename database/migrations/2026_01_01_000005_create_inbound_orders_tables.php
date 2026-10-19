<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inbound_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('status', 30)->default('pending_receipt'); // draft, pending_receipt, partially_received, received, cancelled
            $table->date('expected_date')->nullable();
            $table->dateTime('received_date')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['supplier_id', 'created_at']);
        });

        Schema::create('inbound_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_order_id')->constrained('inbound_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_expected', 14, 4);
            $table->decimal('quantity_received', 14, 4)->default(0.0000);
            $table->decimal('unit_cost', 14, 4)->default(0.0000);
            $table->foreignId('target_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->string('batch_number', 80)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 30)->default('pending'); // pending, received, partial
            $table->timestamps();

            $table->index(['inbound_order_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_order_items');
        Schema::dropIfExists('inbound_orders');
    }
};
