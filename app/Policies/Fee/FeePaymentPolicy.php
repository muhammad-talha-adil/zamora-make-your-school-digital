<?php

namespace App\Policies\Fee;

use App\Models\Fee\FeePayment;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may read and record a fee payment.
 *
 * `fee.payment.collect` had been seeded since the beginning and **not once
 * used** — every route in `routes/fee.php` sat on `auth` alone, so any
 * signed-in account (a student's own portal login included, since students and
 * staff share one `User` model and one guard) could record a payment against
 * any student, or read anybody's receipt.
 *
 * A child and their family read their own receipts through `fee.view.own` —
 * the same ability the `student`/`guardian` roles already hold for this
 * reason, on the same `isTheirOwn` pattern `ExamResultHeaderPolicy` uses.
 *
 * The width for staff is `ChecksSchoolReach`, campus only — a payment has no
 * class.
 */
class FeePaymentPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'fee.view', 'fee.payment.collect');
    }

    public function view(User $user, FeePayment $payment): bool
    {
        if ($this->isTheirOwn($user, $payment)) {
            return $this->may($user, 'fee.view.own', 'fee.view', 'fee.payment.collect');
        }

        return $this->may($user, 'fee.view', 'fee.payment.collect')
            && $this->reaches($user, $payment->campus_id);
    }

    /**
     * A child, or their family, reading their own payment history.
     *
     * There is no single payment to check yet — this scopes a list by student
     * id — so the same "own" check `view()` uses is done against the id
     * rather than a loaded record.
     */
    public function viewByStudent(User $user, int $studentId): bool
    {
        if ($user->student && (int) $user->student->id === $studentId) {
            return $this->may($user, 'fee.view.own', 'fee.view', 'fee.payment.collect');
        }

        return $this->may($user, 'fee.view', 'fee.payment.collect');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'fee.payment.collect');
    }

    public function reverse(User $user, FeePayment $payment): bool
    {
        return $this->may($user, 'fee.payment.refund')
            && $this->reaches($user, $payment->campus_id);
    }

    private function isTheirOwn(User $user, FeePayment $payment): bool
    {
        return $user->student !== null
            && (int) $user->student->id === (int) $payment->student_id;
    }
}
