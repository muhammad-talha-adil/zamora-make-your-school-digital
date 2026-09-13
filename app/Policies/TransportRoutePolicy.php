<?php

namespace App\Policies;

use App\Models\TransportRoute;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's transport routes.
 *
 * See `TransportVehiclePolicy` for the background: the `transport.*`
 * permissions were seeded but never checked anywhere.
 */
class TransportRoutePolicy
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

    public function update(User $user, TransportRoute $route): bool
    {
        return $this->may($user, 'transport.route.manage') && $this->covers($user, $route->campus_id);
    }

    /**
     * Whether this record falls inside the user's reach.
     *
     * A route with no campus is a school-wide route, reachable by anybody
     * who may manage routes at all.
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
