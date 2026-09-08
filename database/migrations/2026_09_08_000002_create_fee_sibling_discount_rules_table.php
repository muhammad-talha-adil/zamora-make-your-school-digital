<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Ten percent off the second child, twenty off the third."
 *
 * Siblings are already known through `student_guardians`, so the concession
 * that almost every school here offers was being typed in by hand for each
 * child and forgotten whenever one of them left. These rules let it be stated
 * once and applied by the fee run.
 *
 * A rule is keyed by the child's rank among the siblings currently enrolled,
 * counting the eldest as first. It only takes effect where the campus policy
 * has `sibling_discount_enabled` set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_sibling_discount_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->cascadeOnDelete();

            $table->foreignId('session_id')
                ->nullable()
                ->constrained('academic_sessions')
                ->cascadeOnDelete();

            // 2 = the second child, 3 = the third, and so on. The eldest pays in
            // full, so a rank of 1 would have no meaning.
            $table->unsignedTinyInteger('child_rank');

            // Null applies the concession to every charge on the voucher.
            $table->foreignId('fee_head_id')
                ->nullable()
                ->constrained('fee_heads')
                ->restrictOnDelete();

            $table->enum('value_type', ['fixed', 'percent'])->default('percent');
            $table->decimal('value', 12, 2);

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->unique(
                ['campus_id', 'session_id', 'child_rank', 'fee_head_id'],
                'fee_sibling_rule_scope_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_sibling_discount_rules');
    }
};
