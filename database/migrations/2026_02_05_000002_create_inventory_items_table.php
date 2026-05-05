<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Consolidated migration for inventory_items table.
     * Includes all columns: id, campus_id, inventory_type_id, name, description,
     * is_active, sku, barcode, qr_code, timestamps, softDeletes
     * (purchase_rate and sale_rate were removed later)
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('inventory_type_id')->constrained('inventory_types')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('sku', 100)->nullable()->unique()->comment('Stock Keeping Unit');
            $table->string('barcode', 100)->nullable()->comment('Barcode for scanning');
            $table->string('qr_code', 255)->nullable()->comment('QR Code identifier');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
