<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a timing apply to a group of classes rather than the whole campus, and
 * gives it a break window.
 *
 * "5 junior classes finish at 12, everyone else at 1" needs a timing that
 * knows which classes it covers. `class_ids` is a JSON list of class ids;
 * null or empty means the timing is the campus/session's general one, exactly
 * as it behaved before this column existed, so nothing already configured
 * changes meaning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_timings', function (Blueprint $table) {
            $table->json('class_ids')->nullable()->after('session_id');
            $table->time('break_starts_at')->nullable()->after('late_after');
            $table->time('break_ends_at')->nullable()->after('break_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_timings', function (Blueprint $table) {
            $table->dropColumn(['class_ids', 'break_starts_at', 'break_ends_at']);
        });
    }
};
