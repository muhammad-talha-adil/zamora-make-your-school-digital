<?php

/**
 * Subscription/demo-mode control: a single `subscriptions` row governs
 * whether the whole app is reachable. A developer must always get through
 * regardless of status; everyone else must be redirected to the locked-out
 * page, with the right reason, once the subscription blocks access.
 *
 * The owner was explicit that expiry/suspension must never touch existing
 * data - only access is locked - so this also asserts no destructive query
 * appears anywhere in the feature.
 */

use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;

function makeSubscriptionUserWithRole(string $role): User
{
    Role::firstOrCreate(
        ['name' => $role, 'guard_name' => 'web'],
        ['label' => ucfirst($role), 'scope_level' => Role::SCOPE_SYSTEM, 'is_active' => true]
    );

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('lets a developer through no matter the subscription status', function (string $status) {
    Subscription::current()->update(['status' => $status]);
    $developer = makeSubscriptionUserWithRole('developer');

    $this->actingAs($developer)->get(route('dashboard'))->assertOk();
})->with(['demo', 'active', 'suspended', 'expired']);

it('blocks a non-developer when the subscription is suspended', function () {
    Subscription::current()->update(['status' => 'suspended']);
    $owner = makeSubscriptionUserWithRole('owner');

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.locked'));
});

it('blocks a non-developer when the demo has expired', function () {
    Subscription::current()->update([
        'status' => 'demo',
        'demo_expires_at' => now()->subDay(),
    ]);
    $campusAdmin = makeSubscriptionUserWithRole('campus_admin');

    $this->actingAs($campusAdmin)
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.locked'));
});

it('blocks a non-developer when the paid subscription has expired', function () {
    Subscription::current()->update([
        'status' => 'active',
        'subscription_expires_at' => now()->subDay(),
    ]);
    $owner = makeSubscriptionUserWithRole('owner');

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.locked'));
});

it('does not block a demo that has not expired yet', function () {
    Subscription::current()->update([
        'status' => 'demo',
        'demo_expires_at' => now()->addDays(5),
    ]);
    $owner = makeSubscriptionUserWithRole('owner');

    $this->actingAs($owner)->get(route('dashboard'))->assertOk();
});

it('does not block an active subscription with no expiry (lifetime)', function () {
    Subscription::current()->update([
        'status' => 'active',
        'subscription_expires_at' => null,
    ]);
    $owner = makeSubscriptionUserWithRole('owner');

    $this->actingAs($owner)->get(route('dashboard'))->assertOk();
});

it('shows the right block reason on the locked page', function () {
    Subscription::current()->update([
        'status' => 'suspended',
        'block_reason' => 'hosting_expiring',
    ]);
    $owner = makeSubscriptionUserWithRole('owner');

    $response = $this->actingAs($owner)->get(route('dashboard'));
    $response->assertRedirect(route('subscription.locked'));

    $lockedResponse = $this->actingAs($owner)->get(route('subscription.locked'));
    $lockedResponse->assertOk();
    $lockedResponse->assertInertia(fn ($page) => $page
        ->component('SubscriptionLocked')
        ->where('blockReason', 'hosting_expiring')
    );
});

it('lets everyone reach the login page and the locked page even while blocked', function () {
    Subscription::current()->update(['status' => 'suspended']);

    $this->get(route('login'))->assertOk();
    $this->get(route('subscription.locked'))->assertOk();
});

it('lets a developer view and update the subscription row', function () {
    $developer = makeSubscriptionUserWithRole('developer');

    $this->actingAs($developer)->get(route('subscription.index'))->assertOk();

    $response = $this->actingAs($developer)->patch(route('subscription.update'), [
        'status' => 'suspended',
        'block_reason' => 'other',
        'block_reason_note' => 'Non-payment',
        'demo_expires_at' => null,
        'subscription_expires_at' => null,
        'notes' => 'Suspended pending payment.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $subscription = Subscription::current();
    expect($subscription->status)->toBe('suspended');
    expect($subscription->block_reason)->toBe('other');
    expect($subscription->block_reason_note)->toBe('Non-payment');
    expect($subscription->updated_by)->toBe($developer->id);
});

it('turns away a non-developer from the subscription management page', function (string $role) {
    $user = makeSubscriptionUserWithRole($role);

    $this->actingAs($user)->get(route('subscription.index'))->assertForbidden();
    $this->actingAs($user)->patch(route('subscription.update'), ['status' => 'active'])->assertForbidden();
})->with(['owner', 'super_admin', 'campus_admin']);

it('carries the 10-day warning banner data when within the window, not outside it', function () {
    $owner = makeSubscriptionUserWithRole('owner');

    Subscription::current()->update([
        'status' => 'active',
        'subscription_expires_at' => now()->addDays(5),
    ]);
    $this->actingAs($owner)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('subscriptionWarning.daysRemaining', 5)
        );

    Subscription::current()->update([
        'subscription_expires_at' => now()->addDays(30),
    ]);
    $this->actingAs($owner)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('subscriptionWarning', null));
});

it('does not show the warning banner to roles outside owner/campus_admin/super_admin', function () {
    Subscription::current()->update([
        'status' => 'active',
        'subscription_expires_at' => now()->addDays(3),
    ]);
    $teacher = makeSubscriptionUserWithRole('teacher');

    $this->actingAs($teacher)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('subscriptionWarning', null));
});

it('never deletes or destructively touches data anywhere in the subscription feature', function () {
    $middleware = file_get_contents(app_path('Http/Middleware/EnsureSubscriptionActive.php'));
    $controller = file_get_contents(app_path('Http/Controllers/Settings/SubscriptionController.php'));
    $model = file_get_contents(app_path('Models/Subscription.php'));

    foreach ([$middleware, $controller, $model] as $source) {
        expect($source)->not->toContain('::delete(')
            ->not->toContain('->delete()')
            ->not->toContain('truncate')
            ->not->toContain('forceDelete');
    }
});
