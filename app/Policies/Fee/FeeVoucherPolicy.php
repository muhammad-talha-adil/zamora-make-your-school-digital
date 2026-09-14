<?php

namespace App\Policies\Fee;

use App\Models\Fee\FeeVoucher;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may read, generate and change a fee voucher.
 *
 * Same gap as `FeePaymentPolicy`: the `fee.voucher.*` permissions were seeded
 * and never checked. The width for staff is campus only, `ChecksSchoolReach`
 * — a voucher belongs to a class but not to one teacher's sections.
 *
 * A child and their family read their own voucher through `fee.view.own`, the
 * same `isTheirOwn` pattern `ExamResultHeaderPolicy` and `FeePaymentPolicy`
 * use — they never generate, edit, delete or (for now) print one themselves.
 */
class FeeVoucherPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    /**
     * `fee.view.own` only opens the door to the list endpoint — it does not
     * widen what the list returns. The portal scopes the query to the
     * caller's own student itself; without this the student/guardian
     * `/portal/fees` list 403s before it ever gets that far.
     */
    public function viewAny(User $user): bool
    {
        return $this->may($user, 'fee.view', 'fee.voucher.view', 'fee.view.own');
    }

    public function view(User $user, FeeVoucher $voucher): bool
    {
        if ($this->isTheirOwn($user, $voucher)) {
            return $this->may($user, 'fee.view.own', 'fee.view', 'fee.voucher.view');
        }

        return $this->may($user, 'fee.view', 'fee.voucher.view')
            && $this->reaches($user, $voucher->campus_id);
    }

    /**
     * A child, or their family, reading their own voucher history.
     */
    public function viewByStudent(User $user, int $studentId): bool
    {
        if ($user->student && (int) $user->student->id === $studentId) {
            return $this->may($user, 'fee.view.own', 'fee.view', 'fee.voucher.view');
        }

        return $this->may($user, 'fee.view', 'fee.voucher.view');
    }

    public function generate(User $user): bool
    {
        return $this->may($user, 'fee.voucher.generate');
    }

    public function update(User $user, FeeVoucher $voucher): bool
    {
        return $this->may($user, 'fee.voucher.edit')
            && $this->reaches($user, $voucher->campus_id);
    }

    public function delete(User $user, FeeVoucher $voucher): bool
    {
        return $this->may($user, 'fee.voucher.delete')
            && $this->reaches($user, $voucher->campus_id);
    }

    public function print(User $user, FeeVoucher $voucher): bool
    {
        if ($this->isTheirOwn($user, $voucher)) {
            return $this->may($user, 'fee.view.own', 'fee.voucher.print', 'fee.voucher.view');
        }

        return $this->may($user, 'fee.voucher.print', 'fee.voucher.view')
            && $this->reaches($user, $voucher->campus_id);
    }

    /**
     * A family's own voucher: the child themselves, or a guardian linked to
     * that child. There is no separate guardian portal — a guardian signs in
     * to the same account type as the child — so both are checked here.
     */
    private function isTheirOwn(User $user, FeeVoucher $voucher): bool
    {
        if ($user->student !== null && (int) $user->student->id === (int) $voucher->student_id) {
            return true;
        }

        return $user->guardian !== null
            && $user->guardian->students()->whereKey($voucher->student_id)->exists();
    }
}
