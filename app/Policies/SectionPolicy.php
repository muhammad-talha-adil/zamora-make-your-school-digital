<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's sections.
 *
 * A section is part of a class's structure, so it shares
 * `academics.class.manage` rather than defining its own ability.
 */
class SectionPolicy
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

    public function update(User $user, Section $section): bool
    {
        return $this->may($user, 'academics.class.manage');
    }

    public function delete(User $user, Section $section): bool
    {
        return $this->may($user, 'academics.class.manage');
    }
}
