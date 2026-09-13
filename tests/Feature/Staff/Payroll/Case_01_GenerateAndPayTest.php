<?php

use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Staff\StaffLeave;
use App\Models\Staff\StaffLeaveType;
use Database\Seeders\StaffLeaveTypeSeeder;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    (new StaffLeaveTypeSeeder)->run();
    $this->april = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);
});

it('generates a payroll run for every active staff member', function () {
    $one = $this->world->person('Zainab Ali', salary: 50000);
    $two = $this->world->person('Bilal Khan', salary: 40000);

    $response = $this->actingAs($this->world->school->actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ]);

    $response->assertSuccessful();
    $response->assertJsonPath('success', true);

    $run = PayrollRun::firstOrFail();
    expect($run->items()->count())->toBe(2);
    expect((float) $run->total_gross)->toBe(90000.0);
    expect((float) $run->total_net)->toBe(90000.0);
});

it('deducts unpaid leave days from the net pay', function () {
    $staff = $this->world->person('Zainab Ali', salary: 30000);
    $unpaid = StaffLeaveType::where('code', 'UL')->firstOrFail();

    StaffLeave::create([
        'staff_profile_id' => $staff->id,
        'staff_leave_type_id' => $unpaid->id,
        'from_date' => '2026-04-06',
        'to_date' => '2026-04-06',
        'days' => 1,
        'status' => StaffLeave::STATUS_APPROVED,
    ]);

    $this->actingAs($this->world->school->actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    $item = PayrollRunItem::where('staff_profile_id', $staff->id)->firstOrFail();
    expect((float) $item->net_salary)->toBeLessThan(30000.0);
    expect($item->notes)->toContain('unpaid leave');
});

it('regenerating a run for the same month replaces the old items', function () {
    $this->world->person('Zainab Ali', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    $this->world->person('Bilal Khan', salary: 40000);

    $this->actingAs($actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    expect(PayrollRun::count())->toBe(1);
    expect(PayrollRun::first()->items()->count())->toBe(2);
});

it('marks an item paid and closes the run once every item is paid', function () {
    $this->world->person('Zainab Ali', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    $item = PayrollRunItem::firstOrFail();

    $response = $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
    ]);

    $response->assertSuccessful();
    expect($item->fresh()->status)->toBe('paid');
    expect(PayrollRun::firstOrFail()->status)->toBe('paid');
});

it('a person without staff.payroll.run may not generate payroll', function () {
    $this->world->person('Zainab Ali', salary: 50000);
    $outsider = $this->world->person('No Rights', abilities: ['staff.manage']);

    $this->actingAs($outsider->user)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertForbidden();
});
