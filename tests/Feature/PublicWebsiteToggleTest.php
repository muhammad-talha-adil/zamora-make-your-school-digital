<?php

use App\Models\Permission;
use App\Models\School;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * The School Profile settings page can switch the public marketing site off,
 * in which case anonymous visitors to the home/about/contact routes should
 * land on the login page instead — see EnsurePublicWebsiteEnabled.
 */
test('the home page is shown when the public website is active', function () {
    School::create(['name' => 'Zamora School', 'website_enabled' => true]);

    $this->get(route('home'))->assertOk();
});

test('the public marketing routes redirect to login when the public website is inactive', function () {
    School::create(['name' => 'Zamora School', 'website_enabled' => false]);

    $this->get(route('home'))->assertRedirect(route('login'));
    $this->get(route('about'))->assertRedirect(route('login'));
    $this->get(route('contact'))->assertRedirect(route('login'));
});

test('an authenticated user is unaffected by the public website toggle', function () {
    School::create(['name' => 'Zamora School', 'website_enabled' => false]);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertOk();
    $this->actingAs($user)->get(route('about'))->assertOk();
    $this->actingAs($user)->get(route('contact'))->assertOk();
});

test('the public website defaults to active when no school row exists', function () {
    $this->get(route('home'))->assertOk();
});

test('a user with school.profile.manage can toggle the public website off through the school profile form', function () {
    Permission::firstOrCreate(['name' => 'school.profile.manage', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('school.profile.manage');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $school = School::create(['name' => 'Zamora School', 'website_enabled' => true]);

    $this->actingAs($user)
        ->post(route('school-profile.update'), [
            'name' => 'Zamora School',
            'website_enabled' => false,
        ])
        ->assertSessionHasNoErrors();

    expect($school->fresh()->website_enabled)->toBeFalse();
});
