<?php

namespace App\Policies\Exam;

use App\Models\User;

/**
 * How far into the school somebody's exam access reaches.
 *
 * There is one statement of this and the three exam policies share it, for the
 * same reason the attendance policy keeps its widths in one method: the moment
 * each policy decides for itself, they drift, and the drift is always in the
 * permissive direction.
 *
 * The three widths:
 *
 *  - **School-wide** — developer, owner, super admin. Every campus.
 *  - **Campus** — campus admin, head teacher. Their own campus, every class in
 *    it. A head teacher sits here until wings are modelled.
 *  - **Class** — a teacher. Only the sections they have been given.
 *
 * A permission says *what* somebody may do; this says *whose records*. Both
 * have to pass, and the permission is checked by the route middleware before
 * any of this is reached.
 */
trait ChecksExamReach
{
    /**
     * Whether a record placed in this campus, class and section is within
     * the user's reach.
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
