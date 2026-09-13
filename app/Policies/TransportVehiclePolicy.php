<?php

namespace App\Policies;

use App\Models\TransportVehicle;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's transport vehicles.
 *
 * The six `transport.*` permissions have been seeded since the beginning and
 * not one of them was checked — all eleven transport routes sat on `auth`
 * alone, so any signed-in account (a student's own portal login included)
 * could create vehicles, reassign routes, or read every campus's fleet.
 */
class TransportVehiclePolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'transport.view', 'transport.vehicle.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'transport.vehicle.manage');
    }

    public function update(User $user, TransportVehicle $vehicle): bool
    {
        return $this->may($user, 'transport.vehicle.manage') && $this->covers($user, $vehicle->campus_id);
    }

    /**
     * Whether this record falls inside the user's reach.
     *
     * A vehicle with no campus is a school-wide asset, reachable by anybody
     * who may manage vehicles at all.
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
