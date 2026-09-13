<?php

/**
 * `ReceivePaymentController::store()`'s "student" path used to bypass the Fee
 * module entirely — no `FeePayment`, no journal, and a query against a `code`
 * column `ledger_categories` has never had, so it could not even run. It now
 * records through the same `FeePaymentService` the Fee module's own payment
 * screen uses.
 *
 * `MakePaymentController::store()` used to require a `purchase_id`
 * unconditionally, so a general expense (rent, salary, electricity — the
 * categories the screen itself offers) had nowhere to attach and could never
 * be recorded.
 */

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Ledger\Ledger;
use App\Models\Ledger\LedgerCategory;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->world->generate(4);
    $this->voucher = FeeVoucher::where('student_id', $this->world->student->id)->firstOrFail();
});

it('records a student payment through the real Fee payment flow, not a bare voucher update', function () {
    $response = $this->post(route('finance.receive.store'), [
        'payment_type' => 'student',
        'student_id' => $this->world->student->id,
        'voucher_id' => $this->voucher->id,
        'amount' => (float) $this->voucher->net_amount,
        'payment_method' => 'cash',
        'transaction_date' => '2026-04-10',
    ]);

    $response->assertRedirect();

    // A real FeePayment exists — the old bypass created none at all.
    $payment = FeePayment::where('student_id', $this->world->student->id)->first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->allocated_amount)->toBe((float) $this->voucher->net_amount);

    $this->voucher->refresh();
    expect($this->voucher->status->value)->toBe('paid');

    // No legacy Ledger row for a fee payment — see FF7.
    expect(Ledger::where('reference_type', FeePayment::class)->count())->toBe(0);
});

it('refuses a student payment against a voucher with nothing outstanding', function () {
    $this->voucher->update(['status' => 'paid', 'paid_amount' => $this->voucher->net_amount, 'balance_amount' => 0]);
    $this->voucher->items()->update(['net_amount' => 0]);

    $this->post(route('finance.receive.store'), [
        'payment_type' => 'student',
        'student_id' => $this->world->student->id,
        'voucher_id' => $this->voucher->id,
        'amount' => 100,
        'payment_method' => 'cash',
        'transaction_date' => '2026-04-10',
    ])->assertNotFound();
});

it('records a manual expense with no purchase behind it', function () {
    $category = LedgerCategory::where('type', 'EXPENSE')->firstOrFail();

    $response = $this->post(route('finance.make.store'), [
        'campus_id' => $this->world->school->campus->id,
        'amount' => 5000,
        'payment_method' => 'cash',
        'category_id' => $category->id,
        'transaction_date' => '2026-04-10',
        'description' => 'Electricity bill',
    ]);

    $response->assertRedirect();
    expect(Ledger::where('description', 'Electricity bill')->exists())->toBeTrue();
});
