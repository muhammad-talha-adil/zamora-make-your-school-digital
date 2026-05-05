<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Payment Allocations Migration
 *
 * Allocates one payment across one or multiple vouchers.
 * This is the bridge between payments and vouchers.
 *
 * Scenarios:
 * 1. Parent pays 5000 for one voucher → One allocation
 * 2. Parent pays 15000 for three vouchers → Three allocations
 * 3. Parent pays 3000 for 5000 voucher → Partial allocation
 * 4. Parent pays 6000 for 5000 voucher → Full allocation + 1000 to wallet
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payment_allocations', function (Blueprint $table) {
            $table->id();

            // Payment reference
            $table->foreignId('fee_payment_id')
                ->constrained('new_fee_payments')
                ->onDelete('cascade');

            // Voucher reference
            $table->foreignId('fee_voucher_id')
                ->constrained('fee_vouchers')
                ->onDelete('restrict');

            // Allocation details
            $table->decimal('allocated_amount', 12, 2); // Amount allocated to this voucher
            $table->date('allocation_date'); // When allocation was made

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['fee_payment_id', 'fee_voucher_id']);
            $table->index('fee_voucher_id');
            $table->index('allocation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payment_allocations');
    }
};
