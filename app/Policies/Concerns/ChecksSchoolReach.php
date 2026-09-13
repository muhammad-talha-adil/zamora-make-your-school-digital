<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * How far into the school somebody's access reaches.
 *
 * There is **one** statement of this and every policy shares it. The moment two
 * policies decide for themselves, they drift, and the drift is always in the
 * permissive direction — which is how the attendance, exam and student modules
 * each ended up letting a teacher at one campus read another campus's children.
 *
 * The three widths:
 *
 *  - **School-wide** — developer, owner, super admin. Every campus.
 *  - **Campus** — campus admin, head teacher. Their own campus, every class in
 *    it. A head teacher sits here until wings are modelled, which is the more
 *    restrictive of the two readings we can support today.
 *  - **Class** — a teacher. Only the sections they have been given.
 *
 * A permission says *what* somebody may do; this says *whose records*. Both
 * have to pass, and the permission is usually checked by route middleware
 * before any of this is reached.
 */
trait ChecksSchoolReach
{
    /**
     * Whether a record placed in this campus, class and section is within the
     * user's reach.
     *
     * A null campus on the record means it belongs to no campus in particular —
     * a school-wide row — and does not narrow anything.
     */
    protected function reaches(
        User $user,
        ?int $campusId,
        ?int $classId = null,
        ?int $sectionId = null,
        ?int $sessionId = null
    ): bool {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $userCampus = $user->campusId();

        if ($userCampus !== null && $campusId !== null && (int) $campusId !== (int) $userCampus) {
            return false;
        }

        if (! $user->isClassRestricted()) {
            return true;
        }

        // A teacher with no class yet reaches nothing, rather than everything.
        return $user->teachesSection($classId, $sectionId, $sessionId);
    }

    /**
     * Whether the user holds any of these abilities.
     */
    protected function may(User $user, string ...$permissions): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
