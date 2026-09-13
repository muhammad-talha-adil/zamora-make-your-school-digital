<?php

/**
 * Transport module — closed.
 *
 * The six `transport.*` permissions had been seeded since the beginning and
 * not one of them was checked anywhere: all eleven routes sat on `auth`
 * alone, so any signed-in account (a student's own portal login included)
 * could create vehicles, reassign routes, or read every campus's fleet, stops
 * and student assignments. This also covers the two data-integrity checks
 * added alongside it: a stop must belong to the route it is attached to on an
 * assignment, and a route must belong to the same campus as the student being
 * assigned to it.
 */

use App\Models\TransportStudentAssignment;
use App\Models\TransportVehicle;
use Tests\Support\TransportWorld;

beforeEach(function () {
    $this->world = TransportWorld::make();
});

/* ---------------------------------------------------------------- the fence */

it('turns away an account with no transport ability at all', function () {
    $outsider = $this->world->person('Nobody Important');

    $this->actingAs($outsider)->get(route('transport.index'))->assertForbidden();
});

it('lets somebody who may view transport see the index', function () {
    $viewer = $this->world->person('Front Desk', ['transport.view']);

    $this->actingAs($viewer)->get(route('transport.index'))->assertOk();
});

it('does not let a viewer create a vehicle', function () {
    $viewer = $this->world->person('Front Desk', ['transport.view']);

    $this->actingAs($viewer)
        ->postJson(route('transport.vehicles.store'), [
            'vehicle_no' => 'XYZ-999',
            'vehicle_type' => 'van',
            'capacity' => 10,
        ])
        ->assertForbidden();
});

it('lets somebody who may manage vehicles create one', function () {
    $manager = $this->world->person('Fleet Manager', ['transport.vehicle.manage']);

    $this->actingAs($manager)
        ->postJson(route('transport.vehicles.store'), [
            'vehicle_no' => 'XYZ-'.uniqid(),
            'vehicle_type' => 'van',
            'capacity' => 10,
        ])
        ->assertOk();
});

it('does not let a vehicle manager create a route', function () {
    $manager = $this->world->person('Fleet Manager', ['transport.vehicle.manage']);

    $this->actingAs($manager)
        ->postJson(route('transport.routes.store'), [
            'name' => 'North Route',
            'monthly_fee' => 500,
        ])
        ->assertForbidden();
});

it('does not let a route manager generate dues', function () {
    $manager = $this->world->person('Route Manager', ['transport.route.manage']);

    $this->actingAs($manager)
        ->postJson(route('transport.generate-dues'), [
            'month_id' => 1,
            'year' => 2026,
        ])
        ->assertForbidden();
});

/* --------------------------------------------------------------- the campus */

it('does not let a campus admin read another campus s vehicles', function () {
    $other = $this->world->vehicle($this->world->school->otherCampus);
    $admin = $this->world->person('Campus Admin', ['transport.view', 'transport.vehicle.manage']);

    expect(TransportVehicle::visibleTo($admin)->pluck('id'))->not->toContain($other->id);
});

it('lets a campus admin read their own campus s vehicles', function () {
    $mine = $this->world->vehicle($this->world->school->campus);
    $admin = $this->world->person('Campus Admin', ['transport.view', 'transport.vehicle.manage']);

    expect(TransportVehicle::visibleTo($admin)->pluck('id'))->toContain($mine->id);
});

it('keeps a school-wide vehicle visible to every campus', function () {
    $schoolWide = $this->world->vehicle(null);
    $admin = $this->world->person('Campus Admin', ['transport.view', 'transport.vehicle.manage']);

    expect(TransportVehicle::visibleTo($admin)->pluck('id'))->toContain($schoolWide->id);
});

it('does not let a campus admin edit another campus s vehicle', function () {
    $other = $this->world->vehicle($this->world->school->otherCampus);
    $admin = $this->world->person('Campus Admin', ['transport.vehicle.manage']);

    $this->actingAs($admin)
        ->putJson(route('transport.vehicles.update', $other->id), [
            'vehicle_no' => $other->vehicle_no,
            'vehicle_type' => 'van',
            'capacity' => 10,
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('does not let a campus admin read another campus s assignments', function () {
    $student = $this->world->student($this->world->school->otherCampus);
    $route = $this->world->route($this->world->school->otherCampus);
    $other = $this->world->assignment($student, $route, campus: $this->world->school->otherCampus);

    $admin = $this->world->person('Campus Admin', ['transport.view', 'transport.assignment.manage']);

    expect(TransportStudentAssignment::visibleTo($admin)->pluck('id'))->not->toContain($other->id);
});

/* ---------------------------------------------------------- data integrity */

it('refuses a stop that does not belong to the selected route', function () {
    $manager = $this->world->person('Route Manager', ['transport.assignment.manage', 'transport.route.manage']);

    $onRoute = $this->world->stop($this->world->school->campus, 'On Route');
    $notOnRoute = $this->world->stop($this->world->school->campus, 'Elsewhere');
    $route = $this->world->route($this->world->school->campus, [$onRoute]);
    $student = $this->world->student($this->world->school->campus);

    $this->actingAs($manager)
        ->postJson(route('transport.assignments.store'), [
            'student_id' => $student->id,
            'transport_route_id' => $route->id,
            'transport_stop_id' => $notOnRoute->id,
            'monthly_fee' => 500,
            'effective_from' => now()->toDateString(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('transport_stop_id');
});

it('allows a stop that does belong to the selected route', function () {
    $manager = $this->world->person('Route Manager', ['transport.assignment.manage', 'transport.route.manage']);

    $stop = $this->world->stop($this->world->school->campus, 'On Route');
    $route = $this->world->route($this->world->school->campus, [$stop]);
    $student = $this->world->student($this->world->school->campus);

    $this->actingAs($manager)
        ->postJson(route('transport.assignments.store'), [
            'student_id' => $student->id,
            'transport_route_id' => $route->id,
            'transport_stop_id' => $stop->id,
            'monthly_fee' => 500,
            'effective_from' => now()->toDateString(),
        ])
        ->assertOk();
});

it('refuses a route that belongs to a different campus than the student', function () {
    $manager = $this->world->person('Route Manager', ['transport.assignment.manage', 'transport.route.manage']);

    $route = $this->world->route($this->world->school->otherCampus);
    $student = $this->world->student($this->world->school->campus);

    $this->actingAs($manager)
        ->postJson(route('transport.assignments.store'), [
            'student_id' => $student->id,
            'transport_route_id' => $route->id,
            'monthly_fee' => 500,
            'effective_from' => now()->toDateString(),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('transport_route_id');
});

it('allows a school-wide route for any campus s student', function () {
    $manager = $this->world->person('Route Manager', ['transport.assignment.manage', 'transport.route.manage']);

    $route = $this->world->route(null);
    $student = $this->world->student($this->world->school->campus);

    $this->actingAs($manager)
        ->postJson(route('transport.assignments.store'), [
            'student_id' => $student->id,
            'transport_route_id' => $route->id,
            'monthly_fee' => 500,
            'effective_from' => now()->toDateString(),
        ])
        ->assertOk();
});

/* -------------------------------------------------------------- the owner */

it('lets the owner reach every campus', function () {
    $other = $this->world->vehicle($this->world->school->otherCampus);

    expect(TransportVehicle::visibleTo($this->world->school->actor)->pluck('id'))->toContain($other->id);
});
