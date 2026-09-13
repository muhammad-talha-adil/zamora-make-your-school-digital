<?php

namespace App\Policies;

use App\Models\Campus;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's campuses.
 *
 * A campus is a school-wide structural record — it has no campus of its own
 * to check reach against, only the permission, same as `FeeHeadPolicy`.
 */
class CampusPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'school.campus.view', 'school.campus.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'school.campus.manage');
    }

    public function update(User $user, Campus $campus): bool
    {
        return $this->may($user, 'school.campus.manage');
    }

    public function delete(User $user, Campus $campus): bool
    {
        return $this->may($user, 'school.campus.delete');
    }
}
