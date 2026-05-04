<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for purchase_returns and purchase_return_items tables.
     * Includes all alterations:
     * - Original: campus_id, purchase_id, supplier_id, user_id, return_number, etc.
     * - Added: purchase_return_id (human-readable return ID)
     * - purchase_return_items: Added reason_id foreign key
     */
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_return_id')->nullable()->unique()->comment('Human-readable return ID (e.g., RET-2026-0001)');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('purchase_id')->nullable()->constrained('inventory_purchases')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('return_number')->comment('Unique return reference number');
            $table->date('return_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campus_id', 'return_date']);
            $table->index(['supplier_id', 'return_date']);
            $table->unique(['return_number', 'campus_id']);
            $table->index('purchase_return_id');
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('purchase_item_id')->nullable()->constrained('inventory_purchase_items')->onDelete('set null');
            $table->foreignId('reason_id')->nullable()->constrained('reasons')->onDelete('set null')->comment('Reason for return');
            $table->integer('quantity')->comment('Quantity being returned');
            $table->decimal('unit_price', 12, 2)->comment('Price per unit at time of return');
            $table->decimal('total', 12, 2)->comment('Total value of returned items');
            $table->json('item_snapshot')->nullable()->comment('Snapshot of item details at return time');
            $table->text('reason')->nullable()->comment('Reason for return (text)');
            $table->timestamps();

            $table->index(['purchase_return_id', 'inventory_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
