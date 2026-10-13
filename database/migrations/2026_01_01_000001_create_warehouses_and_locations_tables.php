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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->string('country', 100)->default('United States');
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->decimal('capacity_cbm', 12, 2)->default(0.00); // Cubic meters capacity
            $table->string('status', 20)->default('active'); // active, inactive, maintenance
            $table->timestamps();

            $table->index(['status', 'code']);
        });

        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('zone_type', 40)->default('storage'); // receiving, storage, cold_storage, picking, packing, dispatch
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
            $table->index(['warehouse_id', 'zone_type']);
        });

        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('warehouse_zone_id')->constrained('warehouse_zones')->cascadeOnDelete();
            $table->string('location_code', 50)->unique(); // e.g. WH01-ZN-A-R01-S02-B05
            $table->string('aisle', 20)->nullable();
            $table->string('rack', 20)->nullable();
            $table->string('shelf', 20)->nullable();
            $table->string('bin', 20)->nullable();
            $table->decimal('max_weight_kg', 10, 2)->default(1000.00);
            $table->decimal('max_volume_cbm', 10, 2)->default(2.50);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_occupied')->default(false);
            $table->string('barcode', 60)->nullable()->unique();
            $table->timestamps();

            $table->index(['warehouse_id', 'is_active', 'is_occupied']);
            $table->index(['warehouse_zone_id', 'aisle', 'rack']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_locations');
        Schema::dropIfExists('warehouse_zones');
        Schema::dropIfExists('warehouses');
    }
};
