<?php

namespace App\Policies\Timetable;

use App\Models\Timetable\TimetableEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksSchoolReach;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Who may read and who may change a class timetable.
 *
 * Changing it takes `academics.timetable.manage` and reach over the section.
 * Reading takes `academics.timetable.view` — which students and guardians hold
 * (there is no `.view.own` sibling) — so a read is decided by
 * `TimetableEntry::scopeVisibleTo()`, the single statement of whose timetable
 * a family or a teacher may see. The list and the record cannot disagree.
 */
class TimetableEntryPolicy
{
    use ChecksSchoolReach;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->may($user, 'academics.timetable.view', 'academics.timetable.manage');
    }

    public function view(User $user, TimetableEntry $entry): bool
    {
        return $this->viewAny($user)
            && TimetableEntry::visibleTo($user)->whereKey($entry->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $this->may($user, 'academics.timetable.manage');
    }

    /**
     * Whether the user may schedule into a section of this class.
     */
    public function manageSection(User $user, ?int $campusId, ?int $classId, ?int $sectionId): bool
    {
        return $this->may($user, 'academics.timetable.manage')
            && $this->reaches($user, $campusId, $classId, $sectionId);
    }

    public function update(User $user, TimetableEntry $entry): bool
    {
        return $this->manageSection($user, $entry->campus_id, $entry->class_id, $entry->section_id);
    }

    public function delete(User $user, TimetableEntry $entry): bool
    {
        return $this->update($user, $entry);
    }
}
