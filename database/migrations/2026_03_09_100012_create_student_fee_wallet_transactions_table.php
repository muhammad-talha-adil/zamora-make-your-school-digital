<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student Fee Wallet Transactions Migration
 * 
 * Advance fee / credit wallet ledger for students.
 * Tracks advance payments and their usage.
 * 
 * Use Cases:
 * - Parent pays 50,000 in advance at start of year
 * - Each month, voucher amount is deducted from wallet
 * - Wallet balance can be refunded when student leaves
 * - Manual credits/debits for corrections
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_wallet_transactions', function (Blueprint $table) {
            $table->id();
            
            // Student reference
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            
            // Transaction details
            $table->date('transaction_date');
            
            $table->enum('transaction_type', [
                'advance_deposit',
                'voucher_adjustment',
                'refund',
                'manual_credit',
                'manual_debit'
            ])->index();
            
            $table->enum('direction', ['credit', 'debit'])->index();
            
            $table->decimal('amount', 12, 2);
            
            // Reference to related record
            $table->string('reference_type', 100)->nullable(); // e.g., 'fee_payment', 'fee_voucher'
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Description
            $table->text('description')->nullable();
            
            // Who created this transaction
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes (with custom short names)
            $table->index(['student_id', 'transaction_date'], 'idx_wallet_txn_student_date');
            $table->index(['transaction_type', 'direction'], 'idx_wallet_txn_type_dir');
            $table->index(['reference_type', 'reference_id'], 'idx_wallet_txn_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_wallet_transactions');
    }
};
