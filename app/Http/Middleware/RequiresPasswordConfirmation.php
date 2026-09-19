<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RequiresPasswordConfirmation
{
    /**
     * Re-verify the acting user's own password, server-side, before a
     * sensitive action (activate/deactivate/delete/status-change) runs.
     *
     * The frontend confirm modal collects a `password` field and sends it
     * with the action request; this is the one place that actually checks
     * it, so the check cannot be skipped by calling the endpoint directly.
     * A `ValidationException` gives both callers a clear error: Inertia
     * redirects back with the message in the `password` error bag, and a
     * JSON/axios caller gets the same message in a 422 response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $password = (string) $request->input('password', '');

        if ($password === '' || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'The provided password is incorrect.',
            ]);
        }

        return $next($request);
    }
}
