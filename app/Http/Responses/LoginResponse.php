<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Every role except `student`/`guardian` keeps Fortify's normal
     * `config('fortify.home')` destination (`/dashboard`). A `student` or
     * `guardian` account has no business on the admin dashboard, so they are
     * sent straight to the portal instead - see
     * `docs/STUDENT-PORTAL-READINESS.md` section 4.
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->hasAnyRole(['student', 'guardian'])) {
            return redirect()->intended(Route::has('portal.index') ? route('portal.index') : '/portal');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
