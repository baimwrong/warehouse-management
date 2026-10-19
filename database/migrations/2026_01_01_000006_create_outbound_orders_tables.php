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
        Schema::create('outbound_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('status', 30)->default('confirmed'); // draft, confirmed, picking, packed, shipped, delivered, cancelled
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
            $table->string('shipping_carrier', 100)->nullable(); // FedEx, DHL, UPS, Internal Fleet
            $table->string('tracking_number', 100)->nullable();
            $table->date('scheduled_ship_date')->nullable();
            $table->dateTime('shipped_date')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('outbound_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_order_id')->constrained('outbound_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_ordered', 14, 4);
            $table->decimal('quantity_picked', 14, 4)->default(0.0000);
            $table->decimal('quantity_packed', 14, 4)->default(0.0000);
            $table->decimal('unit_price', 14, 4)->default(0.0000);
            $table->foreignId('source_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('product_batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('status', 30)->default('pending'); // pending, picked, packed, shipped
            $table->timestamps();

            $table->index(['outbound_order_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_order_items');
        Schema::dropIfExists('outbound_orders');
    }
};
