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
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['add', 'subtract', 'set'])->comment('add: increase stock, subtract: decrease stock, set: set to specific quantity');
            $table->integer('quantity')->comment('For add/subtract: quantity to add/subtract. For set: new quantity value.');
            $table->integer('previous_quantity')->nullable()->comment('Quantity before adjustment');
            $table->integer('new_quantity')->nullable()->comment('Quantity after adjustment');
            $table->string('reason', 500);
            $table->string('reference_number')->nullable()->comment('Optional reference (e.g., stock count ID, damage report ID)');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campus_id', 'inventory_item_id']);
            $table->index(['campus_id', 'created_at']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
