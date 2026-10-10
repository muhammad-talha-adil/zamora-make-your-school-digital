<?php

/**
 * Phase 5 of the role-architecture redesign: a menu row tagged with a
 * `permission` (auto-derived from its route's `permission:` middleware by
 * `MenuSeeder::tagPermissionsFromRoutes()`) now shows for anyone who holds
 * that ability, whether they got it through their role or a direct grant
 * from the Staff Permissions page — not just via the `role` column.
 */

use App\Models\Menu;
use App\Models\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    (new PermissionsSeeder)->run();
    (new RolesSeeder)->run();
    (new MenuSeeder)->run();
});

it('auto-tags a menu row with the permission its own route requires', function () {
    // /students/enquiries is gated by route middleware (`permission:students.create`),
    // unlike /students itself, which is Gate::authorize()'d in its controller
    // and so carries no `permission:` middleware for the seeder to read.
    $enquiries = Menu::where('url', '/students/enquiries')->first();

    expect($enquiries->permission)->toBe('students.create');
});

it('shows a menu row to a staff member granted its permission directly, outside their role', function () {
    $user = User::factory()->create();
    $user->assignRole('driver');
    expect($user->hasPermission('students.create'))->toBeFalse();

    // Direct grant, the same way the Staff Permissions page writes it.
    $user->givePermissionTo('students.create');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();

    $titles = collect($response->viewData('page')['props']['menus']['main'] ?? [])
        ->flatMap(fn ($item) => array_merge([$item['title']], collect($item['children'] ?? [])->pluck('title')->all()));

    expect($titles)->toContain('Admission Enquiries');
});

it('still hides the row from a staff member who has neither the role nor the permission', function () {
    $user = User::factory()->create();
    $user->assignRole('driver');

    $response = $this->actingAs($user)->get('/settings/profile');
    $response->assertOk();

    $titles = collect($response->viewData('page')['props']['menus']['main'] ?? [])
        ->flatMap(fn ($item) => array_merge([$item['title']], collect($item['children'] ?? [])->pluck('title')->all()));

    expect($titles)->not->toContain('Admission Enquiries');
});
