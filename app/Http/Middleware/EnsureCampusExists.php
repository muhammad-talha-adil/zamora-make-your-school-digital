<?php

namespace App\Http\Middleware;

use App\Models\Campus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied (as the `campus.exists` alias) only to the route groups that file
 * their records under a campus - students, staff, fee, academics
 * (classes/sections/subjects), inventory, exam, transport. Not global: menus,
 * themes, the school profile toggle and other system/account settings have
 * nothing to do with campus scoping and must stay reachable regardless.
 *
 * Reads (GET) are left alone so the module can still be browsed while the
 * school is being set up; only the write actions that would create another
 * campus-less record are turned away.
 */
class EnsureCampusExists
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->isMethodSafe()) {
            return $next($request);
        }

        if (Campus::doesntExist()) {
            abort(403, 'Please create a campus first - every other record is filed under one.');
        }

        return $next($request);
    }
}
