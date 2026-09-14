<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a single supplier.
 *
 * `show`/`edit`/`update`/`destroy`/`activate`/`inactivate` all scoped campus
 * access via an optional `campus_id` request parameter — a campus-restricted
 * user could omit it, or spoof another campus's id, and still reach a
 * supplier that does not belong to them. This checks the resolved model
 * instance instead.
 */
class SupplierPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->may($user, 'inventory.view', 'inventory.supplier.manage') && $this->covers($user, $supplier->campus_id);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->may($user, 'inventory.supplier.manage') && $this->covers($user, $supplier->campus_id);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->may($user, 'inventory.supplier.manage') && $this->covers($user, $supplier->campus_id);
    }

    /**
     * Whether this record falls inside the user's reach.
     *
     * A supplier with no campus is an "all campuses" supplier, reachable by
     * anybody who may manage suppliers at all.
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
