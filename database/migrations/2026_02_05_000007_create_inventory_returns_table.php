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
        Schema::create('inventory_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('student_inventory_id')->constrained('student_inventory')->onDelete('cascade');
            $table->integer('quantity');
            $table->date('return_date');
            $table->text('note')->nullable();
            $table->json('item_snapshot')->nullable();
            $table->string('item_name_snapshot')->nullable();
            $table->text('description_snapshot')->nullable();
            $table->decimal('unit_price_snapshot', 10, 2)->nullable();
            $table->json('discount_snapshot')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // IMPROVEMENT: Indexes for performance
            $table->index(['campus_id', 'return_date']);
            $table->index(['student_inventory_id', 'return_date']);
            $table->index(['campus_id', 'student_inventory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_returns');
    }
};
