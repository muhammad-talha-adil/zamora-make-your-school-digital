<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Gate a route behind one or more Spatie roles.
     *
     * Several roles may be given, pipe-separated, and holding any one of
     * them is enough:
     *
     *     ->middleware('role.only:owner|developer')
     *
     * Kept in place of Spatie's own `role` middleware so a refusal aborts
     * with 403 and renders this app's error page, rather than raising
     * Spatie's UnauthorizedException — same reasoning as {@see CheckPermission}.
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = collect(explode('|', $roles))
            ->map(fn (string $role) => trim($role))
            ->filter()
            ->contains(fn (string $role) => $user->hasRole($role));

        if (! $allowed) {
            abort(403, 'Unauthorized action. You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
