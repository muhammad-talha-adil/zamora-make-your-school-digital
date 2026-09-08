<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The billing rules a school sets for itself, rather than per fee structure.
 *
 * Schools in Pakistan differ on all of these — one charges a full month to a
 * child admitted on the 25th, another charges half, a third charges by the day
 * — so they cannot be hard-coded and they cannot live on a fee structure
 * either, since they apply across every structure a campus uses.
 *
 * Scoped to a campus, optionally narrowed to a session. The session-specific
 * row wins where one exists, so last year's policy stays readable after this
 * year's changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->cascadeOnDelete();

            // Null means the campus default, used by any session without its own.
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('academic_sessions')
                ->cascadeOnDelete();

            // --- Mid-month admission (S1) ---
            // none: the full month is charged whatever the admission date.
            // half_month: full before the cut-off day, half on or after it.
            // daily: charged for the days remaining in the month.
            $table->enum('proration_method', ['none', 'half_month', 'daily'])
                ->default('none');

            $table->unsignedTinyInteger('proration_cutoff_day')->default(16);

            // --- Refund of one-time charges on early leaving (S7) ---
            // A child who leaves within this many days of admission gets the
            // admission and registration charges back. Zero means never.
            $table->unsignedSmallInteger('one_time_refund_days')->default(0);

            // --- Automatic sibling concession (S3) ---
            // The rates themselves live in `fee_sibling_discount_rules`; this
            // only says whether they are applied without being entered by hand.
            $table->boolean('sibling_discount_enabled')->default(false);

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            // One policy per campus per session, and one campus default.
            $table->unique(['campus_id', 'session_id'], 'fee_policies_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_policies');
    }
};
