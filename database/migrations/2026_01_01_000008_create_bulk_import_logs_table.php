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
        Schema::create('bulk_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('import_type', 50); // products, inventory, locations, suppliers
            $table->string('file_name', 255);
            $table->unsignedInteger('file_size_kb')->default(0);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status', 30)->default('completed'); // completed, partial, failed
            $table->jsonb('error_log')->nullable(); // PostgreSQL JSONB column for detailed row-by-row error tracing
            $table->timestamps();

            $table->index(['import_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_import_logs');
    }
};
