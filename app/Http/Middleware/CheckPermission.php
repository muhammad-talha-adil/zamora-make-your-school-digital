<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Gate a route behind one or more permissions.
     *
     * Several abilities may be given, pipe-separated, and holding any one of
     * them is enough:
     *
     *     ->middleware('permission:fee.voucher.create|fee.voucher.edit')
     *
     * Kept in place of Spatie's own middleware so a refusal aborts with 403
     * and renders this app's error page, rather than raising Spatie's
     * UnauthorizedException.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = collect(explode('|', $permission))
            ->map(fn (string $ability) => trim($ability))
            ->filter()
            ->contains(fn (string $ability) => $user->hasPermission($ability));

        if (! $allowed) {
            abort(403, 'Unauthorized action. You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
