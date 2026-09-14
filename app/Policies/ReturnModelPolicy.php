<?php

namespace App\Policies;

use App\Models\ReturnModel;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a single student-to-school inventory return.
 *
 * `show`/`destroy`/`restore` all scoped campus access via an optional
 * `campus_id` request parameter — a campus-restricted user could omit it, or
 * spoof another campus's id, and still reach a return that does not belong
 * to them. This checks the resolved model instance instead.
 */
class ReturnModelPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, ReturnModel $return): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.return.manage') && $this->covers($user, $return->campus_id);
    }

    public function delete(User $user, ReturnModel $return): bool
    {
        return $this->may($user, 'inventory.return.manage') && $this->covers($user, $return->campus_id);
    }

    public function restore(User $user, ReturnModel $return): bool
    {
        return $this->may($user, 'inventory.return.manage') && $this->covers($user, $return->campus_id);
    }

    private function covers(User $user, ?int $campusId): bool
    {
        if ($user->isSuperAdmin() || $campusId === null) {
            return true;
        }

        $userCampus = $user->campusId();

        return $userCampus === null || (int) $campusId === (int) $userCampus;
    }
}
