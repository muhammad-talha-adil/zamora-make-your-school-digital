<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    /**
     * Route names that stay reachable regardless of subscription status:
     * authentication (Fortify), the health check, and the developer's own
     * subscription-management page, which must remain reachable to fix a
     * blocked subscription in the first place.
     *
     * @var list<string>
     */
    private const EXEMPT_ROUTE_NAME_PREFIXES = [
        'login',
        'logout',
        'register',
        'password.',
        'two-factor.',
        'verification.',
        'sanctum.',
        'subscription.',
    ];

    /**
     * @var list<string>
     */
    private const EXEMPT_ROUTE_NAMES = [
        'home',
        'about',
        'contact',
    ];

    /**
     * Global enforcement: once a school's demo has run out, its subscription
     * has expired, or the developer has suspended it, every request except a
     * `developer`-role user's is redirected to the locked-out page.
     *
     * This never deletes or modifies application data - it only reads the
     * single `subscriptions` row and, if it is blocking, redirects.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDeveloper()) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        $subscription = Subscription::current();

        if (! $subscription->isBlocking()) {
            return $next($request);
        }

        if ($request->routeIs('subscription.locked')) {
            return $next($request);
        }

        return redirect()->route('subscription.locked');
    }

    private function isExempt(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            // Vite assets are served statically and never reach this
            // middleware; anything else without a route name (the health
            // check, unnamed fallback routes) is left alone rather than
            // guessed at.
            return true;
        }

        if ($routeName === 'up') {
            return true;
        }

        if (in_array($routeName, self::EXEMPT_ROUTE_NAMES, true)) {
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
