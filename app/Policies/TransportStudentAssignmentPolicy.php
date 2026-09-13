<?php

namespace App\Policies;

use App\Models\TransportStudentAssignment;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change a student's transport assignment, and who may run
 * the monthly dues generation over them.
 *
 * See `TransportVehiclePolicy` for the background on why this policy exists
 * at all.
 */
class TransportStudentAssignmentPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'transport.view', 'transport.view.own', 'transport.assignment.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'transport.assignment.manage');
    }

    public function update(User $user, TransportStudentAssignment $assignment): bool
    {
        return $this->may($user, 'transport.assignment.manage') && $this->covers($user, $assignment->campus_id);
    }

    /**
     * Generating monthly transport dues from active assignments.
     */
    public function generateDues(User $user): bool
    {
        return $this->may($user, 'transport.assignment.manage');
    }

    /**
     * Whether this record falls inside the user's reach.
     *
     * An assignment with no campus stays reachable by anybody who may manage
     * assignments at all.
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
