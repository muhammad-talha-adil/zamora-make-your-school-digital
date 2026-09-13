<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

/**
 * Who may read and change a child's record.
 *
 * Every method here used to be a bare permission check that ignored the record
 * entirely, so a teacher at the City campus could edit, re-admit and mark as
 * left any child at any other campus, and `export` handed them the whole
 * school. The third module in a row with that fault.
 *
 * The widths are `ChecksSchoolReach`, shared with the exam policies. A child's
 * campus and class are not on the `students` row — they are on the enrolment
 * period that is currently open — so that is what is checked.
 *
 * The policy guards one record; `Student::visibleTo()` filters a list. They are
 * two halves of one rule and a test asserts they agree.
 */
class StudentPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    /**
     * Records that somebody changed a child's record.
     *
     * Every method used to log every check, including `viewAny` on each load of
     * the list — an INFO line per passed check, several per request, burying
     * the handful of lines that matter. What is worth keeping is who **changed**
     * something, and who was **refused**.
     */
    private function audit(User $user, string $ability, ?Student $student = null): void
    {
        Log::info('Student record changed', [
            'user_id' => $user->id,
            'ability' => $ability,
            'student_id' => $student?->id,
            'student_code' => $student?->student_code,
        ]);
    }

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'students.view');
    }

    public function view(User $user, Student $student): bool
    {
        // A child, or their family, reading their own record. The student
        // portal is the guardian portal here, so there is one check.
        if ($this->isOwnStudent($user, $student)) {
            return $this->may($user, 'students.view.own', 'students.view');
        }

        return $this->may($user, 'students.view') && $this->covers($user, $student);
    }

    /**
     * There is no record to check yet. Where the child is being admitted **to**
     * is checked by the request against the user's own campus.
     */
    public function create(User $user): bool
    {
        return $this->may($user, 'students.create');
    }

    public function update(User $user, Student $student): bool
    {
        if ($this->isOwnStudent($user, $student) && $this->may($user, 'students.edit.own')) {
            return true;
        }

        $allowed = $this->may($user, 'students.edit') && $this->covers($user, $student);

        if ($allowed) {
            $this->audit($user, 'update', $student);
        }

        return $allowed;
    }

    /**
     * Deleting a child who is still on the roll is a heavier act than deleting
     * one who has left, and takes the heavier permission.
     */
    public function delete(User $user, Student $student): bool
    {
        $ability = $student->currentEnrollment ? 'students.force.delete' : 'students.delete';

        $allowed = $this->may($user, $ability) && $this->covers($user, $student);

        if ($allowed) {
            $this->audit($user, 'delete', $student);
        }

        return $allowed;
    }

    /**
     * A deleted child has no open enrolment, so there is no campus on them to
     * check. Restoring is therefore school-wide by nature and is kept to the
     * people who hold the ability.
     */
    public function restore(User $user, Student $student): bool
    {
        $allowed = $this->may($user, 'students.restore');

        if ($allowed) {
            $this->audit($user, 'restore', $student);
        }

        return $allowed;
    }

    public function forceDelete(User $user, Student $student): bool
    {
        $allowed = $this->may($user, 'students.force.delete');

        if ($allowed) {
            $this->audit($user, 'forceDelete', $student);
        }

        return $allowed;
    }

    /**
     * Marking a child as having left, or bringing them back.
     */
    public function changeStatus(User $user, Student $student): bool
    {
        $allowed = $this->may($user, 'students.status.change') && $this->covers($user, $student);

        if ($allowed) {
            $this->audit($user, 'changeStatus', $student);
        }

        return $allowed;
    }

    /**
     * Re-admitting a child who has left.
     *
     * Their last enrolment is closed, so the reach is checked against **where
     * they were**, not where they are — a child has to be findable by the
     * campus that is bringing them back.
     */
    public function readmit(User $user, Student $student): bool
    {
        $allowed = $this->may($user, 'students.readmit') && $this->covers($user, $student);

        if ($allowed) {
            $this->audit($user, 'readmit', $student);
        }

        return $allowed;
    }

    /**
     * Moving a class up at the end of the year.
     */
    public function promote(User $user, Student $student): bool
    {
        $allowed = $this->may($user, 'students.promote') && $this->covers($user, $student);

        if ($allowed) {
            $this->audit($user, 'promote', $student);
        }

        return $allowed;
    }

    /**
     * Exporting handed the whole school to anybody holding the ability. It is
     * still school-wide as an ability; what comes **out** of it is narrowed by
     * `Student::visibleTo()`, which is where the reach belongs for a list.
     */
    public function export(User $user): bool
    {
        return $this->may($user, 'students.export');
    }

    public function import(User $user): bool
    {
        return $this->may($user, 'students.import');
    }

    /**
     * Whether this child falls inside the user's reach.
     *
     * Read from the enrolment period that is open now, and from the most recent
     * closed one for a child who has left — otherwise nobody could ever
     * re-admit them.
     */
    private function covers(User $user, Student $student): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $enrollment = $student->relationLoaded('currentEnrollment')
            ? $student->currentEnrollment
            : null;

        $enrollment ??= StudentEnrollmentRecord::where('student_id', $student->id)
            ->orderByRaw('CASE WHEN leave_date IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('admission_date')
            ->orderByDesc('id')
            ->first();

        if (! $enrollment) {
            // A child with no enrolment at all is a data anomaly, not a child
            // in somebody's class. Only school-wide access reaches them.
            return false;
        }

        return $this->reaches(
            $user,
            $enrollment->campus_id,
            $enrollment->class_id,
            $enrollment->section_id,
            $enrollment->session_id
        );
    }

    /**
     * Whether this is the signed-in child's own record.
     */
    private function isOwnStudent(User $user, Student $student): bool
    {
        return $student->user_id !== null && $student->user_id === $user->id;
    }
}
