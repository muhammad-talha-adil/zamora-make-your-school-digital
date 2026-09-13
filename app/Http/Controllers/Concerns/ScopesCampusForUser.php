<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Resolves which campus a request may see.
 *
 * The Inventory controllers all read `campus_id` straight off the query
 * string and trust it — a signed-in user restricted to one campus (anyone
 * without a school-wide role) could pass any other campus's id and list or
 * mutate its stock, purchases, suppliers and student assignments. This is the
 * same "campus reach" gap `ChecksSchoolReach` closes for Student/Exam/
 * Attendance, applied at the point these controllers build their queries: a
 * campus-restricted user's own campus always wins over whatever the request
 * asked for, while a school-wide user (`isSuperAdmin()`) keeps picking any
 * campus, including none (no filter).
 */
trait ScopesCampusForUser
{
    /**
     * The campus id to scope this request's query to.
     *
     * Null means "do not filter by campus" — only returned for a school-wide
     * user who did not select one.
     */
    protected function resolveCampusId(Request $request): ?int
    {
        $user = $request->user();

        if ($user && $user->isCampusRestricted()) {
            $ownCampusId = $user->campusId();

            if ($ownCampusId !== null) {
                return $ownCampusId;
            }
        }

        $campusId = $request->get('campus_id');

        return $campusId !== null && $campusId !== '' ? (int) $campusId : null;
    }
}
