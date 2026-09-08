<?php

namespace App\Policies\Exam;

use App\Models\Exam\ExamPaper;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and timetable a paper.
 *
 * A paper carries `campus_id`, `class_id` and `section_id`, so this is where
 * the three widths bite: a teacher sees the papers of the sections they have
 * been given, a campus admin sees their campus, and nobody else's.
 *
 * Before this, any signed-in user could reschedule any paper in any campus.
 */
class ExamPaperPolicy
{
    use ChecksExamReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'exam.paper.view');
    }

    public function view(User $user, ExamPaper $paper): bool
    {
        return $this->may($user, 'exam.paper.view') && $this->covers($user, $paper);
    }

    /**
     * There is no record to check yet; where the paper is being placed is
     * checked by the controller before it is written.
     */
    public function create(User $user): bool
    {
        return $this->may($user, 'exam.paper.manage');
    }

    public function update(User $user, ExamPaper $paper): bool
    {
        if ($paper->exam?->is_locked) {
            return false;
        }

        return $this->may($user, 'exam.paper.manage') && $this->covers($user, $paper);
    }

    public function delete(User $user, ExamPaper $paper): bool
    {
        return $this->update($user, $paper);
    }

    /**
     * Whether this paper falls inside the user's reach.
     */
    private function covers(User $user, ExamPaper $paper): bool
    {
        return $this->reaches(
            $user,
            $paper->campus_id,
            $paper->class_id,
            $paper->section_id,
            $paper->exam?->session_id
        );
    }
}
