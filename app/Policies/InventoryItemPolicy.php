<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a single inventory item.
 *
 * `edit`/`update`/`destroy`/`activate`/`inactivate` all scoped campus access
 * via an optional `campus_id` request parameter — a campus-restricted user
 * could omit it, or spoof another campus's id, and still reach an item that
 * does not belong to them. This checks the resolved model instance instead.
 */
class InventoryItemPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.item.manage') && $this->covers($user, $inventoryItem->campus_id);
    }

    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $this->may($user, 'inventory.item.manage') && $this->covers($user, $inventoryItem->campus_id);
    }

    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        return $this->may($user, 'inventory.item.manage') && $this->covers($user, $inventoryItem->campus_id);
    }

    /**
     * Whether this record falls inside the user's reach.
     *
     * An inventory item always belongs to a campus, but a null is treated
     * the same as the other inventory policies for consistency.
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
