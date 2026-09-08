<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records a refunded admission or registration charge.
 *
 * `student_paid_one_time_fees` says a charge was paid and must never be billed
 * again — which is right, but it had no way to say the money went back. A child
 * who left a fortnight after admission kept a paid admission fee on record for
 * ever, and the office had nothing to reconcile the cash against.
 *
 * The row stays: it is the record of what was paid. The refund is written
 * alongside it, so both halves of the story are in one place, and the paid
 * guard is lifted only when the whole amount has been returned.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_paid_one_time_fees', function (Blueprint $table) {
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('amount_paid');
            $table->date('refunded_at')->nullable()->after('payment_date');
            $table->string('refund_reason', 255)->nullable()->after('refunded_at');

            $table->foreignId('refunded_by')
                ->nullable()
                ->after('refund_reason')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_paid_one_time_fees', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn(['refunded_amount', 'refunded_at', 'refund_reason', 'refunded_by']);
        });
    }
};
