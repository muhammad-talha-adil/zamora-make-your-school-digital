<?php

/**
 * Case 02 — the Fee Payment show page (issues #117, #118).
 *
 * `FeePaymentController::show()`/`receipt()` eager-loaded `student`,
 * `allocations.voucher.voucherMonth` and `receivedBy`, but never `campus` —
 * even though the model has the relation and `Show.vue`/`Receipt.vue` render
 * `payment.campus.name`. Without eager loading, an unaccessed Eloquent
 * relation is simply absent from the Inertia JSON payload, so the campus
 * (and anything else that depended on it downstream) rendered as "N/A".
 */

use App\Models\Fee\FeePayment;
use Inertia\Testing\AssertableInertia;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->items = $this->world->generate(1);
});

function recordAPayment(): FeePayment
{
    $item = test()->items->first();

    test()->post(route('fee.payments.store'), [
        'student_id' => test()->world->student->id,
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

it('sends the student name and campus on the payment show page', function () {
    $payment = recordAPayment();

    $this->get(route('fee.payments.show', $payment->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Fee/Payments/Show')
            ->where('payment.student.name', $this->world->student->name)
            ->where('payment.campus.name', $this->world->school->campus->name)
            ->has('payment.allocations.0.voucher.voucher_no')
        );
});

it('sends the student name and campus on the printable receipt', function () {
    $payment = recordAPayment();

    $this->get(route('fee.payments.receipt', $payment->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('payment.student.name', $this->world->student->name)
            ->where('payment.campus.name', $this->world->school->campus->name)
        );
});
