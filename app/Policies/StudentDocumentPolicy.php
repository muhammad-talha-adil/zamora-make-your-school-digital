<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may file, see and remove a student's documents.
 *
 * The widths are `ChecksSchoolReach`, the same as `StudentPolicy` and
 * `StudentInventoryPolicy`: a document is reached through the student it
 * belongs to, whose campus and class live on the enrolment period that is
 * currently (or most recently) open, not on the student row itself.
 */
class StudentDocumentPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user, Student $student): bool
    {
        if ($student->user_id !== null && $student->user_id === $user->id) {
            return $this->may($user, 'students.view.own', 'students.view');
        }

        return $this->may($user, 'students.view') && $this->covers($user, $student);
    }

    public function create(User $user, Student $student): bool
    {
        return $this->may($user, 'students.edit') && $this->covers($user, $student);
    }

    public function delete(User $user, StudentDocument $document): bool
    {
        return $this->may($user, 'students.edit') && $this->covers($user, $document->student);
    }

    private function covers(User $user, Student $student): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $enrollment = $student->relationLoaded('currentEnrollment')
            ? $student->currentEnrollment
            : $student->currentEnrollment()->first();

        if (! $enrollment) {
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
}
