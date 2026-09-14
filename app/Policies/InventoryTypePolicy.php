<?php

namespace App\Policies;

use App\Models\InventoryType;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a single inventory type.
 *
 * `edit`/`update`/`destroy`/`activate`/`inactivate` all scoped campus access
 * via an optional `campus_id` request parameter — a campus-restricted user
 * could omit it, or spoof another campus's id, and still reach a type that
 * does not belong to them. This checks the resolved model instance instead.
 */
class InventoryTypePolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, InventoryType $inventoryType): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.item.manage') && $this->covers($user, $inventoryType->campus_id);
    }

    public function update(User $user, InventoryType $inventoryType): bool
    {
        return $this->may($user, 'inventory.item.manage') && $this->covers($user, $inventoryType->campus_id);
    }

    public function delete(User $user, InventoryType $inventoryType): bool
    {
        return $this->may($user, 'inventory.item.manage') && $this->covers($user, $inventoryType->campus_id);
    }

    /**
     * Whether this record falls inside the user's reach.
     *
     * A type with no campus is an "all campuses" type, reachable by anybody
     * who may manage types at all.
     */
    private function covers(User $user, ?int $campusId): bool
    {
        if ($user->isSuperAdmin() || $campusId === null) {
            return true;
        }

        $userCampus = $user->campusId();

        return $userCampus === null || (int) $campusId === (int) $userCampus;
    }
}
