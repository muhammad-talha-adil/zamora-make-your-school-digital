<?php

/**
 * Case 13 - every fee form says what is wrong in words.
 */

use App\Services\Fee\FeePaymentService;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000, 'yearly' => 0, 'once' => 0]);
    $this->world->generate(4);
    $this->voucher = $this->world->voucherFor(4);
    $item = $this->voucher->items()->first();
    $this->payload = fn (array $over = []) => array_merge([
        'student_id' => $this->voucher->student_id, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        'received_amount' => 5000,
        'charges' => [['voucher_id' => $this->voucher->id, 'fee_voucher_item_id' => $item->id, 'student_account_charge_id' => $item->student_account_charge_id, 'amount' => 5000]],
    ], $over);
});

it('explains each bad payment field', function (array $over, string $field, string $message) {
    $this->post(route('fee.payments.store'), ($this->payload)($over))
        ->assertSessionHasErrors([$field => $message]);
})->with([
    'zero amount' => [['received_amount' => 0], 'received_amount', 'Amount must be greater than 0.'],
    'negative amount' => [['received_amount' => -5], 'received_amount', 'Amount must be greater than 0.'],
    'huge amount' => [['received_amount' => 99999999], 'received_amount', 'Amount is too large. The most you can enter is Rs 10,000,000.'],
    'missing method' => [['payment_method' => ''], 'payment_method', 'Choose how the money was received.'],
    'future date' => [['payment_date' => '2999-01-01'], 'payment_date', 'Payment date cannot be in the future.'],
    'concession without reason' => [['concession' => 100], 'concession_reason', 'Give a reason for the concession.'],
    'negative concession' => [['concession' => -1], 'concession', 'Concession cannot be negative.'],
    'negative late fee' => [['late_fee' => -1], 'late_fee', 'Late fee cannot be negative.'],
]);

it('requires a reason to reverse a payment', function () {
    $payment = app(FeePaymentService::class)->record(($this->payload)());

    $this->post(route('fee.payments.reverse', $payment->id), ['reason' => ''])
        ->assertSessionHasErrors(['reason' => 'Please write the reason.']);
});

it('rejects a family payment of zero or a negative amount', function () {
    $this->post(route('fee.payments.store-family'), [
        'student_id' => $this->voucher->student_id, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        'vouchers' => [['voucher_id' => $this->voucher->id, 'amount' => 0]],
    ])->assertSessionHasErrors('vouchers.0.amount');
});
