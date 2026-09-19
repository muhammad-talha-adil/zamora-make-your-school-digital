<?php

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * `school.menu.manage` is the permission the `menus.*` write actions are
 * gated on, and the whole `menus.*` route group is additionally
 * developer-only (see routes/settings.php) — menu/sidebar structure is
 * system-level configuration, kept out of even the owner's reach.
 */
function userWithMenuPermission(bool $withPermission = true): User
{
    Permission::firstOrCreate(['name' => 'school.menu.manage', 'guard_name' => 'web']);
    Role::firstOrCreate(
        ['name' => 'developer', 'guard_name' => 'web'],
        ['label' => 'Developer', 'scope_level' => Role::SCOPE_SYSTEM, 'is_active' => true]
    );

    $user = User::factory()->create();
    $user->assignRole('developer');

    if ($withPermission) {
        $user->givePermissionTo('school.menu.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    return $user;
}

test('a user with school.menu.manage can create a menu', function () {
    $user = userWithMenuPermission();

    $response = $this->actingAs($user)->post(route('menus.store'), [
        'title' => 'Reports',
        'type' => 'main',
    ]);

    $response->assertRedirect(route('menus.index'));
    $response->assertSessionHasNoErrors();

    expect(Menu::where('title', 'Reports')->exists())->toBeTrue();
});

test('a non-developer cannot reach menu-settings at all, even holding school.menu.manage', function () {
    Permission::firstOrCreate(['name' => 'school.menu.manage', 'guard_name' => 'web']);
    Role::firstOrCreate(
        ['name' => 'owner', 'guard_name' => 'web'],
        ['label' => 'School Owner', 'scope_level' => Role::SCOPE_SCHOOL, 'is_active' => true]
    );

    $user = User::factory()->create();
    $user->assignRole('owner');
    $user->givePermissionTo('school.menu.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $menu = Menu::create(['title' => 'Reports', 'type' => 'main', 'path' => '/reports']);

    $this->actingAs($user)->get(route('menus.index'))->assertForbidden();

    $this->actingAs($user)
        ->post(route('menus.store'), ['title' => 'New', 'type' => 'main'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('menus.update', $menu->id), ['title' => 'Renamed', 'type' => 'main'])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('menus.destroy', $menu->id))
        ->assertForbidden();
});

test('a menu cannot be reparented onto one of its own descendants', function () {
    $user = userWithMenuPermission();

    $parent = Menu::create(['title' => 'Parent', 'type' => 'main', 'path' => '/parent']);
    $child = Menu::create(['title' => 'Child', 'type' => 'main', 'path' => '/parent/child', 'parent_id' => $parent->id]);

    $response = $this->actingAs($user)->patch(route('menus.update', $parent->id), [
        'title' => 'Parent',
        'type' => 'main',
        'parent_id' => $child->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
    expect($parent->fresh()->parent_id)->toBeNull();
});

test('a menu with children cannot be deleted', function () {
    $user = userWithMenuPermission();

    $parent = Menu::create(['title' => 'Parent', 'type' => 'main', 'path' => '/parent']);
    Menu::create(['title' => 'Child', 'type' => 'main', 'path' => '/parent/child', 'parent_id' => $parent->id]);

    $response = $this->actingAs($user)->delete(route('menus.destroy', $parent->id));

    $response->assertRedirect();
    $response->assertSessionHas('warning');
    expect($parent->fresh())->not->toBeNull();
});
