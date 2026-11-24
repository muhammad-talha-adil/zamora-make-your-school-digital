<?php

/**
 * Transport restructure: one page per sidebar item, old tab URLs redirect, and
 * every page keeps its own permission fence.
 */

use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\TransportWorld;

beforeEach(function () {
    $this->world = TransportWorld::make();
    $this->viewer = $this->world->person('Front Desk', ['transport.view']);
});

it('renders each transport page for a viewer', function (string $routeName, string $component) {
    $this->actingAs($this->viewer)
        ->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'dashboard' => ['transport.index', 'Transport/Index'],
    'vehicles' => ['transport.vehicles.index', 'Transport/Vehicles'],
    'routes' => ['transport.routes.index', 'Transport/Routes'],
    'expenses' => ['transport.expenses.index', 'Transport/Expenses'],
    'reports' => ['transport.reports.index', 'Transport/Reports'],
    'assignments' => ['students.transport.index', 'Transport/StudentAssignments'],
    'dues' => ['students.transport-dues.index', 'Transport/StudentDues'],
]);

it('turns away an account with no transport ability from every page', function (string $routeName) {
    $outsider = $this->world->person('Nobody Important');

    $this->actingAs($outsider)->get(route($routeName))->assertForbidden();
})->with([
    'vehicles' => 'transport.vehicles.index',
    'routes' => 'transport.routes.index',
    'expenses' => 'transport.expenses.index',
    'reports' => 'transport.reports.index',
    'assignments' => 'students.transport.index',
    'dues' => 'students.transport-dues.index',
]);

it('redirects old tab urls to the page that replaced them', function (string $tab, string $routeName) {
    $this->actingAs($this->viewer)
        ->get('/transport?tab='.$tab)
        ->assertRedirect(route($routeName));
})->with([
    'vehicles' => ['vehicles', 'transport.vehicles.index'],
    'routes' => ['routes', 'transport.routes.index'],
    'stops' => ['stops', 'transport.routes.index'],
    'assignments' => ['assignments', 'students.transport.index'],
    'expenses' => ['expenses', 'transport.expenses.index'],
    'dues' => ['dues', 'students.transport-dues.index'],
    'reports' => ['reports', 'transport.reports.index'],
]);

it('keeps the dashboard for an unknown tab', function () {
    $this->actingAs($this->viewer)->get('/transport?tab=nope')->assertOk();
});

it('shows the transport section on the student detail page', function () {
    $manager = $this->world->person('Incharge', ['transport.assignment.manage', 'students.view']);
    $student = $this->world->student($this->world->school->campus);

    $this->actingAs($manager)
        ->get(route('students.show', $student->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('transport')->where('can.manageTransport', true));
});
