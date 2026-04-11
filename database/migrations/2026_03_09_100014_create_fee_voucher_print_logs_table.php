<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Voucher Print Logs Migration
 * 
 * Tracks voucher/challan print history for audit purposes.
 * Useful for tracking who printed what and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_voucher_print_logs', function (Blueprint $table) {
            $table->id();
            
            // Voucher reference
            $table->foreignId('fee_voucher_id')
                ->constrained('fee_vouchers')
                ->onDelete('cascade');
            
            // Print details
            $table->foreignId('printed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->timestamp('printed_at');
            $table->unsignedInteger('print_count')->default(1);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['fee_voucher_id', 'printed_at']);
            $table->index('printed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_voucher_print_logs');
    }
};
