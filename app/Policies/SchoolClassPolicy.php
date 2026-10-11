<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's classes.
 *
 * A class now belongs to one campus, so the usual campus reach applies on
 * top of the `academics.class.manage` ability, which also covers sections.
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
        return $this->may($user, 'academics.class.manage') && $this->reaches($user, $schoolClass->campus_id);
    }

    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        return $this->may($user, 'academics.class.manage') && $this->reaches($user, $schoolClass->campus_id);
    }
}
