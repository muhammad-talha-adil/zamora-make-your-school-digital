<?php

namespace App\Policies;

use App\Models\InventoryAdjustment;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a single stock adjustment.
 *
 * `show`/`destroy` scoped campus access via an optional `campus_id` request
 * parameter — a campus-restricted user could omit it, or spoof another
 * campus's id, and still reach an adjustment that does not belong to them.
 * This checks the resolved model instance instead.
 */
class InventoryAdjustmentPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, InventoryAdjustment $adjustment): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.stock.manage') && $this->covers($user, $adjustment->campus_id);
    }

    public function delete(User $user, InventoryAdjustment $adjustment): bool
    {
        return $this->may($user, 'inventory.stock.manage') && $this->covers($user, $adjustment->campus_id);
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
