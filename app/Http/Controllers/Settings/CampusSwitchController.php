<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets a school-wide user (owner/developer) narrow the whole admin panel to
 * one campus at a time from the header switcher. Stored in the session only
 * — every controller that already calls
 * `ScopesCampusForUser::resolveCampusId()` or `ChecksSchoolReach::reaches()`
 * picks this up automatically, so no other page needed to change for the
 * switcher to take effect.
 *
 * There is no "all campuses" option — the client asked for a single active
 * campus at all times, so `campus_id` is required here.
 */
class CampusSwitchController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
        ]);

        $request->session()->put('viewing_campus_id', $validated['campus_id']);

        return redirect()->back();
    }
}
