<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How much of a day each status is worth.
 *
 * Every count in the module was written as `code === 'P'`, so adding a status
 * meant editing the summary arithmetic in four places — and a half day, which
 * schools here mark routinely when a child leaves after break, could not be
 * expressed at all.
 *
 * Stating the weight on the status itself means the arithmetic stops caring
 * which statuses exist: a school can add "Short Leave" at 0.5 and the reports
 * follow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_statuses', function (Blueprint $table) {
            // The share of a day a student on this status is counted present:
            // 1.00 present, 0.50 half day, 0.00 absent.
            $table->decimal('weight', 3, 2)->default(0)->after('code');

            // Whether the day counts towards the days the child was expected.
            // Approved leave is shown in its own column and still expected, so
            // this is only false for statuses that mean the school was shut for
            // that child in particular.
            $table->boolean('counts_as_expected')->default(true)->after('weight');

            $table->unsignedSmallInteger('sort_order')->default(0)->after('counts_as_expected');
        });

        // Late is still present: the child was in class.
        DB::table('attendance_statuses')->whereIn('code', ['P', 'LT'])->update(['weight' => 1]);
    }

    public function down(): void
    {
        Schema::table('attendance_statuses', function (Blueprint $table) {
            $table->dropColumn(['weight', 'counts_as_expected', 'sort_order']);
        });
    }
};
