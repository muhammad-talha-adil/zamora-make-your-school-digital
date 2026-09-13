<?php

use App\Models\Menu;
use App\Models\Permission;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * `school.menu.manage` is the permission the `menus.*` routes are gated on
 * (see routes/settings.php). Every mutating MenuController action must
 * accept holders of that permission and reject everyone else.
 */
function userWithMenuPermission(bool $withPermission = true): User
{
    Permission::firstOrCreate(['name' => 'school.menu.manage', 'guard_name' => 'web']);

    $user = User::factory()->create();

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

test('a user without school.menu.manage cannot create, update or delete a menu', function () {
    $user = userWithMenuPermission(withPermission: false);
    $menu = Menu::create(['title' => 'Reports', 'type' => 'main', 'path' => '/reports']);

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
