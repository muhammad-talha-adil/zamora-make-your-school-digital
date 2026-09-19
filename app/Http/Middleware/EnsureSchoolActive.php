<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolActive
{
    /**
     * Route names that stay reachable even while the school is switched off:
     * authentication (Fortify, so a blocked user can still reach the login
     * page and log out) and the health check.
     *
     * @var list<string>
     */
    private const EXEMPT_ROUTE_NAME_PREFIXES = [
        'login',
        'logout',
        'password.',
        'two-factor.',
        'verification.',
        'sanctum.',
    ];

    /**
     * Global enforcement of the "School is Active" toggle on
     * `/settings/school-profile`: once a school has switched itself off,
     * every signed-in request except a `developer`/`owner` user's is turned
     * away with a friendly 403, on this request and every one after it — not
     * just at the login form. A user already mid-session when the toggle
     * flips is locked out on their very next request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->hasAnyRole(['developer', 'owner'])) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        $school = School::first();

        if ($school && ! $school->is_active) {
            abort(403, 'This school has been deactivated. Please contact your school owner or administrator.');
        }

        return $next($request);
    }

    private function isExempt(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            return true;
        }

        if ($routeName === 'up') {
            return true;
        }

        foreach (self::EXEMPT_ROUTE_NAME_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
