<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the school day actually starts, which is not the same all year.
 *
 * "Late" cannot be a fixed time here. School starts an hour or more earlier
 * through Ramzan, and later in winter in the north — so a child who walks in at
 * 8:15 is late in one month and early in the next, and a system with one
 * hard-coded start time is wrong for a good part of the year.
 *
 * Each row is a period with its own clock. A campus without one falls back to
 * whatever it has always done: nothing is marked late automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_timings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->cascadeOnDelete();

            $table->foreignId('session_id')
                ->nullable()
                ->constrained('academic_sessions')
                ->cascadeOnDelete();

            // "Regular", "Ramzan", "Winter" — the school's own words.
            $table->string('name', 60);

            // The dates this clock applies between. A regular timing covers the
            // whole session and the special ones sit inside it; the narrower
            // period wins, which is decided by span rather than by a flag so
            // nobody has to remember to turn one off.
            $table->date('starts_on');
            $table->date('ends_on');

            $table->time('day_starts_at');

            // A child arriving after this is late. Usually a few minutes after
            // the start, because the gate does not close on the bell.
            $table->time('late_after');

            $table->time('day_ends_at')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['campus_id', 'session_id', 'starts_on', 'ends_on'], 'idx_timing_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_timings');
    }
};
