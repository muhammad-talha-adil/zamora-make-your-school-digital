<?php

namespace App\Policies\Fee;

use App\Models\Fee\FeeHead;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's fee heads.
 *
 * A fee head is a school-wide lookup, like a `SalaryHead` or a
 * `StaffDepartment` — no campus reach to check, only the permission.
 */
class FeeHeadPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'fee.view', 'fee.head.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'fee.head.manage');
    }

    public function update(User $user, FeeHead $feeHead): bool
    {
        return $this->may($user, 'fee.head.manage');
    }

    public function delete(User $user, FeeHead $feeHead): bool
    {
        return $this->may($user, 'fee.head.manage');
    }
}
