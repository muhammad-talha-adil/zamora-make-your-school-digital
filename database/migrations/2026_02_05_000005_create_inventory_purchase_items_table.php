<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Consolidated migration for inventory_purchase_items table.
     * Includes sale_rate column.
     */
    public function up(): void
    {
        Schema::create('inventory_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('inventory_purchases')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('purchase_rate', 10, 2);
            $table->decimal('sale_rate', 10, 2)->nullable()->comment('Sale rate for this item');
            $table->decimal('total', 10, 2);
            $table->json('item_snapshot')->nullable()->comment('Snapshot for audit trail');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_id', 'inventory_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_purchase_items');
    }
};
