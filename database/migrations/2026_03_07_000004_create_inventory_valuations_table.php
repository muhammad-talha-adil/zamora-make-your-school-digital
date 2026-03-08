<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates inventory_valuations table for tracking inventory worth.
     */
    public function up(): void
    {
        Schema::create('inventory_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->date('valuation_date')->comment('Date of valuation');
            $table->integer('quantity')->comment('Quantity at time of valuation');
            $table->decimal('unit_cost', 10, 2)->comment('Unit cost (weighted average)');
            $table->decimal('total_value', 15, 2)->comment('Total value (quantity × unit_cost)');
            $table->string('valuation_method')->default('weighted_average')->comment('Valuation method: weighted_average, fifo, lifo');
            $table->string('reference_type')->nullable()->comment('Reference type (purchase, adjustment, etc.)');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('Reference ID');
            $table->text('note')->nullable()->comment('Notes');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['campus_id', 'valuation_date']);
            $table->index(['inventory_item_id', 'valuation_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_valuations');
    }
};
