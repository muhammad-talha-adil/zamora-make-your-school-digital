<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which days a campus actually works.
 *
 * Attendance had no notion of a working day at all, so a percentage was
 * "days marked present out of days somebody happened to mark" — a child marked
 * on three days out of twenty-two showed 100%. Working that out properly needs
 * to know which weekdays the school runs, and schools here differ: Friday is a
 * half day at many, Saturday is a full day at some and closed at others, and it
 * varies by province.
 *
 * Scoped to a campus, optionally narrowed to a session, exactly like
 * `fee_policies` — the session-specific row wins where one exists, so last
 * year's timetable stays readable after this year's changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->cascadeOnDelete();

            $table->foreignId('session_id')
                ->nullable()
                ->constrained('academic_sessions')
                ->cascadeOnDelete();

            /*
             * ISO weekday numbers the school is open on: 1 is Monday, 7 is
             * Sunday. Monday to Saturday is the common default here.
             */
            $table->json('working_days');

            // Weekdays that are open but count as half a day, typically Friday.
            $table->json('half_days')->nullable();

            // Whether an absence is worth telling the guardian about the same
            // morning. The messages themselves are queued in
            // `attendance_absence_alerts`.
            $table->boolean('absence_alert_enabled')->default(false);

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->unique(['campus_id', 'session_id'], 'attendance_policies_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
