<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Payments Migration
 * 
 * Payment receipt header. Records actual money received from students.
 * One payment can be allocated across multiple vouchers.
 * 
 * Pakistani School Context:
 * - Parents pay via cash, bank transfer, JazzCash, EasyPaisa, or cheque
 * - Payment may cover one voucher, multiple vouchers, or partial voucher
 * - Excess payment goes to student's advance wallet
 * - Each payment gets a unique receipt number
 * 
 * IMPORTANT: This is the consolidated migration combining:
 * - 2026_03_09_100010_create_new_fee_payments_table.php (original)
 * - 2026_03_09_100015_add_campus_id_to_fee_payments_table.php (alter)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_fee_payments', function (Blueprint $table) {
            $table->id();
            
            // Unique receipt identifier
            $table->string('receipt_no', 50)->unique(); // e.g., "RCP-CAMPUS1-20240315-0001"
            
            // Student reference
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('restrict');
            
            $table->foreignId('student_enrollment_record_id')
                ->constrained('student_enrollment_records')
                ->onDelete('restrict');
            
            // Campus reference (for multi-campus support)
            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->onDelete('restrict');
            
            // Payment details
            $table->date('payment_date'); // When payment was received
            
            $table->enum('payment_method', [
                'cash',
                'bank',
                'online',
                'jazzcash',
                'easypaisa',
                'cheque'
            ])->index();
            
            $table->string('reference_no', 100)->nullable(); // Bank ref, transaction ID, cheque no
            $table->string('bank_name', 100)->nullable(); // For bank/cheque payments
            
            // Financial amounts
            $table->decimal('received_amount', 12, 2); // Total amount received (updated to 12,2 for long-term)
            $table->decimal('excess_amount', 12, 2)->default(0); // Amount exceeding voucher total (renamed from wallet_amount)
            $table->decimal('allocated_amount', 12, 2)->default(0); // Amount allocated to vouchers
            $table->decimal('remaining_unallocated_amount', 12, 2)->default(0); // Not yet allocated
            
            // Status
            $table->enum('status', ['pending', 'posted', 'reversed'])
                ->default('posted')
                ->index();
            
            // Metadata
            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance (with custom short names)
            $table->index(['student_id', 'payment_date'], 'idx_payment_student_date');
            $table->index(['student_enrollment_record_id', 'payment_date'], 'idx_payment_enroll_date');
            $table->index(['campus_id', 'payment_date'], 'idx_payment_campus_date');
            $table->index(['payment_date', 'status'], 'idx_payment_date_status');
            $table->index('payment_method', 'idx_payment_method');
            $table->index('received_by', 'idx_payment_received_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_fee_payments');
    }
};
