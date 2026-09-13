<?php

/**
 * `SessionController` manages the academic sessions every other module reads
 * "the current session" from (Exam, Fee, Staff, Student). Before this test it
 * had no authorization at all (any signed-in account could create, edit,
 * delete or (de)activate a session), and `store()`/`update()` could set
 * `is_active` on a session without deactivating the others — leaving two
 * sessions active at once, unlike `activate()` which already enforced
 * exclusivity.
 */

use App\Models\Session;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make()->withFullRoles();
    $this->authorized = $this->world->userWithRole('campus_admin', 'campus.admin.'.uniqid().'@school.test');
    $this->outsider = $this->world->userWithRole('driver', 'driver.'.uniqid().'@school.test');
});

it('turns an unpermissioned account away from every session action', function () {
    $this->actingAs($this->outsider)->get(route('sessions.index'))->assertForbidden();

    $this->actingAs($this->outsider)->post(route('sessions.store'), [
        'name' => '2027-2028',
    ])->assertForbidden();

    $this->actingAs($this->outsider)->patch(route('sessions.update', $this->world->otherSession), [
        'name' => 'Renamed',
    ])->assertForbidden();

    $this->actingAs($this->outsider)->patch(route('sessions.activate', $this->world->otherSession))
        ->assertForbidden();

    $this->actingAs($this->outsider)->delete(route('sessions.destroy', $this->world->otherSession))
        ->assertForbidden();
});

it('lets a campus admin manage sessions', function () {
    $this->actingAs($this->authorized)->getJson(route('sessions.index'))->assertOk();
});

it('does not leave two sessions active when a new one is created active', function () {
    expect($this->world->session->fresh()->is_active)->toBeTrue();

    $this->actingAs($this->authorized)->postJson(route('sessions.store'), [
        'name' => '2027-2028',
        'is_active' => true,
    ])->assertSuccessful();

    expect(Session::where('is_active', true)->count())->toBe(1);
    expect($this->world->session->fresh()->is_active)->toBeFalse();
});

it('does not leave two sessions active when an existing one is edited active', function () {
    expect($this->world->session->fresh()->is_active)->toBeTrue();

    $this->actingAs($this->authorized)->patchJson(route('sessions.update', $this->world->otherSession), [
        'name' => $this->world->otherSession->name,
        'is_active' => true,
    ])->assertSuccessful();

    expect(Session::where('is_active', true)->count())->toBe(1);
    expect($this->world->otherSession->fresh()->is_active)->toBeTrue();
    expect($this->world->session->fresh()->is_active)->toBeFalse();
});

it('refuses to deactivate the only active session', function () {
    Session::where('id', '!=', $this->world->session->id)->delete();

    $this->actingAs($this->authorized)->patchJson(route('sessions.inactivate', $this->world->session))
        ->assertStatus(422);

    expect($this->world->session->fresh()->is_active)->toBeTrue();
});

it('refuses to delete the active session', function () {
    $this->actingAs($this->authorized)->deleteJson(route('sessions.destroy', $this->world->session))
        ->assertStatus(422);

    expect(Session::find($this->world->session->id))->not->toBeNull();
});

it('still allows deleting an inactive session', function () {
    $this->actingAs($this->authorized)->deleteJson(route('sessions.destroy', $this->world->otherSession))
        ->assertSuccessful();

    expect(Session::find($this->world->otherSession->id))->toBeNull();
});
