<?php

/**
 * Case 01 — recording a fee payment, and reversing one.
 *
 * `FeePaymentController::store()` validated a `charges` array and then read
 * `$validated['vouchers']` inside the transaction — a key that was never
 * validated and did not exist. Every submission threw an
 * "Undefined array key" error before a payment could be saved; this is the
 * first test this endpoint has ever had.
 */

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeePaymentAllocation;
use App\Models\Fee\FeeVoucherItem;
use App\Models\User;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->items = $this->world->generate(4);
});

function paymentPayload(FeeVoucherItem $item, float $receivedAmount, ?float $allocatedAmount = null): array
{
    return [
        'student_id' => test()->world->student->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => $receivedAmount,
        'charges' => [
            [
                'voucher_id' => $item->fee_voucher_id,
                'fee_voucher_item_id' => $item->id,
                'student_account_charge_id' => $item->student_account_charge_id,
                'amount' => $allocatedAmount ?? $receivedAmount,
            ],
        ],
    ];
}

it('records a payment against a voucher item without crashing', function () {
    $item = $this->items->first();

    $response = $this->post(route('fee.payments.store'), paymentPayload($item, (float) $item->net_amount));

    $response->assertRedirect();
    expect(FeePayment::count())->toBe(1);

    $payment = FeePayment::firstOrFail();
    expect((float) $payment->allocated_amount)->toBe((float) $item->net_amount)
        ->and($payment->status->value)->toBe('posted');
});

it('keeps the excess as a wallet advance when overpaid', function () {
    $item = $this->items->first();
    $overpay = (float) $item->net_amount + 500;

    $this->post(route('fee.payments.store'), paymentPayload($item, $overpay, (float) $item->net_amount))
        ->assertRedirect();

    $payment = FeePayment::firstOrFail();
    expect((float) $payment->excess_amount)->toBe(500.0);
});

it('reverses a posted payment and undoes its allocation', function () {
    $item = $this->items->first();
    $this->post(route('fee.payments.store'), paymentPayload($item, (float) $item->net_amount))->assertRedirect();

    $payment = FeePayment::firstOrFail();

    $this->post(route('fee.payments.reverse', $payment->id), ['reason' => 'Recorded in error'])
        ->assertRedirect();

    $payment->refresh();
    expect($payment->status->value)->toBe('reversed')
        ->and((float) $payment->allocated_amount)->toBe(0.0)
        ->and(FeePaymentAllocation::where('fee_payment_id', $payment->id)->count())->toBe(0);

    expect((float) $item->fresh()->studentAccountCharge?->status)->not->toBe('paid');
});

it('refuses to reverse a payment twice', function () {
    $item = $this->items->first();
    $this->post(route('fee.payments.store'), paymentPayload($item, (float) $item->net_amount))->assertRedirect();
    $payment = FeePayment::firstOrFail();

    $this->post(route('fee.payments.reverse', $payment->id))->assertRedirect();
    $this->post(route('fee.payments.reverse', $payment->id))->assertSessionHasErrors();
});

it('a person without fee.payment.collect may not record a payment', function () {
    $outsider = User::create([
        'name' => 'No Rights',
        'username' => 'no.rights.'.uniqid(),
        'email' => 'no.rights.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $item = $this->items->first();

    $this->actingAs($outsider)
        ->post(route('fee.payments.store'), paymentPayload($item, (float) $item->net_amount))
        ->assertForbidden();
});
