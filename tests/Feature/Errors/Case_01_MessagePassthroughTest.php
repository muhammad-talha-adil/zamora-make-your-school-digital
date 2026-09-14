<?php

/**
 * The custom errors/{status} pages used to show only a generic, one-size
 * fits-all sentence for every 403 in the app — a student whose account has
 * no linked student record (a real, expected case, not a permission
 * mistake) got told "this area is reserved for specific staff roles",
 * which is both wrong and confusing. `abort(403, '...')`'s message is now
 * threaded through to the page for the client-facing statuses (never
 * 500/503, whose messages can carry internal detail).
 */

use App\Models\User;
use Tests\Support\PortalWorld;

it('shows the real reason on the 403 page instead of the generic staff-only sentence', function () {
    PortalWorld::make();

    $user = User::factory()->create();
    $user->syncRoles(['student']);

    $response = $this->actingAs($user)->get(route('portal.index'));

    $response->assertForbidden();
    $response->assertInertia(fn ($page) => $page
        ->component('errors/403')
        ->where('status', 403)
        ->where('message', 'No student record is linked to this account.')
    );
});
