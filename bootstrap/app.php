<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureArtisanUiAccess;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectPortalUsersFromDashboard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            EnsureSubscriptionActive::class,
        ]);

        $middleware->alias([
            // `permission` keeps this project's own middleware so an
            // unauthorised request lands on the styled 403 page rather than
            // Spatie's bare exception.
            'permission' => CheckPermission::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'artisan.ui' => EnsureArtisanUiAccess::class,
            'redirect.portal-users' => RedirectPortalUsersFromDashboard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $exception, $request) {
            if ($request->expectsJson() || $request->is('api/*') || ! $request->isMethod('GET')) {
                return $response;
            }

            $status = $response->getStatusCode();

            $pages = [
                401 => 'errors/401',
                403 => 'errors/403',
                404 => 'errors/404',
                419 => 'errors/419',
                429 => 'errors/429',
                500 => 'errors/500',
                503 => 'errors/503',
            ];

            if (! array_key_exists($status, $pages)) {
                return $response;
            }

            return Inertia::render($pages[$status], [
                'status' => $status,
            ])->toResponse($request)->setStatusCode($status);
        });
    })->create();
