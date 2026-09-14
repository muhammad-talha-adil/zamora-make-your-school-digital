<?php

namespace App\Policies;

use App\Models\StudentInventory;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a single student inventory assignment.
 *
 * `show`/`createReturn`/`return`/`checkReturnAvailability`/`destroy`/
 * `restore` all scoped campus access via an optional `campus_id` request
 * parameter — a campus-restricted user could omit it, or spoof another
 * campus's id, and still reach an assignment that does not belong to them.
 * This checks the resolved model instance instead.
 */
class StudentInventoryPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, StudentInventory $studentInventory): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.student.issue') && $this->covers($user, $studentInventory->campus_id);
    }

    public function update(User $user, StudentInventory $studentInventory): bool
    {
        return $this->may($user, 'inventory.student.issue') && $this->covers($user, $studentInventory->campus_id);
    }

    public function delete(User $user, StudentInventory $studentInventory): bool
    {
        return $this->may($user, 'inventory.student.issue') && $this->covers($user, $studentInventory->campus_id);
    }

    public function restore(User $user, StudentInventory $studentInventory): bool
    {
        return $this->may($user, 'inventory.student.issue') && $this->covers($user, $studentInventory->campus_id);
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
