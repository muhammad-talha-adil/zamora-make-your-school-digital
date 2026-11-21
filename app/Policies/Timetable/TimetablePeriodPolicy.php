<?php

namespace App\Policies\Timetable;

use App\Models\Timetable\TimetablePeriod;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may read and who may change the periods of the school day.
 *
 * Periods are campus-wide, so reach is the campus alone.
 */
class TimetablePeriodPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'academics.timetable.view', 'academics.timetable.manage');
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'academics.timetable.manage');
    }

    public function update(User $user, TimetablePeriod $period): bool
    {
        return $this->may($user, 'academics.timetable.manage') && $this->reaches($user, $period->campus_id);
    }

    public function delete(User $user, TimetablePeriod $period): bool
    {
        return $this->update($user, $period);
    }
}
