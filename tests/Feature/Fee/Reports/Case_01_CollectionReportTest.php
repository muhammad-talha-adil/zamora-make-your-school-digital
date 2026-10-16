<?php

/**
 * Case 01 — the collection report, which has never loaded successfully.
 *
 * `new_fee_payments.wallet_amount` was renamed to `excess_amount` by the
 * migration that built the table; `FeeReportController::collection()` still
 * summed the old name, so this screen threw a SQL error on every visit,
 * filtered or not.
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

function recordFeePayment(FeeWorld $world, FeeVoucherItem $item, float $receivedAmount): void
{
    test()->post(route('fee.payments.store'), [
        'student_id' => $world->student->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => $receivedAmount,
        'charges' => [[
            'voucher_id' => $item->fee_voucher_id,
            'fee_voucher_item_id' => $item->id,
            'student_account_charge_id' => $item->student_account_charge_id,
            'amount' => min($receivedAmount, (float) $item->net_amount),
        ]],
    ])->assertRedirect();
}

it('loads without a SQL error', function () {
    $this->get(route('fee.reports.collection'))->assertOk();
});

it('reports the excess amount under total_wallet, not the old wallet_amount column', function () {
    // Overpayment through the actual store() endpoint is disallowed outright
    // (Issue #121 — see FeePaymentService::recordPayment()), so a payment
    // carrying an excess_amount can no longer be produced through the HTTP
    // flow. This test's job is narrower than that: confirm the report sums
    // the renamed `excess_amount` column, not the old `wallet_amount` one —
    // so the row is created directly, as a stand-in for the legacy/reversed
    // payments the column still exists for.
    FeePayment::create([
        'receipt_no' => 'TEST-WALLET-1',
        'student_id' => $this->world->student->id,
        'student_enrollment_record_id' => $this->world->enrollment->id,
        'campus_id' => $this->world->school->campus->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => 300,
        'allocated_amount' => 0,
        'excess_amount' => 300,
        'remaining_unallocated_amount' => 300,
        'status' => 'posted',
        'received_by' => $this->world->school->actor->id,
    ]);

    $response = $this->get(route('fee.reports.collection'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('summary.total_wallet'));
    expect((float) $response->viewData('page')['props']['summary']['total_wallet'])->toBe(300.0);
});

it('sums received amounts into the summary', function () {
    $item = $this->items->first();
    recordFeePayment($this->world, $item, (float) $item->net_amount);

    $response = $this->get(route('fee.reports.collection'));

    $props = $response->viewData('page')['props'];
    expect((float) $props['summary']['total_received'])->toBe((float) $item->net_amount)
        ->and((int) $props['summary']['payment_count'])->toBe(1);
});
