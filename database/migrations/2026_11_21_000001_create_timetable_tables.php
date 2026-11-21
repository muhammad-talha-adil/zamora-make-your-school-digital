<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The weekly class timetable.
 *
 * A period is a slot in the school day (a name and a start/end time, or a
 * break) belonging to a campus for one session. An entry places a subject and
 * a teacher into one period on one weekday for one section.
 *
 * The unique keys are the last line of defence behind the clash checks in
 * `TimetableClashService`: a teacher cannot hold two rooms in one period, and
 * a section cannot hold two lessons in one period, even under a race.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->string('name', 60);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_break')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campus_id', 'session_id', 'sort_order'], 'timetable_periods_scope_index');
        });

        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('timetable_periods')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->string('room', 40)->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'staff_profile_id', 'weekday', 'period_id'], 'timetable_entries_teacher_slot_unique');
            $table->unique(['session_id', 'section_id', 'weekday', 'period_id'], 'timetable_entries_section_slot_unique');
            $table->index(['class_id', 'section_id'], 'timetable_entries_section_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('timetable_periods');
    }
};
