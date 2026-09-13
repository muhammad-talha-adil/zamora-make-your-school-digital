<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's classes.
 *
 * A class is a school-wide structural lookup, no campus reach to check —
 * only the `academics.class.manage` ability, which also covers sections.
 */
class SchoolClassPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'academics.class.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'academics.class.manage');
    }

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        return $this->may($user, 'academics.class.manage');
    }

    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        return $this->may($user, 'academics.class.manage');
    }
}
