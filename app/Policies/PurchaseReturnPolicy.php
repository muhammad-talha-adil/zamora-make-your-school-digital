<?php

namespace App\Policies;

use App\Models\PurchaseReturn;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a single purchase return.
 *
 * `show`/`update`/`destroy` all scoped campus access via an optional
 * `campus_id` request parameter — a campus-restricted user could omit it, or
 * spoof another campus's id, and still reach a return that does not belong
 * to them. This checks the resolved model instance instead.
 */
class PurchaseReturnPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.return.manage') && $this->covers($user, $purchaseReturn->campus_id);
    }

    public function update(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $this->may($user, 'inventory.return.manage') && $this->covers($user, $purchaseReturn->campus_id);
    }

    public function delete(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $this->may($user, 'inventory.return.manage') && $this->covers($user, $purchaseReturn->campus_id);
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
