<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureArtisanUiAccess
{
    /**
     * Gate the artisan/cache command UI behind the `developer` role and a
     * production kill switch.
     *
     * This dashboard can run destructive Artisan commands, so it is
     * deliberately scoped tighter than `isSuperAdmin()` (owner/super_admin
     * included) — only `developer` may reach it, matching the
     * `Gate::before` bypass reserved for that role elsewhere in the app.
     * In production it additionally refuses to load unless
     * `config('app.allow_artisan_ui')` has been explicitly enabled via the
     * `ALLOW_ARTISAN_UI` environment variable.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->isDeveloper()) {
            abort(403, 'Unauthorized action. You do not have permission to access this resource.');
        }

        if (app()->environment('production') && ! config('app.allow_artisan_ui')) {
            abort(403, 'The Artisan command UI is disabled in production.');
        }

        return $next($request);
    }
}
