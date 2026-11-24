<?php

/**
 * Case 14 - browser round 01o: report CSV exports, never-negative ledger,
 * late fee kept out of item lists, receipt details on every route, and the
 * wallet-credit confirmation.
 */

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucherItem;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->items = $this->world->generate(4);
    $this->item = $this->items->first();
});

function payItem(FeeVoucherItem $item, float $received, array $extra = [], ?float $allocated = null): void
{
    test()->post(route('fee.payments.store'), array_merge([
        'student_id' => test()->world->student->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => $received,
        'charges' => [[
            'voucher_id' => $item->fee_voucher_id,
            'fee_voucher_item_id' => $item->id,
            'student_account_charge_id' => $item->student_account_charge_id,
            'amount' => $allocated ?? $received,
        ]],
    ], $extra))->assertRedirect();
}

it('exports the collection report with every row and the method name', function () {
    payItem($this->item, 100);
    payItem($this->item, 50);

    $response = $this->get(route('fee.reports.collection', ['export' => 1]));

    $response->assertOk();
    $lines = array_values(array_filter(explode("\n", $response->streamedContent())));
    expect($lines)->toHaveCount(3)
        ->and($lines[1])->toContain('cash')
        ->and($lines[2])->toContain('cash');
});

it('exports the outstanding and defaulters reports', function () {
    $this->get(route('fee.reports.outstanding', ['export' => 1]))->assertOk();
    $this->get(route('fee.reports.defaulters', ['export' => 1]))->assertOk();
});

it('never lets the ledger running balance go negative, even with a reversed late-fee payment', function () {
    $net = (float) $this->item->net_amount;
    payItem($this->item, $net + 100, ['late_fee' => 100], $net);
    $first = FeePayment::firstOrFail();
    $this->post(route('fee.payments.reverse', $first->id), ['reason' => 'Mistake'])->assertRedirect();
    payItem($this->item->fresh(), 100);

    $rows = $this->get(route('fee.ledger.show', $this->world->student->id))->viewData('page')['props']['rows'];

    expect($rows)->not->toBeEmpty();
    foreach ($rows as $row) {
        expect($row['balance'])->toBeGreaterThanOrEqual(0.0);
    }
});

it('keeps the ledger balance non-negative when a payment is dated before the voucher month', function () {
    payItem($this->item, 100);
    FeePayment::query()->update(['payment_date' => '2026-03-01']);

    $rows = $this->get(route('fee.ledger.show', $this->world->student->id))->viewData('page')['props']['rows'];

    foreach ($rows as $row) {
        expect($row['balance'])->toBeGreaterThanOrEqual(0.0);
    }
});

it('hides late fee lines (any head, any case) from the voucher page', function () {
    $voucher = $this->item->voucher;
    $voucher->items()->create(['description' => 'LATE FEE', 'amount' => 200, 'discount_amount' => 0, 'fine_amount' => 0, 'net_amount' => 200, 'source_type' => 'manual', 'source_module' => 'fee']);

    $items = $this->get(route('fee.vouchers.show', $voucher->id))->viewData('page')['props']['voucher']['items'];

    expect(collect($items)->pluck('description')->map(fn ($d) => strtolower($d))->all())->not->toContain('late fee');
    expect($voucher->items()->get()->filter->isLateFee())->toHaveCount(1);
});

it('passes school, items and remaining balance on the show, receipt and print routes', function () {
    $net = (float) $this->item->net_amount;
    payItem($this->item, $net - 10, ['concession' => 10, 'concession_reason' => 'Sibling']);
    $payment = FeePayment::firstOrFail();

    foreach (['fee.payments.show', 'fee.payments.receipt', 'fee.payments.print-receipt'] as $name) {
        $props = $this->get(route($name, $payment->id))->viewData('page')['props'];

        expect($props)->toHaveKey('schoolName')
            ->and($props['payment'])->toHaveKeys(['items_summary', 'remaining_balance', 'concession_amount', 'concession_reason'])
            ->and($props['payment']['items_summary'])->not->toBeEmpty();
    }
});

it('only keeps extra money as wallet credit after the box is ticked', function () {
    $net = (float) $this->item->net_amount;

    $this->post(route('fee.payments.store'), [
        'student_id' => $this->world->student->id, 'payment_date' => '2026-04-10', 'payment_method' => 'cash',
        'received_amount' => $net + 500,
        'charges' => [['voucher_id' => $this->item->fee_voucher_id, 'fee_voucher_item_id' => $this->item->id, 'student_account_charge_id' => $this->item->student_account_charge_id, 'amount' => $net]],
    ])->assertSessionHasErrors('received_amount');
    expect(FeePayment::count())->toBe(0);

    payItem($this->item, $net + 500, ['confirm_wallet_credit' => true], $net);

    expect((float) FeePayment::firstOrFail()->excess_amount)->toBe(500.0);
});

it('rejects a received amount of zero, a negative amount and one above the cap', function (float $amount) {
    $this->post(route('fee.payments.store'), [
        'student_id' => $this->world->student->id, 'payment_date' => '2026-04-10', 'payment_method' => 'cash',
        'received_amount' => $amount,
        'charges' => [['voucher_id' => $this->item->fee_voucher_id, 'fee_voucher_item_id' => $this->item->id, 'student_account_charge_id' => $this->item->student_account_charge_id, 'amount' => 10]],
    ])->assertSessionHasErrors('received_amount');
})->with([0.0, -5.0, 20000000.0]);
