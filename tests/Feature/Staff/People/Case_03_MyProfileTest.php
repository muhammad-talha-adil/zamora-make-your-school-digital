<?php

/**
 * Phase 2 — `/staff/me`.
 *
 * Everything a teacher, driver or clerk needs to reach their own record
 * already works through `staff.people.show` — they just have no way to know
 * their own numeric `staffProfile` id. This is the thin resolving route that
 * closes that gap, mirroring the student portal's `ResolvesOwnStudent`.
 */

use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
});

function detachedStaffUser(string $name = 'Detached Account'): User
{
    $user = User::create([
        'name' => $name,
        'username' => strtolower(str_replace(' ', '', $name)).'.'.uniqid(),
        'email' => strtolower(str_replace(' ', '', $name)).'.'.uniqid().'@staff.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $user->givePermissionTo('staff.view.own');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

it('sends a teacher to their own profile', function () {
    $teacher = $this->world->person('A Teacher', ['staff.view.own']);

    $this->actingAs($teacher->user)
        ->get(route('staff.me'))
        ->assertRedirect(route('staff.people.show', $teacher->id));
});

it('never sends one person to a colleague s profile', function () {
    $teacher = $this->world->person('A Teacher', ['staff.view.own']);
    $this->world->person('Another Teacher', ['staff.view.own']);

    $this->actingAs($teacher->user)
        ->get(route('staff.me'))
        ->assertRedirect(route('staff.people.show', $teacher->id));
});

it('turns away an account with no staff ability at all', function () {
    $outsider = $this->world->person('Nobody Important');

    $this->actingAs($outsider->user)->get(route('staff.me'))->assertForbidden();
});

it('gives a clear 403 to a staff.view.own holder with no linked staff record', function () {
    $this->actingAs(detachedStaffUser())->get(route('staff.me'))->assertForbidden();
});

it('lets a developer preview a real staff profile instead of a 403', function () {
    Role::firstOrCreate(['name' => 'developer', 'guard_name' => 'web']);

    $this->world->person('Whoever Is First');

    $developer = detachedStaffUser('Developer Account');
    $developer->assignRole('developer');

    $this->actingAs($developer)
        ->get(route('staff.me'))
        ->assertRedirect(route('staff.people.show', StaffProfile::orderBy('id')->firstOrFail()->id));
});
