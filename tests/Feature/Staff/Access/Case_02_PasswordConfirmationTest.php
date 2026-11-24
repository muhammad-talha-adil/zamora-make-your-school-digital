<?php

/**
 * Case 02 — Activating/deactivating a staff member is a plain yes/no on the
 * screen (a confirm dialog); `staff.manage` plus the campus check in the
 * policy is the gate. No password is asked for.
 */

use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->staff = $this->world->person('Jane Teacher');
    $this->actingAs($this->world->school->actor);
});

it('toggles a staff member without asking for a password', function () {
    $this->patchJson(route('staff.members.toggle', $this->staff->id))->assertOk();

    expect($this->staff->fresh()->is_active)->toBeFalse();
});

it('toggles back to active the second time', function () {
    $this->patchJson(route('staff.members.toggle', $this->staff->id));
    $this->patchJson(route('staff.members.toggle', $this->staff->id));

    expect($this->staff->fresh()->is_active)->toBeTrue();
});

it('does not let a viewer without staff.manage toggle', function () {
    $viewer = $this->world->person('Plain Viewer', abilities: ['staff.view']);

    $this->actingAs($viewer->user)->patchJson(route('staff.members.toggle', $this->staff->id))->assertForbidden();
});
