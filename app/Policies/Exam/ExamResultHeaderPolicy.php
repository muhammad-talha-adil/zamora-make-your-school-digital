<?php

namespace App\Policies\Exam;

use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamResultHeader;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may read a child's marks, and who may change them.
 *
 * This is the one that mattered most. Every signed-in user could read every
 * child's marks in every class of every campus, and enter and change them.
 *
 * A result header carries `campus_id`, `class_id` and `section_id`, so the
 * three widths apply as they do to a register. On top of them the result has a
 * workflow, and this policy respects it: a verified result is not editable by
 * the person who entered it, a published one is not editable at all without
 * being reopened, and a locked one is closed to everybody.
 *
 * A child and their family read their own result through `viewOwn`, which is
 * the `exam.result.view.own` ability the student and guardian roles already
 * hold and nothing used.
 */
class ExamResultHeaderPolicy
{
    use ChecksExamReach;
    use HandlesAuthorization;

    /**
     * `exam.result.view.own` only opens the door to the list endpoint — it
     * does not widen what the list returns. The portal scopes the query to
     * the caller's own student itself.
     */
    public function viewAny(User $user): bool
    {
        return $this->may($user, 'exam.result.view', 'exam.marks.enter', 'exam.result.view.own');
    }

    public function view(User $user, ExamResultHeader $header): bool
    {
        if ($this->isTheirOwn($user, $header)) {
            return $this->may($user, 'exam.result.view.own', 'exam.result.view');
        }

        return $this->may($user, 'exam.result.view', 'exam.marks.enter')
            && $this->covers($user, $header);
    }

    /**
     * A child, or their family, reading their own result.
     *
     * The student portal is the guardian portal here — a family signs in as the
     * child — so there is one check rather than two.
     */
    public function viewOwn(User $user, ExamResultHeader $header): bool
    {
        return $this->isTheirOwn($user, $header)
            && $this->may($user, 'exam.result.view.own', 'exam.result.view');
    }

    /**
     * Entering marks against a result that already exists.
     *
     * A result that has been verified or published is not edited in place; it
     * is reopened first, which is a decision somebody with `exam.marks.verify`
     * takes and is recorded as such.
     */
    public function enterMarks(User $user, ExamResultHeader $header): bool
    {
        if ($header->exam?->is_locked || $header->is_locked) {
            return false;
        }

        if (! in_array((string) $header->status, ExamResultHeader::OPEN_STATUSES, true)) {
            return false;
        }

        return $this->may($user, 'exam.marks.enter') && $this->covers($user, $header);
    }

    /**
     * Entering the first marks for a child, where no result exists yet.
     *
     * There is no result to check the reach against, so the **paper** being
     * marked is passed instead: it is the record that says which campus, class
     * and section this is, and a teacher may only mark the sections they have
     * been given.
     */
    public function create(User $user, ?ExamPaper $paper = null): bool
    {
        if (! $this->may($user, 'exam.marks.enter')) {
            return false;
        }

        if (! $paper) {
            return true;
        }

        if ($paper->exam?->is_locked) {
            return false;
        }

        return $this->reaches(
            $user,
            $paper->campus_id,
            $paper->class_id,
            $paper->section_id,
            $paper->exam?->session_id
        );
    }

    /**
     * Signing a result off, and reopening one.
     *
     * Deliberately not the same ability as entering marks: the point of
     * verification is that somebody other than the marker looks at it.
     */
    public function verify(User $user, ExamResultHeader $header): bool
    {
        if ($header->exam?->is_locked) {
            return false;
        }

        return $this->may($user, 'exam.marks.verify') && $this->covers($user, $header);
    }

    public function reopen(User $user, ExamResultHeader $header): bool
    {
        return $this->verify($user, $header);
    }

    public function publish(User $user, ExamResultHeader $header): bool
    {
        return $this->may($user, 'exam.result.publish') && $this->covers($user, $header);
    }

    public function manageRevaluation(User $user, ExamResultHeader $header): bool
    {
        return $this->may($user, 'exam.revaluation.manage') && $this->covers($user, $header);
    }

    /**
     * Whether this result is the signed-in child's own.
     */
    private function isTheirOwn(User $user, ExamResultHeader $header): bool
    {
        if ($user->student !== null && (int) $user->student->id === (int) $header->student_id) {
            return true;
        }

        return $user->guardian !== null
            && $user->guardian->students()->whereKey($header->student_id)->exists();
    }

    /**
     * Whether this result falls inside the user's reach.
     */
    private function covers(User $user, ExamResultHeader $header): bool
    {
        return $this->reaches(
            $user,
            $header->campus_id,
            $header->class_id,
            $header->section_id,
            $header->exam?->session_id
        );
    }
}
