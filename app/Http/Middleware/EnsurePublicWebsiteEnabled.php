<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicWebsiteEnabled
{
    /**
     * Guards the public marketing routes (home/about/contact): when the
     * school has switched its public website off, an anonymous visitor is
     * sent straight to the login page instead of seeing the public site.
     *
     * A logged-in user is never affected — they may be a staff member who
     * still needs to reach these pages, or simply unaffected by a toggle
     * that only concerns anonymous visitors to the marketing site.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $school = School::first();

        if ($school && ! $school->website_enabled) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
