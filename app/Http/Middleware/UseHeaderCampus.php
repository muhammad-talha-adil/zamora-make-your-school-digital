<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records always belong to the campus picked in the header switcher, so exam
 * screens never ask for a campus and a stale client value is ignored.
 */
class UseHeaderCampus
{
    public function handle(Request $request, Closure $next): Response
    {
        $campusId = $request->user()?->campusId();

        if ($campusId !== null) {
            $request->merge(['campus_id' => $campusId]);
            $request->query->set('campus_id', (string) $campusId);
        }

        return $next($request);
    }
}
