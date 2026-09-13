<?php

namespace App\Policies;

use App\Models\TransportVehicleExpense;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and record the school's transport vehicle expenses.
 *
 * See `TransportVehiclePolicy` for the background on why this policy exists
 * at all.
 */
class TransportVehicleExpensePolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'transport.view', 'transport.expense.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'transport.expense.manage');
    }

    public function update(User $user, TransportVehicleExpense $expense): bool
    {
        return $this->may($user, 'transport.expense.manage') && $this->covers($user, $expense->campus_id);
    }

    /**
     * Whether this record falls inside the user's reach.
     *
     * An expense with no campus stays reachable by anybody who may manage
     * expenses at all.
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
