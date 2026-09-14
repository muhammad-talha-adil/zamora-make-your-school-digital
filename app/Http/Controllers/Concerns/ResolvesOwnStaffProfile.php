<?php

namespace App\Http\Controllers\Concerns;

use App\Models\StaffProfile;
use App\Models\User;

/**
 * Resolving "my own staff record" with no route parameter.
 *
 * Mirrors {@see ResolvesOwnStudent}: a member of staff signs in and reads
 * their own record directly, with no need to already know their numeric
 * `staffProfile` id. Unlike a student, one user has at most one staff
 * record, so there is no "which one" question to ask — just whether one
 * exists at all.
 */
trait ResolvesOwnStaffProfile
{
    /**
     * The staff record this viewer may read as "their own".
     *
     * The developer role has no staff record of its own — it previews these
     * screens the way a support ticket would need to, against any real staff
     * profile, defaulting to the first one so the bare `/staff/me` route
     * still resolves without a query string.
     */
    protected function resolveOwnStaffProfile(User $user): StaffProfile
    {
        if ($user->staffProfile) {
            return $user->staffProfile;
        }

        if ($user->isDeveloper()) {
            return StaffProfile::query()->orderBy('id')->firstOrFail();
        }

        abort(403, 'No staff record is linked to this account.');
    }
}
