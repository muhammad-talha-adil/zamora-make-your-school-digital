<?php

use App\Models\Month;
use App\Models\PayrollRunItem;
use App\Models\StaffAdvance;
use Database\Seeders\StaffLeaveTypeSeeder;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    (new StaffLeaveTypeSeeder)->run();
    $this->april = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);
});

it('accumulates partial payments to paid', function () {
    $this->world->person('Zainab Ali', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    $item = PayrollRunItem::firstOrFail();

    $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
        'amount' => 20000,
    ])->assertSuccessful();

    $item->refresh();
    expect($item->status)->toBe('partial');
    expect((float) $item->amount_paid)->toBe(20000.0);

    $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
        'amount' => 30000,
    ])->assertSuccessful();

    expect($item->fresh()->status)->toBe('paid');
});

it('disburses an advance for a staff member', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $response = $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 30000,
        'disbursed_date' => '2026-04-01',
        'monthly_deduction_amount' => 2000,
    ]);

    $response->assertSuccessful();

    $advance = StaffAdvance::firstOrFail();
    expect((float) $advance->balance_remaining)->toBe(30000.0);
    expect($advance->status)->toBe('active');
});

it('auto-deducts the advance instalment from the month payable amount', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 30000,
        'disbursed_date' => '2026-04-01',
        'monthly_deduction_amount' => 2000,
    ])->assertSuccessful();

    $this->actingAs($actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    $item = PayrollRunItem::where('staff_profile_id', $staff->id)->firstOrFail();
    expect((float) $item->advance_deduction_amount)->toBe(2000.0);
    expect($item->amountDue())->toBe(48000.0);

    $advance = StaffAdvance::firstOrFail();
    expect((float) $advance->balance_remaining)->toBe(28000.0);
});

it('stops future deductions once an advance is returned early', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 10000,
        'disbursed_date' => '2026-04-01',
        'monthly_deduction_amount' => 2000,
    ])->assertSuccessful();

    $advance = StaffAdvance::firstOrFail();

    $this->actingAs($actor)->patchJson(route('staff.advances.return', $advance), [
        'status' => 'returned',
        'password' => 'password',
    ])->assertSuccessful();

    expect($advance->fresh()->status)->toBe('returned');
    expect((float) $advance->fresh()->balance_remaining)->toBe(0.0);

    $this->actingAs($actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    $item = PayrollRunItem::where('staff_profile_id', $staff->id)->firstOrFail();
    expect((float) $item->advance_deduction_amount)->toBe(0.0);
});

it('keeps item net, run totals, slip and amount due on one figure when an advance is deducted', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 30000,
        'disbursed_date' => '2026-04-01',
        'monthly_deduction_amount' => 2000,
    ])->assertSuccessful();

    $this->actingAs($actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $this->april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    $item = PayrollRunItem::firstOrFail();
    $run = $item->payrollRun;

    $expectedNet = (float) $item->gross_salary + (float) $item->allowance_amount
        - (float) $item->deduction_amount - (float) $item->advance_deduction_amount;

    expect((float) $item->net_salary)->toBe($expectedNet)
        ->and($item->amountDue())->toBe($expectedNet)
        ->and($item->totalDeductionAmount())->toBe((float) $item->deduction_amount + 2000.0)
        ->and((float) $run->total_net)->toBe($expectedNet)
        ->and((float) $run->total_deductions)->toBe($item->totalDeductionAmount());

    $this->actingAs($actor)->get(route('staff.payroll.items.slip', $item))
        ->assertInertia(fn ($page) => $page
            ->where('slip.net_salary', (int) $expectedNet)
            ->where('slip.amount_due', (int) $expectedNet));

    $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), ['payment_method' => 'bank'])
        ->assertSuccessful();

    expect((float) $item->fresh()->amount_paid)->toBe($expectedNet)
        ->and($item->fresh()->status)->toBe('paid');
});
