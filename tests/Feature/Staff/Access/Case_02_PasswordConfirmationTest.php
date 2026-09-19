<?php

/**
 * Case 02 — Activating/deactivating a staff member is a sensitive,
 * status-changing action. `RequiresPasswordConfirmation` re-verifies the
 * acting user's own password server-side before `StaffController::toggleStaff`
 * runs — this cannot be bypassed by calling the endpoint directly without a
 * password, regardless of what the frontend confirm modal sends.
 */

use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->staff = $this->world->person('Jane Teacher');
    $this->actingAs($this->world->school->actor);
});

it('refuses the toggle when no password is given', function () {
    $response = $this->patch(route('staff.members.toggle', $this->staff->id));

    $response->assertSessionHasErrors('password');
    expect($this->staff->fresh()->is_active)->toBeTrue();
});

it('refuses the toggle when the password is wrong', function () {
    $response = $this->patch(route('staff.members.toggle', $this->staff->id), [
        'password' => 'not-the-right-password',
    ]);

    $response->assertSessionHasErrors('password');
    expect($this->staff->fresh()->is_active)->toBeTrue();
});

it('applies the toggle once the correct password is given', function () {
    $response = $this->patch(route('staff.members.toggle', $this->staff->id), [
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    expect($this->staff->fresh()->is_active)->toBeFalse();
});

it('returns a 422 with a clear message for an axios/JSON caller with the wrong password', function () {
    $response = $this->patchJson(route('staff.members.toggle', $this->staff->id), [
        'password' => 'nope',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('password');
});
