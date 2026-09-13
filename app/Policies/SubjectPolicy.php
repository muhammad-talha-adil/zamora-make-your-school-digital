<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may see and change the school's subjects.
 *
 * A school-wide lookup, no campus reach to check — only the
 * `academics.subject.manage` ability.
 */
class SubjectPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'academics.subject.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'academics.subject.manage');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $this->may($user, 'academics.subject.manage');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $this->may($user, 'academics.subject.manage');
    }
}
