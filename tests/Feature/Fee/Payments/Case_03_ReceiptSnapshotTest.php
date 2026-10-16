<?php

/**
 * Case 03 — the receipt's student identity is frozen at payment time.
 *
 * `FeePaymentController::receipt()` used to read the student's name and
 * registration number live off the relation every time the receipt was
 * viewed — a name correction (or any later edit) silently rewrote a receipt
 * already handed to a parent. Every other receipt-like record in the app
 * (inventory issuance, exam registration, purchases) already freezes this
 * kind of detail at the moment of the transaction; this one now does too.
 */

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucherItem;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->items = $this->world->generate(4);
});

function recordSnapshotPayment(FeeWorld $world, FeeVoucherItem $item): FeePayment
{
    test()->post(route('fee.payments.store'), [
        'student_id' => $world->student->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => (float) $item->net_amount,
        'charges' => [[
            'voucher_id' => $item->fee_voucher_id,
            'fee_voucher_item_id' => $item->id,
            'student_account_charge_id' => $item->student_account_charge_id,
            'amount' => (float) $item->net_amount,
        ]],
    ])->assertRedirect();

    return FeePayment::firstOrFail();
}

it('captures the student name and registration number at the moment of payment', function () {
    $item = $this->items->first();
    $payment = recordSnapshotPayment($this->world, $item);

    expect($payment->student_name_snapshot)->toBe($this->world->student->name)
        ->and($payment->registration_no_snapshot)->toBe($this->world->student->registration_no)
        ->and($payment->class_name_snapshot)->not->toBeNull();
});

it('keeps showing the receipt\'s original name after the student is renamed', function () {
    $item = $this->items->first();
    $payment = recordSnapshotPayment($this->world, $item);

    $originalName = $payment->student_name_snapshot;

    $this->world->student->user->update(['name' => 'A Completely Different Name']);

    $response = $this->get(route('fee.payments.receipt', $payment->id));
    $response->assertOk();

    $shown = $response->viewData('page')['props']['payment']['student']['name'];

    expect($shown)->toBe($originalName)
        ->and($shown)->not->toBe('A Completely Different Name');
});
