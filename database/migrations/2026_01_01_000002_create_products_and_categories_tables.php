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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 60)->unique();
            $table->string('barcode', 80)->unique()->nullable();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('uom', 20)->default('PCS'); // PCS, BOX, PALLET, KG, LTR, METER
            $table->decimal('min_stock_level', 12, 2)->default(10.00);
            $table->decimal('max_stock_level', 12, 2)->default(500.00);
            $table->decimal('reorder_point', 12, 2)->default(25.00);
            $table->decimal('unit_cost', 14, 4)->default(0.0000);
            $table->decimal('unit_price', 14, 4)->default(0.0000);
            $table->decimal('weight_kg', 10, 3)->default(0.000);
            $table->decimal('volume_cbm', 10, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'sku']);
            $table->index(['category_id', 'is_active']);
        });

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('batch_number', 80);
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'batch_number']);
            $table->index(['product_id', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
