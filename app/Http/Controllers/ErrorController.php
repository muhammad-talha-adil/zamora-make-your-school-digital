<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

class ErrorController extends Controller
{
    /**
     * Handle 404 Not Found errors.
     */
    public function notFound(Request $request): Response
    {
        $exception = $request->query('exception', '');
        
        return Inertia::render('errors/404', [
            'message' => 'The requested page was not found.',
            'exception' => App::environment('local') ? $exception : null,
            'status' => 404,
        ]);
    }

    /**
     * Handle 419 Page Expired errors (CSRF).
     */
    public function expired(Request $request): Response
    {
        return Inertia::render('errors/419', [
            'message' => 'Page expired due to session timeout or CSRF token mismatch.',
            'status' => 419,
        ]);
    }

    /**
     * Handle 403 Forbidden errors.
     */
    public function forbidden(Request $request): Response
    {
        return Inertia::render('errors/403', [
            'message' => 'You do not have permission to access this page.',
            'status' => 403,
        ]);
    }

    /**
     * Handle 500 Internal Server errors.
     */
    public function serverError(Request $request): Response
    {
        $exception = $request->query('exception', '');
        
        return Inertia::render('errors/500', [
            'message' => 'An unexpected error occurred. Please try again later.',
            'exception' => App::environment('local') ? $exception : null,
            'status' => 500,
        ]);
    }
}
