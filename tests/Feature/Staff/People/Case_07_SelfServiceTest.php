<?php

/**
 * Staff self-service dashboard and printable salary slip.
 */

use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->april = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);
});

function slipFor(StaffProfile $staff, Month $month): PayrollRunItem
{
    $run = PayrollRun::create([
        'title' => 'April 2026',
        'campus_id' => $staff->campus_id,
        'payroll_month_id' => $month->id,
        'payroll_year' => 2026,
        'status' => 'processed',
    ]);

    return PayrollRunItem::create([
        'payroll_run_id' => $run->id,
        'staff_profile_id' => $staff->id,
        'gross_salary' => 50000,
        'allowance_amount' => 0,
        'deduction_amount' => 0,
        'net_salary' => 50000,
        'status' => 'pending',
    ]);
}

it('renders the own dashboard for a staff member', function () {
    $teacher = $this->world->person('A Teacher', ['staff.view.own']);
    slipFor($teacher, $this->april);

    $this->actingAs($teacher->user)
        ->get(route('staff.me.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Me/Dashboard')
            ->where('staff.id', $teacher->id)
            ->where('latestPayroll.net_salary', 50000));
});

it('refuses the dashboard without staff.view.own', function () {
    $user = $this->world->person('No Ability')->user;

    $this->actingAs($user)->get(route('staff.me.dashboard'))->assertForbidden();
});

it('lets a staff member open their own salary slip', function () {
    $teacher = $this->world->person('A Teacher', ['staff.view.own']);
    $item = slipFor($teacher, $this->april);

    $this->actingAs($teacher->user)
        ->get(route('staff.payroll.items.slip', $item))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Staff/Payroll/SalarySlip')->where('slip.id', $item->id));
});

it('refuses another staff member s salary slip', function () {
    $teacher = $this->world->person('A Teacher', ['staff.view.own']);
    $other = $this->world->person('Another Teacher', ['staff.view.own']);
    $item = slipFor($other, $this->april);

    $this->actingAs($teacher->user)
        ->get(route('staff.payroll.items.slip', $item))
        ->assertForbidden();
});

it('lets a salary manager open anybody s slip', function () {
    $teacher = $this->world->person('A Teacher');
    $item = slipFor($teacher, $this->april);

    $this->actingAs($this->world->school->actor)
        ->get(route('staff.payroll.items.slip', $item))
        ->assertSuccessful();
});
