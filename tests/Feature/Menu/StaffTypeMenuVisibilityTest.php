<?php

/**
 * The sidebar was curated for student/guardian viewers only (see
 * PortalAccessTest); every other non-admin role still saw the entire admin
 * sidebar (Fee Management, Inventory, Staff, Transport, Finance) even though
 * their permissions cover only a fraction of it. `MenuSeeder` now sets an
 * explicit `role` value on the rows a staff-type role's own permissions
 * (RolesSeeder) don't cover, while every row an admin role already saw stays
 * visible to that admin role (no `role` value ever drops one of the four
 * admin roles: developer, owner, super_admin, campus_admin).
 */

use App\Models\Menu;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Testing\TestResponse;

function seedRealMenusAndRoles(): void
{
    (new PermissionsSeeder)->run();
    (new RolesSeeder)->run();
    (new MenuSeeder)->run();
}

function makeMenuTestUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

/**
 * Flattens the shared `menus` prop (main + footer, parents + children) down
 * to a flat list of titles, so a test can assert on presence/absence without
 * caring about tree shape.
 *
 * @return array<int, string>
 */
function visibleMenuTitles(TestResponse $response): array
{
    $titles = [];

    $response->assertInertia(function ($page) use (&$titles) {
        $data = $page->toArray()['props']['menus'];

        $walk = function (array $nodes) use (&$walk, &$titles): void {
            foreach ($nodes as $node) {
                $titles[] = $node['title'];
                $walk($node['children'] ?? []);
            }
        };

        $walk($data['main']);
        $walk($data['footer']);
    });

    return $titles;
}

test('a teacher no longer sees modules their permissions do not cover', function () {
    seedRealMenusAndRoles();
    $user = makeMenuTestUser('teacher');

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();
    $titles = visibleMenuTitles($response);

    expect($titles)->not->toContain('Fee Management');
    expect($titles)->not->toContain('Inventory');
    expect($titles)->not->toContain('Transport');
    expect($titles)->not->toContain('Finance');
    expect($titles)->not->toContain('Staff Directory');
    expect($titles)->not->toContain('Payroll');
    expect($titles)->not->toContain('New Admission');

    // What a teacher genuinely can act on stays visible.
    expect($titles)->toContain('Dashboard');
    expect($titles)->toContain('Students');
    expect($titles)->toContain('Student List');
    expect($titles)->toContain('Exams');
    expect($titles)->toContain('Attendance');
    expect($titles)->toContain('Staff');
    expect($titles)->toContain('My Profile');
});

test('an accountant sees fee, finance and inventory but not staff management', function () {
    seedRealMenusAndRoles();
    $user = makeMenuTestUser('accountant');

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();
    $titles = visibleMenuTitles($response);

    expect($titles)->toContain('Fee Management');
    expect($titles)->toContain('Finance');
    expect($titles)->toContain('Inventory');
    expect($titles)->toContain('Payroll');
    expect($titles)->toContain('My Profile');

    expect($titles)->not->toContain('Staff Directory');
    expect($titles)->not->toContain('Teaching Assignments');
    expect($titles)->not->toContain('Transport');
    expect($titles)->not->toContain('New Admission');
});

test('a driver sees almost nothing beyond dashboard, transport and their own profile', function () {
    seedRealMenusAndRoles();
    $user = makeMenuTestUser('driver');

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();
    $titles = visibleMenuTitles($response);

    expect($titles)->toContain('Dashboard');
    expect($titles)->toContain('Transport');
    expect($titles)->toContain('My Profile');

    expect($titles)->not->toContain('Fee Management');
    expect($titles)->not->toContain('Finance');
    expect($titles)->not->toContain('Inventory');
    expect($titles)->not->toContain('Staff Directory');
    expect($titles)->not->toContain('Students');
    // Exams/Attendance were out of scope for this pass (see MenuSeeder) and
    // still show for every staff role, driver included - not this fix's
    // regression to guard.
});

test('a maid sees almost nothing beyond dashboard and their own profile', function () {
    seedRealMenusAndRoles();
    $user = makeMenuTestUser('maid');

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();
    $titles = visibleMenuTitles($response);

    expect($titles)->toContain('Dashboard');
    expect($titles)->toContain('My Profile');

    expect($titles)->not->toContain('Fee Management');
    expect($titles)->not->toContain('Finance');
    expect($titles)->not->toContain('Inventory');
    expect($titles)->not->toContain('Transport');
    expect($titles)->not->toContain('Staff Directory');
    expect($titles)->not->toContain('Students');
});

test('a clerk sees students, fee viewing and inventory but not finance or payroll', function () {
    seedRealMenusAndRoles();
    $user = makeMenuTestUser('clerk');

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();
    $titles = visibleMenuTitles($response);

    expect($titles)->toContain('New Admission');
    expect($titles)->toContain('Student List');
    expect($titles)->toContain('Fee Management');
    expect($titles)->toContain('Inventory');
    expect($titles)->toContain('My Profile');

    expect($titles)->not->toContain('Finance');
    expect($titles)->not->toContain('Payroll');
    expect($titles)->not->toContain('Staff Directory');
});

test('a receptionist sees students, fee viewing and transport but not finance or inventory', function () {
    seedRealMenusAndRoles();
    $user = makeMenuTestUser('receptionist');

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();
    $titles = visibleMenuTitles($response);

    expect($titles)->toContain('Student List');
    expect($titles)->toContain('Fee Management');
    expect($titles)->toContain('Transport');
    expect($titles)->toContain('My Profile');

    expect($titles)->not->toContain('New Admission');
    expect($titles)->not->toContain('Inventory');
    expect($titles)->not->toContain('Finance');
    expect($titles)->not->toContain('Staff Directory');
});

test('admin roles keep seeing exactly the same menu as before the staff-visibility fix', function (string $role) {
    seedRealMenusAndRoles();
    $user = makeMenuTestUser($role);

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();
    $titles = visibleMenuTitles($response);

    // Every module group and every staff-only child (New Admission, Staff
    // Directory, Payroll, Fee Management and its children, etc.) is still
    // present - the fix only ever adds staff-role visibility to a row,
    // it never drops an admin role from one.
    foreach ([
        'Dashboard', 'Students', 'New Admission', 'Student List', 'Promotion', 'Admission Enquiries',
        'Exams', 'Attendance', 'Fee Management', 'Inventory', 'Staff', 'Staff Directory',
        'Teaching Assignments', 'Payroll', 'Mark Attendance', 'Transport', 'Finance',
    ] as $expectedTitle) {
        expect($titles)->toContain($expectedTitle);
    }
})->with(['developer', 'owner', 'super_admin', 'campus_admin']);

test('an admin role loses no menu row it could see before the staff-visibility fix', function (string $role) {
    seedRealMenusAndRoles();
    $user = makeMenuTestUser($role);

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();
    $titles = visibleMenuTitles($response);

    // Every row this admin role's own `role` list allows is expected to be
    // visible - the two pre-existing role-scoped rows (the student/guardian
    // "My Portal" group, and the owner/developer-only "Activity Log") are
    // untouched by this fix and correctly excluded from the other admin
    // roles, so the baseline is "what the row's role list allows", not
    // "every row in the table".
    $expectedCount = Menu::active()->get()
        ->filter(fn (Menu $menu) => $menu->role === null || in_array($role, explode(',', $menu->role), true))
        ->count();

    expect(count($titles))->toBe($expectedCount);
})->with(['developer', 'owner', 'super_admin', 'campus_admin']);
