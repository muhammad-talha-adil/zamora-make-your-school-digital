<?php

namespace App\Policies\Fee;

use App\Models\Fee\StudentDiscount;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and act on the discount approval queue.
 *
 * `requires_approval` discount types create a `StudentDiscount` row sitting
 * in `pending` with nobody able to move it — see
 * `StudentRepository::createDiscountsFromAdmission()`. Approval is reserved
 * for the Owner and Principal (the `campus_admin` role in this system), and
 * a campus admin's reach stops at their own campus — the same
 * `ChecksSchoolReach` width `FeeVoucherPolicy` uses, read off the student's
 * enrollment record since `student_discounts` carries no `campus_id` of its
 * own.
 */
class StudentDiscountPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'fee.discount.approve');
    }

    public function approve(User $user, StudentDiscount $studentDiscount): bool
    {
        return $this->may($user, 'fee.discount.approve')
            && $this->reaches($user, $studentDiscount->enrollmentRecord->campus_id);
    }

    public function reject(User $user, StudentDiscount $studentDiscount): bool
    {
        return $this->approve($user, $studentDiscount);
    }
}
