<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which classes a teacher is responsible for.
 *
 * Nothing in the system recorded this, so nothing could answer the question the
 * attendance policy needed to ask: *is this teacher's class?* Every check was a
 * bare permission test, which meant any teacher holding `attendance.view` could
 * read every campus's registers and any teacher holding `attendance.edit` could
 * rewrite a class they had nothing to do with.
 *
 * Two kinds of assignment live here, and the difference matters to a school:
 *
 *  - **Subject teacher** — teaches one subject to a section. May teach several
 *    subjects to several sections; each is its own row.
 *  - **Class teacher** — owns the section. Takes its register, hands out its
 *    result cards, and is the person a parent asks for. Exactly one per section
 *    per session, which the index below enforces rather than trusts.
 *
 * Scoped to a session because a teaching load is a year's arrangement: last
 * year's assignments stay readable after this year's are made, the same way an
 * enrollment period does for a child.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_class_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_profile_id')
                ->constrained('staff_profiles')
                ->cascadeOnDelete();

            /*
             * Restrict, not cascade, on the three columns the generated key
             * below is built from: MySQL forbids a cascading foreign key on a
             * column a stored generated column depends on. It is the better
             * rule regardless — a session or a class with teaching assignments
             * against it is not something to delete out from under them.
             */
            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            $table->foreignId('class_id')
                ->constrained('school_classes')
                ->restrictOnDelete();

            // Null means the whole class, for a school whose classes have no
            // sections at all.
            $table->foreignId('section_id')
                ->nullable()
                ->constrained('sections')
                ->restrictOnDelete();

            // Null for a class teacher, who is responsible for the section
            // rather than for one subject in it.
            $table->foreignId('subject_id')
                ->nullable()
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->boolean('is_class_teacher')->default(false);

            /*
             * Carries the condition MySQL cannot put in an index: null for
             * anything that is not a class teacher, and therefore never in
             * conflict with anything. Declared here rather than added later
             * because MySQL refuses to add a stored column to a table that
             * already has foreign keys.
             */
            if (DB::getDriverName() !== 'sqlite') {
                $table->string('class_teacher_key', 64)->storedAs(
                    "case when is_class_teacher = 1
                     then concat(session_id, '-', class_id, '-', coalesce(section_id, 0))
                     end"
                );
            }

            // How many periods a week this assignment is worth, so the office
            // can see a teacher's load before handing them one more class.
            $table->unsignedSmallInteger('periods_per_week')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['staff_profile_id', 'session_id'], 'idx_teacher_assignment_staff');
            $table->index(['session_id', 'class_id', 'section_id'], 'idx_teacher_assignment_class');
        });

        $this->enforceOneClassTeacherPerSection();
    }

    /**
     * One class teacher per section per session.
     *
     * "One of the teachers of 6-A" is not an answer a parent can be given, so
     * the constraint is in the database rather than in a screen that can be
     * bypassed. A subject teacher row is excluded from it — there are many of
     * those by design.
     */
    private function enforceOneClassTeacherPerSection(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement(
                'create unique index unique_class_teacher on teacher_class_assignments
                 (session_id, class_id, section_id) where is_class_teacher = 1 and section_id is not null'
            );
            DB::statement(
                'create unique index unique_class_teacher_no_section on teacher_class_assignments
                 (session_id, class_id) where is_class_teacher = 1 and section_id is null'
            );

            return;
        }

        // MySQL has no partial index; the generated column declared in the
        // create carries the condition instead.
        Schema::table('teacher_class_assignments', function (Blueprint $table) {
            $table->unique('class_teacher_key', 'unique_class_teacher');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_class_assignments');
    }
};
