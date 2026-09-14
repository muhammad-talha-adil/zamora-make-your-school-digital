<?php

/**
 * Infrastructure half of the student/guardian portal (see
 * docs/STUDENT-PORTAL-READINESS.md): the shared `menus` Inertia prop is
 * curated for a pure student/guardian viewer without touching any existing
 * `Menu::role` value, and login/direct navigation route student/guardian
 * accounts to `/portal` instead of the admin `/dashboard`.
 */

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;

function makePortalTestUser(string $role): User
{
    Role::firstOrCreate(
        ['name' => $role, 'guard_name' => 'web'],
        ['label' => ucfirst($role), 'scope_level' => Role::SCOPE_SELF, 'is_active' => true]
    );

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * Recreates the shape MenuSeeder builds: a handful of admin sections plus
 * the new Portal group and the footer Profile/Appearance items - enough to
 * prove the filter without depending on the full 573-line seeder.
 */
function seedSamplePortalMenus(): void
{
    $students = Menu::create(['title' => 'Students', 'icon' => 'users', 'type' => 'main', 'order' => 1, 'is_active' => true]);
    Menu::create(['title' => 'Student List', 'icon' => 'list', 'type' => 'main', 'order' => 1, 'parent_id' => $students->id, 'is_active' => true, 'url' => '/students']);

    $finance = Menu::create(['title' => 'Finance', 'icon' => 'wallet', 'type' => 'main', 'order' => 2, 'is_active' => true]);
    Menu::create(['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'type' => 'main', 'order' => 1, 'parent_id' => $finance->id, 'is_active' => true, 'url' => '/finance']);

    $portal = Menu::create(['title' => 'My Portal', 'icon' => 'home', 'type' => 'main', 'order' => 3, 'is_active' => true]);
    Menu::create(['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'type' => 'main', 'order' => 1, 'parent_id' => $portal->id, 'is_active' => true, 'url' => '/portal']);
    Menu::create(['title' => 'Fees', 'icon' => 'wallet', 'type' => 'main', 'order' => 2, 'parent_id' => $portal->id, 'is_active' => true, 'url' => '/portal/fees']);
    Menu::create(['title' => 'Exam Results', 'icon' => 'clipboard-list', 'type' => 'main', 'order' => 3, 'parent_id' => $portal->id, 'is_active' => true, 'url' => '/portal/exams']);
    Menu::create(['title' => 'Attendance', 'icon' => 'calendar-check', 'type' => 'main', 'order' => 4, 'parent_id' => $portal->id, 'is_active' => true, 'url' => '/portal/attendance']);

    $settings = Menu::create(['title' => 'Settings', 'icon' => 'settings', 'type' => 'footer', 'order' => 1, 'is_active' => true]);
    Menu::create(['title' => 'Profile', 'icon' => 'user', 'type' => 'footer', 'order' => 1, 'parent_id' => $settings->id, 'is_active' => true, 'url' => '/settings/profile']);
    Menu::create(['title' => 'Appearance', 'icon' => 'palette', 'type' => 'footer', 'order' => 2, 'parent_id' => $settings->id, 'is_active' => true, 'url' => '/settings/appearance']);
    Menu::create(['title' => 'School Profile', 'icon' => 'building', 'type' => 'footer', 'order' => 3, 'parent_id' => $settings->id, 'is_active' => true, 'url' => '/settings/school-profile']);
}

test('a student-only viewer sees only the portal group and profile/appearance in their menu', function () {
    seedSamplePortalMenus();
    $user = makePortalTestUser('student');

    // The dashboard itself redirects student/guardian accounts to /portal
    // (see the redirect test below), but the shared `menus` prop is built
    // by middleware regardless of destination, so hitting a page that does
    // not redirect (Profile, which every role can reach) is enough to
    // inspect it.
    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();

    $response->assertInertia(function ($page) {
        $main = collect($page->toArray()['props']['menus']['main']);
        $footer = collect($page->toArray()['props']['menus']['footer']);

        expect($main->pluck('title')->all())->toBe(['My Portal']);
        expect($main->first()['children'])->toHaveCount(4);
        expect(collect($main->first()['children'])->pluck('title')->all())
            ->toBe(['Dashboard', 'Fees', 'Exam Results', 'Attendance']);

        expect($footer->pluck('title')->all())->toBe(['Settings']);
        expect(collect($footer->first()['children'])->pluck('title')->all())
            ->toBe(['Profile', 'Appearance']);
    });
});

test('a guardian-only viewer sees only the portal group and profile/appearance in their menu', function () {
    seedSamplePortalMenus();
    $user = makePortalTestUser('guardian');

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();

    $response->assertInertia(function ($page) {
        $main = collect($page->toArray()['props']['menus']['main']);

        expect($main->pluck('title')->all())->toBe(['My Portal']);
    });
});

test('an owner viewer menu is unaffected by the portal-only filter', function (string $role) {
    seedSamplePortalMenus();
    $user = makePortalTestUser($role);

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();

    $response->assertInertia(function ($page) {
        $main = collect($page->toArray()['props']['menus']['main']);
        $footer = collect($page->toArray()['props']['menus']['footer']);

        // All three top-level admin groups plus Portal, exactly as seeded -
        // nothing hidden for a non-student/guardian viewer.
        expect($main->pluck('title')->all())->toBe(['Students', 'Finance', 'My Portal']);
        expect(collect($footer->first()['children'])->pluck('title')->all())
            ->toBe(['Profile', 'Appearance', 'School Profile']);
    });
})->with(['owner', 'campus_admin', 'teacher']);

test('a student is redirected from the login screen straight to the portal', function () {
    $user = makePortalTestUser('student');

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/portal');
});

test('a guardian navigating straight to /dashboard is redirected to the portal, not shown or blocked', function () {
    $user = makePortalTestUser('guardian');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect('/portal');
});

test('a non-portal user still lands on and can see the admin dashboard', function () {
    $user = makePortalTestUser('owner');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});
