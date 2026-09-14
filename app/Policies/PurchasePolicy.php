<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a single purchase.
 *
 * `show`/`edit`/`update`/`destroy` all scoped campus access via an optional
 * `campus_id` request parameter — a campus-restricted user could omit it, or
 * spoof another campus's id, and still reach a purchase that does not belong
 * to them. This checks the resolved model instance instead.
 */
class PurchasePolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, Purchase $purchase): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.purchase.view', 'inventory.return.manage') && $this->covers($user, $purchase->campus_id);
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $this->may($user, 'inventory.purchase.manage') && $this->covers($user, $purchase->campus_id);
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $this->may($user, 'inventory.purchase.delete') && $this->covers($user, $purchase->campus_id);
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
