<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the monthly summary a real, rebuildable cache.
 *
 * The table, its model and the relation on `Student` all existed and **nothing
 * ever wrote a row**, so every report recomputed from scratch and anyone who
 * trusted the relation got an empty collection. It is kept rather than dropped
 * because a report card and a government return both want "days present out of
 * working days" per child per month, and recomputing a year of registers for a
 * whole school on each request is not something to do twice.
 *
 * What it is not: a source of truth. Every row is derived from
 * `attendance_students` and can be thrown away and rebuilt with
 * `php artisan attendance:rebuild-summaries`.
 *
 * The columns added here are what was missing to say anything honest:
 *
 *  - `expected_days`  — the working days this child was actually expected, so a
 *                       percentage has a denominator. Days before admission,
 *                       after leaving, weekends and holidays are all out.
 *  - `present_equivalent` — days present counted by the status weight, so a half
 *                       day counts as half.
 *  - `half_day_count` — kept separately because the office reads it.
 *
 * And the index named `unique_monthly_summary` becomes the unique constraint
 * its name always claimed to be.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->unsignedSmallInteger('half_day_count')->default(0)->after('late_count');
            $table->unsignedSmallInteger('expected_days')->default(0)->after('total_days');
            $table->decimal('present_equivalent', 6, 2)->default(0)->after('expected_days');

            // The register a summary was last built from, so a stale row is
            // recognisable without recomputing it.
            $table->timestamp('computed_at')->nullable()->after('present_equivalent');
        });

        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->dropIndex('unique_monthly_summary');
        });

        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->unique(['student_id', 'session_id', 'month', 'year'], 'unique_monthly_summary');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->dropUnique('unique_monthly_summary');
        });

        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->index(['student_id', 'session_id', 'month', 'year'], 'unique_monthly_summary');
            $table->dropColumn(['half_day_count', 'expected_days', 'present_equivalent', 'computed_at']);
        });
    }
};
