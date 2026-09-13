<?php

namespace App\Policies;

use App\Models\TransportStop;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's transport stops.
 *
 * Stops are managed alongside routes, so they share `transport.route.manage`
 * rather than having their own ability. See `TransportVehiclePolicy` for the
 * background on why this policy exists at all.
 */
class TransportStopPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'transport.view', 'transport.route.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'transport.route.manage');
    }

    public function update(User $user, TransportStop $stop): bool
    {
        return $this->may($user, 'transport.route.manage') && $this->covers($user, $stop->campus_id);
    }

    /**
     * Whether this record falls inside the user's reach.
     *
     * A stop with no campus is a school-wide stop, reachable by anybody who
     * may manage routes at all.
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
