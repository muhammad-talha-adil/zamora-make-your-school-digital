<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates parent-child structure for student inventory returns:
     * - student_inventory_returns (parent table with return ID like SIR-2026-0001)
     * - student_inventory_return_items (child table with individual items)
     */
    public function up(): void
    {
        // Parent table: student_inventory_returns
        Schema::create('student_inventory_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_id')->unique(); // SIR-2026-0001 format
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('record_id')->constrained('student_inventory_records')->onDelete('cascade');
            $table->decimal('total_quantity', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->default('returned'); // returned, partial
            $table->date('return_date');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('return_id');
            $table->index(['campus_id', 'return_date']);
            $table->index(['student_id', 'return_date']);
        });

        // Child table: student_inventory_return_items
        Schema::create('student_inventory_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('student_inventory_returns')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('student_inventory_items')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('item_snapshot')->nullable(); // JSON snapshot of item at return time
            $table->timestamps();

            $table->index(['return_id', 'inventory_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_inventory_return_items');
        Schema::dropIfExists('student_inventory_returns');
    }
};
