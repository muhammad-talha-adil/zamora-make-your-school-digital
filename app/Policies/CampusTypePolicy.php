<?php

namespace App\Policies;

use App\Models\CampusType;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the campus types a campus can be tagged with.
 *
 * A lookup for campuses, so it shares the `school.campus.*` abilities rather
 * than defining its own.
 */
class CampusTypePolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'school.campus.view', 'school.campus.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'school.campus.manage');
    }

    public function update(User $user, CampusType $campusType): bool
    {
        return $this->may($user, 'school.campus.manage');
    }

    public function delete(User $user, CampusType $campusType): bool
    {
        return $this->may($user, 'school.campus.delete');
    }
}
