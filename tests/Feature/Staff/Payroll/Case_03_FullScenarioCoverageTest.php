<?php

/**
 * Thorough scenario coverage for the payroll + advance system, on top of
 * Case_01 (plain generate/pay) and Case_02 (advances & partial payments).
 *
 * Exercises: full single-shot payment, accumulating partial payments capped
 * at net salary, lump and part advances, auto-deduction with and without a
 * monthly plan, an advance settling to zero over several months, an early
 * return stopping further deductions, authorisation on all three advance/pay
 * actions, overpayment rejection, and giving an advance to an inactive staff
 * profile (no such guard exists today, so the real — permissive — behaviour
 * is asserted rather than assumed).
 */

use App\Models\Campus;
use App\Models\CampusType;
use App\Models\Month;
use App\Models\PayrollRunItem;
use App\Models\StaffAdvance;
use App\Models\StaffAdvanceDeduction;
use Database\Seeders\StaffLeaveTypeSeeder;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    (new StaffLeaveTypeSeeder)->run();
    $this->april = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);
    $this->may = Month::firstOrCreate(['month_number' => 5], ['name' => 'May']);
    $this->june = Month::firstOrCreate(['month_number' => 6], ['name' => 'June']);
});

function runPayroll(object $test, object $actor, int $monthId, int $year = 2026): void
{
    $test->actingAs($actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $monthId,
        'payroll_year' => $year,
    ])->assertSuccessful();
}

it('pays a salary in full in one shot', function () {
    $this->world->person('Zainab Ali', salary: 50000);
    $actor = $this->world->school->actor;

    runPayroll($this, $actor, $this->april->id);
    $item = PayrollRunItem::firstOrFail();

    $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
    ])->assertSuccessful();

    $item->refresh();
    expect($item->status)->toBe('paid');
    expect((float) $item->amount_paid)->toBe((float) $item->net_salary);
});

it('caps accumulating partial payments at the net salary', function () {
    $this->world->person('Zainab Ali', salary: 50000);
    $actor = $this->world->school->actor;

    runPayroll($this, $actor, $this->april->id);
    $item = PayrollRunItem::firstOrFail();

    $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
        'amount' => 20000,
    ])->assertSuccessful();
    expect($item->fresh()->status)->toBe('partial');
    expect((float) $item->fresh()->amount_paid)->toBe(20000.0);

    $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
        'amount' => 30000,
    ])->assertSuccessful();
    $item->refresh();
    expect($item->status)->toBe('paid');
    expect((float) $item->amount_paid)->toBe(50000.0);

    // A third attempt that would push amount_paid past net_salary is rejected.
    $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
        'amount' => 1000,
    ])->assertJsonValidationErrors('amount');
    expect((float) $item->fresh()->amount_paid)->toBe(50000.0);
});

it('rejects a single payment that exceeds what is still due', function () {
    $this->world->person('Zainab Ali', salary: 50000);
    $actor = $this->world->school->actor;

    runPayroll($this, $actor, $this->april->id);
    $item = PayrollRunItem::firstOrFail();

    $this->actingAs($actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
        'amount' => 999999,
    ])->assertJsonValidationErrors('amount');

    expect((float) $item->fresh()->amount_paid)->toBe(0.0);
    expect($item->fresh()->status)->toBe('pending');
});

it('gives a full lump-sum advance with the full amount as the remaining balance', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 40000,
        'disbursed_date' => '2026-04-01',
    ])->assertSuccessful();

    $advance = StaffAdvance::firstOrFail();
    expect((float) $advance->balance_remaining)->toBe(40000.0);
    expect($advance->status)->toBe('active');
    expect($advance->monthly_deduction_amount)->toBeNull();
});

it('gives a small partial-month advance', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 5000,
        'disbursed_date' => '2026-04-01',
    ])->assertSuccessful();

    $advance = StaffAdvance::firstOrFail();
    expect((float) $advance->balance_remaining)->toBe(5000.0);
});

it('deducts the whole remaining balance at once when no monthly plan is set', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 10000,
        'disbursed_date' => '2026-04-01',
    ])->assertSuccessful();

    runPayroll($this, $actor, $this->april->id);

    $item = PayrollRunItem::where('staff_profile_id', $staff->id)->firstOrFail();
    expect((float) $item->advance_deduction_amount)->toBe(10000.0);
    expect($item->amountDue())->toBe(40000.0);

    $advance = StaffAdvance::firstOrFail();
    expect((float) $advance->balance_remaining)->toBe(0.0);
    expect($advance->status)->toBe('settled');
});

it('deducts a fixed monthly instalment across several consecutive months until settled', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 5000,
        'disbursed_date' => '2026-04-01',
        'monthly_deduction_amount' => 2000,
    ])->assertSuccessful();

    $advance = StaffAdvance::firstOrFail();

    // Month 1: April — 2000 deducted, 3000 remaining.
    runPayroll($this, $actor, $this->april->id);
    $aprilItem = PayrollRunItem::where('staff_profile_id', $staff->id)->firstOrFail();
    expect((float) $aprilItem->advance_deduction_amount)->toBe(2000.0);
    expect((float) $advance->fresh()->balance_remaining)->toBe(3000.0);
    expect($advance->fresh()->status)->toBe('active');
    expect(StaffAdvanceDeduction::where('staff_advance_id', $advance->id)->count())->toBe(1);

    // Month 2: May — 2000 deducted, 1000 remaining.
    runPayroll($this, $actor, $this->may->id);
    $mayItem = PayrollRunItem::where('staff_profile_id', $staff->id)
        ->whereHas('payrollRun', fn ($q) => $q->where('payroll_month_id', $this->may->id))
        ->firstOrFail();
    expect((float) $mayItem->advance_deduction_amount)->toBe(2000.0);
    expect((float) $advance->fresh()->balance_remaining)->toBe(1000.0);
    expect($advance->fresh()->status)->toBe('active');
    expect(StaffAdvanceDeduction::where('staff_advance_id', $advance->id)->count())->toBe(2);

    // Month 3: June — only the remaining 1000 is deducted (capped), and the
    // advance settles; no further deductions would happen after this.
    runPayroll($this, $actor, $this->june->id);
    $juneItem = PayrollRunItem::where('staff_profile_id', $staff->id)
        ->whereHas('payrollRun', fn ($q) => $q->where('payroll_month_id', $this->june->id))
        ->firstOrFail();
    expect((float) $juneItem->advance_deduction_amount)->toBe(1000.0);
    expect((float) $advance->fresh()->balance_remaining)->toBe(0.0);
    expect($advance->fresh()->status)->toBe('settled');
    expect(StaffAdvanceDeduction::where('staff_advance_id', $advance->id)->count())->toBe(3);
});

it('stops deducting once an advance is returned early mid-way', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 10000,
        'disbursed_date' => '2026-04-01',
        'monthly_deduction_amount' => 2000,
    ])->assertSuccessful();

    $advance = StaffAdvance::firstOrFail();

    // One deduction happens.
    runPayroll($this, $actor, $this->april->id);
    expect((float) $advance->fresh()->balance_remaining)->toBe(8000.0);
    expect(StaffAdvanceDeduction::where('staff_advance_id', $advance->id)->count())->toBe(1);

    // Returned/waived early — no more deductions should follow.
    $this->actingAs($actor)->patchJson(route('staff.advances.return', $advance), [
        'status' => 'returned',
        'password' => 'password',
    ])->assertSuccessful();

    expect($advance->fresh()->status)->toBe('returned');
    expect((float) $advance->fresh()->balance_remaining)->toBe(0.0);

    runPayroll($this, $actor, $this->may->id);
    $mayItem = PayrollRunItem::where('staff_profile_id', $staff->id)
        ->whereHas('payrollRun', fn ($q) => $q->where('payroll_month_id', $this->may->id))
        ->firstOrFail();
    expect((float) $mayItem->advance_deduction_amount)->toBe(0.0);
    expect(StaffAdvanceDeduction::where('staff_advance_id', $advance->id)->count())->toBe(1);
});

it('does not allow a person without staff.payroll.approve to pay a payroll item', function () {
    $staff = $this->world->person('Zainab Ali', salary: 50000);
    $outsider = $this->world->person('No Rights', abilities: ['staff.payroll.run']);
    $actor = $this->world->school->actor;

    runPayroll($this, $actor, $this->april->id);
    $item = PayrollRunItem::where('staff_profile_id', $staff->id)->firstOrFail();

    $this->actingAs($outsider->user)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
    ])->assertForbidden();
});

it('does not allow a person without staff.payroll.approve to give an advance', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $outsider = $this->world->person('No Rights', abilities: ['staff.payroll.run', 'staff.view']);

    $this->actingAs($outsider->user)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 10000,
        'disbursed_date' => '2026-04-01',
    ])->assertForbidden();
});

it('does not allow a person without staff.payroll.approve to return an advance', function () {
    $staff = $this->world->person('Bilal Khan', salary: 50000);
    $outsider = $this->world->person('No Rights', abilities: ['staff.payroll.run', 'staff.view']);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 10000,
        'disbursed_date' => '2026-04-01',
    ])->assertSuccessful();
    $advance = StaffAdvance::firstOrFail();

    $this->actingAs($outsider->user)->patchJson(route('staff.advances.return', $advance), [
        'status' => 'returned',
        'password' => 'password',
    ])->assertForbidden();
});

it('does not allow giving an advance to a staff member on another campus', function () {
    $otherCampusType = CampusType::firstOrCreate(['name' => 'Branch']);
    $otherCampus = Campus::create(['name' => 'Other Campus', 'campus_type_id' => $otherCampusType->id, 'is_active' => true]);
    $staff = $this->world->person('Other Campus Staff', salary: 50000, campus: $otherCampus);

    // Holds the approval ability but not staff.view across campuses — their
    // reach is limited to their own campus by ChecksSchoolReach.
    $limited = $this->world->person('Campus Approver', abilities: ['staff.payroll.approve', 'staff.view']);

    $this->actingAs($limited->user)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 10000,
        'disbursed_date' => '2026-04-01',
    ])->assertForbidden();
});

it('still allows an advance for an inactive staff profile, since no such guard exists today', function () {
    $staff = $this->world->person('Inactive Staffer', salary: 50000);
    $staff->update(['is_active' => false]);
    $actor = $this->world->school->actor;

    // Documents the real, current behaviour: the service has no active-staff
    // guard on advances, so this succeeds rather than being rejected.
    $this->actingAs($actor)->postJson(route('staff.advances.store'), [
        'staff_profile_id' => $staff->id,
        'amount' => 10000,
        'disbursed_date' => '2026-04-01',
    ])->assertSuccessful();

    expect(StaffAdvance::where('staff_profile_id', $staff->id)->exists())->toBeTrue();
});
