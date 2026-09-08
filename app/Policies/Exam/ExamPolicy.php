<?php

namespace App\Policies\Exam;

use App\Models\Exam\Exam;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may set up an exam, publish it, and close it.
 *
 * There was no policy here at all, and not one `authorize()` call in any of the
 * eight exam controllers. Every signed-in user — a student, a guardian, a
 * driver, a maid — could publish results and lock or unlock an exam.
 *
 * An exam belongs to a session rather than a campus, so the campus and class
 * widths do not narrow it: what an exam needs is the right ability, and the
 * separation of who may **set one up** from who may **publish** its results.
 * The records inside it — papers, and results — are where the campus and class
 * widths bite, and those have their own policies.
 */
class ExamPolicy
{
    use ChecksExamReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'exam.view');
    }

    public function view(User $user, Exam $exam): bool
    {
        return $this->may($user, 'exam.view');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'exam.manage');
    }

    /**
     * A published exam is still editable — a name or a date may be wrong — but
     * a locked one is not. That is what locking is for.
     */
    public function update(User $user, Exam $exam): bool
    {
        if ($exam->is_locked) {
            return false;
        }

        return $this->may($user, 'exam.manage');
    }

    public function delete(User $user, Exam $exam): bool
    {
        if ($exam->is_locked) {
            return false;
        }

        return $this->may($user, 'exam.delete');
    }

    /**
     * Putting results in front of parents is its own decision, and its own
     * ability. Somebody who may schedule an exam is not thereby somebody who
     * may publish what came out of it.
     */
    public function publish(User $user, Exam $exam): bool
    {
        if ($exam->is_locked) {
            return false;
        }

        return $this->may($user, 'exam.result.publish');
    }

    /**
     * Taking a published result back off the board is the same decision as
     * putting it up.
     */
    public function unpublish(User $user, Exam $exam): bool
    {
        return $this->publish($user, $exam);
    }

    /**
     * Closing an exam to further marking.
     */
    public function lock(User $user, Exam $exam): bool
    {
        if ($exam->is_locked) {
            return false;
        }

        return $this->may($user, 'exam.manage');
    }

    /**
     * Reopening one is deliberately narrower than closing it: only somebody who
     * may finalise marks may undo a closure. A teacher who closed their own
     * marking cannot quietly reopen it.
     */
    public function unlock(User $user, Exam $exam): bool
    {
        if (! $exam->is_locked) {
            return false;
        }

        return $this->may($user, 'exam.marks.verify');
    }

    public function manageSettings(User $user): bool
    {
        return $this->may($user, 'exam.settings');
    }

    public function manageRegistrations(User $user): bool
    {
        return $this->may($user, 'exam.registration.manage');
    }
}
