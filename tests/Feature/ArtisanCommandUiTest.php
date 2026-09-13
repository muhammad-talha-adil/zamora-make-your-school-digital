<?php

/**
 * The `/artisan` dashboard can run destructive Artisan commands
 * (`migrate:fresh`, arbitrary `Artisan::call()`, ...). It must be reachable
 * only by the `developer` role, `migrate:fresh` must be blocked outright
 * regardless of role, and the free-text `run/{command}` endpoint must only
 * accept an explicit allowlist of safe commands.
 */

use App\Models\Role;
use App\Models\User;

function makeUserWithRole(string $role): User
{
    Role::firstOrCreate(
        ['name' => $role, 'guard_name' => 'web'],
        ['label' => ucfirst($role), 'scope_level' => Role::SCOPE_SYSTEM, 'is_active' => true]
    );

    $user = User::create([
        'name' => ucfirst($role),
        'username' => $role.'_'.uniqid(),
        'email' => $role.'.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

it('turns away a guest', function () {
    $this->get(route('artisan.ui'))->assertRedirect(route('login'));
});

it('turns away a non-developer, including owner and super_admin', function (string $role) {
    $user = makeUserWithRole($role);

    $this->actingAs($user)->get(route('artisan.ui'))->assertForbidden();
    $this->actingAs($user)->post(route('artisan.cache.clear'))->assertForbidden();
})->with(['owner', 'super_admin', 'campus_admin']);

it('lets a developer reach the dashboard', function () {
    $developer = makeUserWithRole('developer');

    $this->actingAs($developer)->get(route('artisan.ui'))->assertOk();
});

it('blocks migrate:fresh outright even for a developer', function () {
    $developer = makeUserWithRole('developer');

    $this->actingAs($developer)->post(route('artisan.migrate.fresh'))->assertForbidden();
    $this->actingAs($developer)->post(route('artisan.migrate.fresh.seed'))->assertForbidden();
});

it('rejects a disallowed free-text command even for a developer', function () {
    $developer = makeUserWithRole('developer');

    $this->actingAs($developer)
        ->post(route('artisan.run.command', ['command' => 'migrate:fresh']))
        ->assertForbidden();

    $this->actingAs($developer)
        ->post(route('artisan.run.command', ['command' => 'db:wipe']))
        ->assertForbidden();
});

it('allows a safe allowlisted free-text command for a developer', function () {
    $developer = makeUserWithRole('developer');

    $this->actingAs($developer)
        ->post(route('artisan.run.command', ['command' => 'cache:clear']))
        ->assertRedirect(route('artisan.ui'));
});
