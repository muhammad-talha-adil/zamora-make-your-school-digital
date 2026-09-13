<?php

namespace App\Services\Staff;

use App\Models\Staff\StaffSubject;
use App\Models\StaffProfile;
use App\Models\TeacherClassAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Which classes a teacher has, and which subjects they may be given.
 *
 * **This is the phase that makes three finished modules work.**
 *
 * `teacher_class_assignments` was built to close attendance A13, and the class
 * width in Attendance, Exam and Student all read it. Nothing has ever written
 * to it, so the live table holds **zero rows** — which means every teacher in
 * the school currently sees nothing at all. The rule is right; it has had no
 * data.
 *
 * Two separate ideas, deliberately kept apart:
 *
 *  - **A class assignment** is a duty: this teacher takes 9-A for Physics this
 *    session, and one of them is the class teacher.
 *  - **A subject** is a capability: this person *may* be given Physics. It is
 *    what the timetable should offer, and what a school reads when it needs
 *    somebody to cover a class at short notice.
 */
class TeacherAssignmentService
{
    /**
     * Gives a teacher a class.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function assignClass(StaffProfile $teacher, array $data): TeacherClassAssignment
    {
        $classId = (int) ($data['class_id'] ?? 0);
        $sessionId = (int) ($data['session_id'] ?? 0);

        if (! $classId || ! $sessionId) {
            throw ValidationException::withMessages([
                'class_id' => 'Say which class, in which session.',
            ]);
        }

        $sectionId = $data['section_id'] ?? null;
        $subjectId = $data['subject_id'] ?? null;

        $this->refuseDuplicateClass($teacher, $sessionId, $classId, $sectionId, $subjectId);

        if ($data['is_class_teacher'] ?? false) {
            $this->refuseSecondClassTeacher($sessionId, $classId, $sectionId, $teacher);
        }

        return DB::transaction(fn () => TeacherClassAssignment::create([
            'staff_profile_id' => $teacher->id,
            'session_id' => $sessionId,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'subject_id' => $subjectId,
            'is_class_teacher' => (bool) ($data['is_class_teacher'] ?? false),
            'periods_per_week' => $data['periods_per_week'] ?? null,
            'is_active' => true,
            'notes' => $data['notes'] ?? null,
        ]));
    }

    /**
     * Takes a class away.
     *
     * Marked inactive rather than deleted: the register for last month was
     * taken by this teacher, and a report that asks who took it should still
     * get an answer.
     */
    public function unassignClass(TeacherClassAssignment $assignment): TeacherClassAssignment
    {
        $assignment->update(['is_active' => false]);

        return $assignment->fresh();
    }

    /**
     * Says this person may be given a subject.
     *
     * @throws ValidationException
     */
    public function allowSubject(StaffProfile $teacher, int $subjectId, bool $isPrimary = false): StaffSubject
    {
        $existing = StaffSubject::where('staff_profile_id', $teacher->id)
            ->where('subject_id', $subjectId)
            ->first();

        if ($existing) {
            if ($isPrimary) {
                $existing->update(['is_primary' => true]);
            }

            return $existing->fresh();
        }

        return StaffSubject::create([
            'staff_profile_id' => $teacher->id,
            'subject_id' => $subjectId,
            'is_primary' => $isPrimary,
        ]);
    }

    public function disallowSubject(StaffProfile $teacher, int $subjectId): void
    {
        StaffSubject::where('staff_profile_id', $teacher->id)
            ->where('subject_id', $subjectId)
            ->delete();
    }

    /**
     * Who could take this subject, for the day somebody is away.
     *
     * The question a school asks at eight in the morning and has never been
     * able to ask this system.
     *
     * @return Collection<int, StaffProfile>
     */
    public function whoCanTeach(int $subjectId, ?int $campusId = null)
    {
        return StaffProfile::query()
            ->active()
            ->with(['user:id,name', 'designation'])
            ->whereHas('subjects', fn ($q) => $q->where('subject_id', $subjectId))
            ->when($campusId, fn ($q) => $q->where(function ($inner) use ($campusId) {
                $inner->whereNull('campus_id')->orWhere('campus_id', $campusId);
            }))
            ->get();
    }

    /**
     * A teacher's timetable, as the profile screen shows it.
     *
     * @return Collection<int, TeacherClassAssignment>
     */
    public function classesOf(StaffProfile $teacher, ?int $sessionId = null)
    {
        return TeacherClassAssignment::with(['schoolClass', 'section', 'subject', 'session'])
            ->where('staff_profile_id', $teacher->id)
            ->active()
            ->when($sessionId, fn ($q) => $q->where('teacher_class_assignments.session_id', $sessionId))
            ->orderBy('class_id')
            ->get();
    }

    /**
     * The class teacher of a section, if one has been named.
     */
    public function classTeacherOf(int $sessionId, int $classId, ?int $sectionId = null): ?StaffProfile
    {
        $assignment = TeacherClassAssignment::with('staffProfile.user')
            ->where('teacher_class_assignments.session_id', $sessionId)
            ->where('class_id', $classId)
            ->when(
                $sectionId,
                fn ($q) => $q->where('section_id', $sectionId),
                fn ($q) => $q->whereNull('section_id')
            )
            ->active()
            ->classTeacher()
            ->first();

        /** @var StaffProfile|null $profile */
        $profile = $assignment?->staffProfile;

        return $profile;
    }

    /**
     * @throws ValidationException
     */
    private function refuseDuplicateClass(
        StaffProfile $teacher,
        int $sessionId,
        int $classId,
        ?int $sectionId,
        ?int $subjectId
    ): void {
        $already = TeacherClassAssignment::where('staff_profile_id', $teacher->id)
            ->where('teacher_class_assignments.session_id', $sessionId)
            ->where('class_id', $classId)
            ->where(fn ($q) => $sectionId ? $q->where('section_id', $sectionId) : $q->whereNull('section_id'))
            ->where(fn ($q) => $subjectId ? $q->where('subject_id', $subjectId) : $q->whereNull('subject_id'))
            ->active()
            ->exists();

        if ($already) {
            throw ValidationException::withMessages([
                'class_id' => 'This teacher already has that class.',
            ]);
        }
    }

    /**
     * A section has one class teacher.
     *
     * Two would mean two people answering for the same register, and the
     * attendance module reads this to decide who may sign one off.
     *
     * @throws ValidationException
     */
    private function refuseSecondClassTeacher(
        int $sessionId,
        int $classId,
        ?int $sectionId,
        StaffProfile $teacher
    ): void {
        $existing = TeacherClassAssignment::with('staffProfile.user')
            ->where('teacher_class_assignments.session_id', $sessionId)
            ->where('class_id', $classId)
            ->where(fn ($q) => $sectionId ? $q->where('section_id', $sectionId) : $q->whereNull('section_id'))
            ->where('staff_profile_id', '!=', $teacher->id)
            ->active()
            ->classTeacher()
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'is_class_teacher' => 'This section already has a class teacher: '
                    .($existing->staffProfile?->user->name ?? 'somebody else')
                    .'. Take it from them first.',
            ]);
        }
    }
}
