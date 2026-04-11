<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Voucher Items Migration
 * 
 * Line items breakdown for each voucher. Shows exactly what the student
 * is being charged for in this voucher.
 * 
 * Example Voucher Breakdown:
 * Voucher #FV-2024-001234 for September 2024:
 *   - Tuition Fee: 5000 PKR
 *   - Computer Fee: 500 PKR
 *   - Transport Fee: 1500 PKR
 *   - Late Payment Fine: 200 PKR
 *   - Sibling Discount: -500 PKR
 *   Total: 6700 PKR
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_voucher_items', function (Blueprint $table) {
            $table->id();
            
            // Parent voucher
            $table->foreignId('fee_voucher_id')
                ->constrained('fee_vouchers')
                ->onDelete('cascade');
            
            // Fee head reference (nullable for custom items)
            $table->foreignId('fee_head_id')
                ->nullable()
                ->constrained('fee_heads')
                ->onDelete('restrict');
            
            // Item details
            $table->string('description', 200); // Human-readable description
            $table->decimal('amount', 12, 2); // Base amount
            $table->decimal('discount_amount', 12, 2)->default(0); // Discount on this item
            $table->decimal('fine_amount', 12, 2)->default(0); // Fine on this item
            $table->decimal('adjusted_amount', 12, 2)->default(0); // Other adjustments
            $table->decimal('net_amount', 12, 2); // Final amount for this item
            
            // Source tracking (where did this item come from?)
            $table->enum('source_type', [
                'structure',           // From fee structure
                'override',            // From student fee assignment
                'manual',              // Manually added
                'arrears',             // Previous balance
                'advance_adjustment'   // Advance payment adjustment
            ])->index();
            
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of source record
            
            $table->timestamps();
            
            // Indexes (with custom short names)
            $table->index(['fee_voucher_id', 'fee_head_id'], 'idx_voucher_item_voucher_head');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_voucher_items');
    }
};
