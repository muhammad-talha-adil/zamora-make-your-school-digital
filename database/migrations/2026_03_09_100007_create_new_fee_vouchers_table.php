    <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Vouchers Migration
 *
 * Voucher/challan header table. This is the core transaction document.
 * Each voucher is a historical snapshot - once generated, it should not
 * change even if fee structures are modified later.
 *
 * Pakistani School Context:
 * - Vouchers are typically generated monthly
 * - Each voucher has a unique number (like an invoice)
 * - Parents pay at bank or school office using voucher number
 * - Vouchers can be paid in full, partially, or remain unpaid
 * - Late payments may incur fines
 * - Advance payments can be adjusted against future vouchers
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_vouchers', function (Blueprint $table) {
            $table->id();

            // Unique voucher identifier
            $table->string('voucher_no', 50)->unique(); // e.g., "FV-2024-001234"

            // Student reference
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('restrict'); // Don't delete vouchers if student deleted

            $table->foreignId('student_enrollment_record_id')
                ->constrained('student_enrollment_records')
                ->onDelete('restrict');

            // Scope (cached from enrollment for performance)
            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->onDelete('restrict');

            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->onDelete('restrict');

            $table->foreignId('class_id')
                ->constrained('school_classes')
                ->onDelete('restrict');

            $table->foreignId('section_id')
                ->constrained('sections')
                ->onDelete('restrict');

            // Billing period
            $table->foreignId('voucher_month_id')
                ->constrained('months')
                ->onDelete('restrict'); // Month reference
            $table->unsignedSmallInteger('voucher_year'); // e.g., 2024

            // Important dates
            $table->date('issue_date'); // When voucher was generated
            $table->date('due_date'); // Payment deadline

            // Status
            $table->enum('status', [
                'unpaid',
                'partial',
                'paid',
                'overdue',
                'cancelled',
                'adjusted',
            ])->default('unpaid')->index();

            // Financial amounts (all in PKR - using 12,2 for long-term storage)
            $table->decimal('gross_amount', 12, 2)->default(0); // Total before discounts
            $table->decimal('discount_amount', 12, 2)->default(0); // Total discounts
            $table->decimal('fine_amount', 12, 2)->default(0); // Late payment fines
            $table->decimal('paid_amount', 12, 2)->default(0); // Amount paid so far
            $table->decimal('net_amount', 12, 2)->default(0); // Final amount due
            $table->decimal('balance_amount', 12, 2)->default(0); // Remaining unpaid
            $table->decimal('advance_adjusted_amount', 12, 2)->default(0); // Advance used

            // Metadata
            $table->text('notes')->nullable();
            $table->foreignId('generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('published_at')->nullable(); // When made available to student

            $table->timestamps();
            $table->softDeletes();

            // Indexes for high-performance queries (with custom short names)
            $table->index(['student_id', 'status'], 'idx_voucher_student_status');
            $table->index(['student_enrollment_record_id', 'status'], 'idx_voucher_enroll_status');
            $table->index(['voucher_month_id', 'voucher_year'], 'idx_voucher_period');
            $table->index(['session_id', 'campus_id', 'class_id', 'section_id'], 'idx_voucher_scope');
            $table->index(['due_date', 'status'], 'idx_voucher_due_status');
            $table->index(['issue_date', 'status'], 'idx_voucher_issue_status');
            $table->index('published_at', 'idx_voucher_published');

            // Composite index for finding student's vouchers by period
            $table->index(['student_id', 'voucher_year', 'voucher_month_id'], 'idx_voucher_student_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_vouchers');
    }
};
