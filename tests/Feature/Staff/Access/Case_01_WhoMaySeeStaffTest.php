<?php

/**
 * Phase 2 — who may see a staff record, and who may see a salary.
 *
 * The ten `staff.*` permissions had been seeded since the beginning and **not
 * one of them was used.** All eleven routes sat on `auth` alone, so any
 * signed-in account could read every salary in the school and generate a
 * payroll.
 *
 * Done second and not last, because authorisation added at the end broke 42
 * tests in the exam module — every test before it had been written as the wrong
 * person.
 */

use App\Models\StaffProfile;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
});

/* ---------------------------------------------------------------- the fence */

it('turns away an account with no staff ability at all', function () {
    $outsider = $this->world->person('Nobody Important');

    $this->actingAs($outsider->user)->get(route('staff.index'))->assertForbidden();
});

it('lets somebody who may view staff see the list', function () {
    $clerk = $this->world->person('Office Clerk', ['staff.view']);

    $this->actingAs($clerk->user)->get(route('staff.index'))->assertOk();
});

it('does not let a viewer create a staff member', function () {
    $clerk = $this->world->person('Office Clerk', ['staff.view']);

    $this->actingAs($clerk->user)
        ->postJson(route('staff.members.store'), [
            'name' => 'New Person',
            'employment_type' => 'permanent',
            'basic_salary' => 40000,
            'payment_method' => 'bank',
        ])
        ->assertForbidden();
});

it('does not let a viewer generate a payroll', function () {
    $clerk = $this->world->person('Office Clerk', ['staff.view']);

    $this->actingAs($clerk->user)
        ->postJson(route('staff.payroll.generate'), [
            'payroll_month_id' => 1,
            'payroll_year' => 2026,
        ])
        ->assertForbidden();
});

it('keeps generating a payroll and releasing it as two abilities', function () {
    // The person who works the figures out is not automatically the person who
    // pays them.
    $accountant = $this->world->person('Accounts', ['staff.view', 'staff.payroll.run']);
    $cashier = $this->world->person('Cashier', ['staff.view', 'staff.payroll.approve']);

    // Checked on the gate rather than the route: route model binding runs
    // before the permission middleware, so a payroll item that does not exist
    // is a 404 before it is ever a 403.
    expect($accountant->user->can('runPayroll', StaffProfile::class))->toBeTrue()
        ->and($accountant->user->can('approvePayroll', StaffProfile::class))->toBeFalse()
        ->and($cashier->user->can('runPayroll', StaffProfile::class))->toBeFalse()
        ->and($cashier->user->can('approvePayroll', StaffProfile::class))->toBeTrue();
});

/* --------------------------------------------------------------- the salary */

it('does not show a salary to somebody who may manage staff but not money', function () {
    $teacher = $this->world->person('Well Paid Teacher', salary: 90000);

    // A campus admin may hire and edit, and has no business knowing what
    // anybody earns.
    $admin = $this->world->person('Campus Admin', ['staff.view', 'staff.manage']);

    // The figures do not leave the server at all, rather than being hidden in
    // the browser — checked on both the record and the list, since Phase 7
    // moved the directory off the dashboard onto its own screens.
    $list = $this->actingAs($admin->user)->getJson(route('staff.people.list'));
    $list->assertOk();
    expect($list->getContent())->not->toContain('90000');

    $show = $this->actingAs($admin->user)->get(route('staff.people.show', $teacher->id));
    $show->assertOk();
    expect($show->getContent())->not->toContain('90000');
});

it('shows the salary to somebody who may set salaries', function () {
    $teacher = $this->world->person('Well Paid Teacher', salary: 90000);

    $hr = $this->world->person('HR', ['staff.view', 'staff.salary.manage']);

    expect($this->actingAs($hr->user)->getJson(route('staff.people.list'))->getContent())
        ->toContain('90000');

    expect($this->actingAs($hr->user)->get(route('staff.people.show', $teacher->id))->getContent())
        ->toContain('90000');
});

it('does not let anybody set their own salary', function () {
    $hr = $this->world->person('HR', ['staff.view', 'staff.salary.manage']);

    expect($hr->user->can('manageSalary', $hr))->toBeFalse();
});

/* --------------------------------------------------------------- the campus */

it('does not let a campus admin read another campus s staff', function () {
    $other = $this->world->person('Other Campus Teacher', campus: $this->world->school->otherCampus);
    $admin = $this->world->person('Campus Admin', ['staff.view', 'staff.manage']);

    expect($admin->user->can('view', $other))->toBeFalse()
        ->and(StaffProfile::visibleTo($admin->user)->pluck('id'))->not->toContain($other->id);
});

it('lets a campus admin read their own campus s staff', function () {
    $mine = $this->world->person('My Campus Teacher');
    $admin = $this->world->person('Campus Admin', ['staff.view', 'staff.manage']);

    expect($admin->user->can('view', $mine))->toBeTrue()
        ->and(StaffProfile::visibleTo($admin->user)->pluck('id'))->toContain($mine->id);
});

it('keeps a school-wide post visible to every campus', function () {
    // The principal and the accountant belong to no campus, and hiding them
    // from the campus that has to reach them helps nobody.
    $principal = $this->world->schoolWidePerson('Principal');
    $admin = $this->world->person('Campus Admin', ['staff.view', 'staff.manage']);

    expect($admin->user->can('view', $principal))->toBeTrue()
        ->and(StaffProfile::visibleTo($admin->user)->pluck('id'))->toContain($principal->id);
});

it('does not let a campus admin edit another campus s staff', function () {
    $other = $this->world->person('Other Campus Teacher', campus: $this->world->school->otherCampus);
    $admin = $this->world->person('Campus Admin', ['staff.view', 'staff.manage']);

    $this->actingAs($admin->user)
        ->putJson(route('staff.members.update', $other->id), [
            'name' => 'Renamed',
            'employee_no' => $other->employee_no,
            'employment_type' => 'permanent',
            'basic_salary' => 1,
            'payment_method' => 'bank',
        ])
        ->assertForbidden();
});

/* ------------------------------------------------------------------ oneself */

it('lets somebody read their own record', function () {
    // This is the staff portal.
    $teacher = $this->world->person('A Teacher', ['staff.view.own']);

    expect($teacher->user->can('view', $teacher))->toBeTrue();
});

it('does not let somebody read a colleague s record on that alone', function () {
    $teacher = $this->world->person('A Teacher', ['staff.view.own']);
    $colleague = $this->world->person('Another Teacher');

    expect($teacher->user->can('view', $colleague))->toBeFalse();
});

it('does not let anybody approve their own leave', function () {
    $head = $this->world->person('Head Teacher', ['staff.view', 'staff.manage']);

    expect($head->user->can('decideLeave', $head))->toBeFalse();
});

it('lets the owner reach every campus', function () {
    $other = $this->world->person('Other Campus Teacher', campus: $this->world->school->otherCampus);

    // School-wide roles are not narrowed by the campus on their staff record.
    expect($this->world->school->actor->can('view', $other))->toBeTrue();
});
