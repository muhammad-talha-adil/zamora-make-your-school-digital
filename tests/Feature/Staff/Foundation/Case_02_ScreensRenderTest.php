<?php

/**
 * Phase 7 — the screens Phases 3-6 built the API for, and the dashboard
 * `Staff/Index.vue` used to be.
 *
 * A passing Vite manifest lookup for each of these is as important as the
 * response code: an Inertia render for a component that was never built (or
 * was renamed and left behind) is a 500, not a 404.
 */

use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
});

it('renders the staff dashboard', function () {
    $viewer = $this->world->person('Head Office', ['staff.view', 'staff.payroll.run']);

    $this->actingAs($viewer->user)->get(route('staff.index'))->assertOk();
});

it('renders the staff directory', function () {
    $this->world->person('Somebody', ['staff.view']);
    $viewer = $this->world->person('Head Office', ['staff.view']);

    $this->actingAs($viewer->user)->get(route('staff.people.index'))->assertOk();
});

it('renders a staff profile', function () {
    $staff = $this->world->person('A Teacher');
    $viewer = $this->world->person('Head Office', ['staff.view']);

    $this->actingAs($viewer->user)->get(route('staff.people.show', $staff->id))->assertOk();
});

it('renders the teaching assignment screen', function () {
    $viewer = $this->world->person('Head Office', ['staff.view']);

    $this->actingAs($viewer->user)->get(route('staff.teaching.page'))->assertOk();
});

it('renders the payroll screen', function () {
    $viewer = $this->world->person('Head Office', ['staff.payroll.run']);

    $this->actingAs($viewer->user)->get(route('staff.payroll.page'))->assertOk();
});
