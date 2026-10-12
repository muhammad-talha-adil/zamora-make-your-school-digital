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

        // A school-wide user (owner/developer) may still pass an explicit
        // campus_id to filter an in-page list (e.g. Inventory Settings'
        // campus dropdown) to a campus other than the header switcher's
        // current pick — this is a free in-page filter choice for them, not
        // a security boundary, since they can already reach every campus.
        // Campus-restricted staff always get forced to their own campus
        // regardless of what the request asks for.
        if ($user?->isSuperAdmin()) {
            $requested = $request->get('campus_id');

            if ($requested !== null && $requested !== '') {
                return (int) $requested;
            }
        }

        $campusId = $user?->campusId();

        if ($campusId !== null) {
            return $campusId;
        }

        $campusId = $request->get('campus_id');

        return $campusId !== null && $campusId !== '' ? (int) $campusId : null;
    }
}
