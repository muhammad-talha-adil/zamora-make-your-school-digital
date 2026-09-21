<?php

/**
 * Case 03 — a fully paid voucher can be neither paid again nor printed again
 * (issue #119: "1 br jo voucher pay ho gya wo repayment na ho ske na again
 * print ho ske").
 *
 * Repayment was already blocked as a side effect of
 * `FeePaymentService::record()` only accepting vouchers whose status is
 * unpaid/partial/overdue — a fully paid voucher simply isn't in that set, so
 * it is covered here as a guard against regression rather than a new fix.
 * Printing a paid voucher was not blocked at all; `FeeVoucherController::
 * print()` now refuses it, matching the owner's literal wording that a
 * settled voucher shouldn't be printable again (the receipt from
 * `FeePaymentController::receipt()` is the record of what was actually paid).
 */

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Role;
use App\Models\User;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->items = $this->world->generate(6);
});

function payVoucherInFull(): FeeVoucher
{
    $item = test()->items->first();
    $voucher = FeeVoucher::findOrFail($item->fee_voucher_id);

    test()->post(route('fee.payments.store'), [
        'student_id' => test()->world->student->id,
        'payment_date' => '2026-06-10',
        'payment_method' => 'cash',
        'received_amount' => (float) $voucher->net_amount,
        'charges' => $voucher->items->map(fn ($i) => [
            'voucher_id' => $i->fee_voucher_id,
            'fee_voucher_item_id' => $i->id,
            'student_account_charge_id' => $i->student_account_charge_id,
            'amount' => (float) $i->net_amount,
        ])->all(),
    ])->assertRedirect();

    return $voucher->fresh();
}

it('marks the voucher paid once the full amount is settled', function () {
    $voucher = payVoucherInFull();

    expect($voucher->status->value)->toBe('paid');
});

it('refuses a second payment against an already-paid voucher', function () {
    $voucher = payVoucherInFull();
    $item = $voucher->items->first();

    $this->post(route('fee.payments.store'), [
        'student_id' => $this->world->student->id,
        'payment_date' => '2026-06-15',
        'payment_method' => 'cash',
        'received_amount' => 100,
        'charges' => [[
            'voucher_id' => $voucher->id,
            'fee_voucher_item_id' => $item->id,
            'student_account_charge_id' => $item->student_account_charge_id,
            'amount' => 100,
        ]],
    ])->assertSessionHasErrors('vouchers');

    expect(FeePayment::count())->toBe(1);
});

it('refuses ordinary staff to print a paid voucher', function () {
    $voucher = payVoucherInFull();

    $role = Role::firstOrCreate(
        ['name' => 'campus_admin', 'guard_name' => 'web'],
        ['label' => 'Campus Admin', 'scope_level' => Role::SCOPE_CAMPUS, 'is_active' => true]
    );
    $staff = User::create([
        'name' => 'Campus Admin',
        'username' => 'campus-admin-'.uniqid(),
        'email' => 'campus-admin-'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $staff->assignRole($role);
    $staff->givePermissionTo('fee.voucher.print');

    $this->actingAs($staff)
        ->get(route('fee.vouchers.print', $voucher->id))
        ->assertStatus(409);
});

it('still lets developer/owner/super_admin view a paid voucher', function () {
    $voucher = payVoucherInFull();

    $this->get(route('fee.vouchers.print', $voucher->id))->assertOk();
});

it('still allows printing an unpaid voucher', function () {
    $item = $this->items->first();
    $voucher = FeeVoucher::findOrFail($item->fee_voucher_id);

    $this->get(route('fee.vouchers.print', $voucher->id))->assertOk();
});
