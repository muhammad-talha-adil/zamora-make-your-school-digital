<?php

use App\Models\Role;
use App\Models\School;
use App\Models\User;

/**
 * The "School is Active" toggle on `/settings/school-profile`: once off,
 * nobody but `developer`/`owner` may sign in, and anybody already signed in
 * with another role is turned away on their very next request — see
 * EnsureSchoolActive (middleware) and the Login event listener in
 * AppServiceProvider (login-attempt guard).
 */
function makeSchoolLockoutUser(string $role, string $password = 'password'): User
{
    Role::firstOrCreate(
        ['name' => $role, 'guard_name' => 'web'],
        ['label' => ucfirst($role), 'scope_level' => Role::SCOPE_SYSTEM, 'is_active' => true]
    );

    $user = User::factory()->create(['password' => bcrypt($password)]);
    $user->assignRole($role);

    return $user;
}

test('a non-owner/developer cannot log in while the school is inactive', function () {
    School::create(['name' => 'Zamora School', 'is_active' => false]);
    $user = makeSchoolLockoutUser('teacher');

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('owner and developer can still log in while the school is inactive', function (string $role) {
    School::create(['name' => 'Zamora School', 'is_active' => false]);
    $user = makeSchoolLockoutUser($role);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
})->with(['owner', 'developer']);

test('an already logged-in non-owner/developer is locked out on their next request once the school goes inactive', function () {
    $school = School::create(['name' => 'Zamora School', 'is_active' => true]);
    $user = makeSchoolLockoutUser('teacher');

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $school->update(['is_active' => false]);

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
});

test('owner and developer are unaffected on their next request once the school goes inactive', function (string $role) {
    $school = School::create(['name' => 'Zamora School', 'is_active' => true]);
    $user = makeSchoolLockoutUser($role);

    $school->update(['is_active' => false]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
})->with(['owner', 'developer']);

test('logout stays reachable for a locked-out user', function () {
    School::create(['name' => 'Zamora School', 'is_active' => false]);
    $user = makeSchoolLockoutUser('teacher');

    $this->actingAs($user)->post(route('logout'))->assertRedirect();
    $this->assertGuest();
});

test('the school defaults to active when no school row exists', function () {
    $user = makeSchoolLockoutUser('teacher');

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
