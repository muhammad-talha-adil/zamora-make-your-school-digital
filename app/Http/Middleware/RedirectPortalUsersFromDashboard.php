<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectPortalUsersFromDashboard
{
    /**
     * A `student`/`guardian` account has no admin dashboard to see. Rather
     * than 403 them for an old bookmark or stray link, send them to the
     * portal instead - see `docs/STUDENT-PORTAL-READINESS.md` section 4.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasAnyRole(['student', 'guardian'])) {
            return redirect(Route::has('portal.index') ? route('portal.index') : '/portal');
        }

        return $next($request);
    }
}
