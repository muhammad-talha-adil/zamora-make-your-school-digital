<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Voucher Adjustments Migration
 * 
 * Voucher-level adjustments like arrears, advance payments, waivers,
 * fine reversals, and manual charges.
 * 
 * Use Cases:
 * - Arrears: Student owes 2000 from previous months, add to current voucher
 * - Advance: Student paid 5000 in advance, adjust against current voucher
 * - Waiver: Principal approves 1000 waiver for hardship case
 * - Fine Reversal: Remove late fee due to valid excuse
 * - Manual Charge: Add special charge not in fee structure
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_voucher_adjustments', function (Blueprint $table) {
            $table->id();
            
            // Parent voucher
            $table->foreignId('fee_voucher_id')
                ->constrained('fee_vouchers')
                ->onDelete('cascade');
            
            // Adjustment type
            $table->enum('adjustment_type', [
                'arrears',
                'advance',
                'waiver',
                'fine_reversal',
                'manual_charge'
            ])->index();
            
            // Amount (positive or negative)
            $table->decimal('amount', 12, 2);
            
            // Description
            $table->text('description')->nullable();
            
            // Related voucher (for arrears/advance from another voucher)
            $table->foreignId('related_voucher_id')
                ->nullable()
                ->constrained('fee_vouchers')
                ->onDelete('restrict');
            
            // Who created this adjustment
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['fee_voucher_id', 'adjustment_type']);
            $table->index('related_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_voucher_adjustments');
    }
};
