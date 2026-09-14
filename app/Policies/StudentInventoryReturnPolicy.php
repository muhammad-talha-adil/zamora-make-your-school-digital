<?php

namespace App\Policies;

use App\Models\StudentInventoryReturn;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see a single student inventory return record.
 *
 * `showReturn` scoped campus access via an optional `campus_id` request
 * parameter — a campus-restricted user could omit it, or spoof another
 * campus's id, and still reach a return that does not belong to them. This
 * checks the resolved model instance instead.
 */
class StudentInventoryReturnPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, StudentInventoryReturn $studentInventoryReturn): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.student.issue') && $this->covers($user, $studentInventoryReturn->campus_id);
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
