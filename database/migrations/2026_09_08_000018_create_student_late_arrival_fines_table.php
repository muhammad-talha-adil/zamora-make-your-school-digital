<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A month's late-arrival charge, worked out by attendance and billed by fee.
 *
 * The two modules meet here rather than reaching into one another: attendance
 * knows who was late and how often, the fee run knows how to put a charge on a
 * voucher, and this table is the whole of what passes between them.
 *
 * Keeping the count and the working alongside the amount is deliberate. When a
 * parent asks why there is Rs 300 on the voucher, the answer has to be "six
 * late arrivals, three forgiven, three at a hundred" — not a number nobody can
 * account for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_late_arrival_fines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            // The working, kept so the charge can be explained.
            $table->unsignedSmallInteger('late_count')->default(0);
            $table->unsignedSmallInteger('charged_count')->default(0);
            $table->decimal('amount', 10, 2)->default(0);

            // pending | billed | waived
            $table->string('status', 20)->default('pending')->index();

            // The voucher line it ended up on, so the charge can be traced from
            // either end.
            $table->foreignId('fee_voucher_item_id')
                ->nullable()
                ->constrained('fee_voucher_items')
                ->nullOnDelete();

            $table->text('waiver_reason')->nullable();
            $table->timestamp('computed_at')->nullable();

            $table->timestamps();

            // One charge per child per month, recomputed rather than repeated.
            $table->unique(['student_id', 'session_id', 'month', 'year'], 'unique_monthly_late_fine');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_late_arrival_fines');
    }
};
