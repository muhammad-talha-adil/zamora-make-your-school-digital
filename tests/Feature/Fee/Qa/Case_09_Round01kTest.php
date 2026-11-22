<?php

/**
 * Case 09 - round 01k browser QA fixes.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeVoucher;
use App\Services\Fee\FeePaymentService;
use App\Services\Fee\VoucherGenerationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000, 'yearly' => 0, 'once' => 0]);

    FeeHead::firstOrCreate(['code' => 'PREVIOUS_BALANCE'], [
        'name' => 'Previous Balance', 'category' => 'misc', 'is_recurring' => false,
        'default_frequency' => 'once', 'is_optional' => false, 'sort_order' => 60, 'is_active' => true,
    ]);
});

/**
 * @return array<string, mixed>
 */
function payOf(FeeVoucher $voucher, float $amount, array $extra = []): array
{
    $item = $voucher->items()->first();

    return array_merge([
        'student_id' => $voucher->student_id,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'received_amount' => $amount,
        'charges' => [[
            'voucher_id' => $voucher->id,
            'fee_voucher_item_id' => $item->id,
            'student_account_charge_id' => $item->student_account_charge_id,
            'amount' => $amount,
        ]],
    ], $extra);
}

it('generates with previous unpaid balance, totals match items and the old voucher is closed', function () {
    $this->world->generate(4);
    $result = app(VoucherGenerationService::class)->generateMonthlyVouchers(5, 2026, ['include_previous_unpaid' => true]);
    $may = $this->world->voucherFor(5)->fresh();

    expect($result['errors'])->toBe([])
        ->and((float) $may->net_amount)->toBe(10000.0)
        ->and((float) $may->balance_amount)->toBe(10000.0)
        ->and($this->world->voucherFor(4)->fresh()->status)->toBe(VoucherStatus::ADJUSTED);
});

it('leaves no half-made voucher behind when a student fails part way', function () {
    $this->world->generate(4);
    FeeHead::where('code', 'PREVIOUS_BALANCE')->delete();
    FeeHead::saving(function ($head) {
        if ($head->code === 'PREVIOUS_BALANCE') {
            throw new RuntimeException('boom');
        }
    });

    $result = app(VoucherGenerationService::class)->generateMonthlyVouchers(5, 2026, ['include_previous_unpaid' => true]);

    expect($result['generated'])->toBe(0)
        ->and($result['errors'])->toHaveCount(1)
        ->and(FeeVoucher::where('voucher_month_id', $this->world->monthId(5))->count())->toBe(0);
});

it('sets the due date to the 10th of the billing month', function () {
    $this->world->generate(8);

    expect($this->world->voucherFor(8)->due_date->toDateString())->toBe('2026-08-10');
});

it('keeps gross to the items and shows a late fee separately', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    FeeHead::firstOrCreate(['code' => 'LATE_FINE'], [
        'name' => 'Late Fine', 'category' => 'fine', 'is_recurring' => false,
        'default_frequency' => 'once', 'is_optional' => true, 'sort_order' => 40, 'is_active' => true,
    ]);

    app(FeePaymentService::class)->record(payOf($voucher, 5000, ['late_fee' => 200, 'received_amount' => 5200]));
    $voucher->refresh();

    expect((float) $voucher->gross_amount)->toBe(5000.0)
        ->and((float) $voucher->fine_amount)->toBe(200.0)
        ->and((float) $voucher->net_amount)->toBe(5200.0);
});

it('repairs zero totals and wrong due dates, and can run twice', function () {
    $this->world->generate(8);
    $voucher = $this->world->voucherFor(8);
    $voucher->update(['gross_amount' => 0, 'net_amount' => 0, 'balance_amount' => 0, 'due_date' => '2026-12-07']);

    Artisan::call('fee:repair-vouchers');
    $voucher->refresh();

    expect((float) $voucher->net_amount)->toBe(5000.0)
        ->and((float) $voucher->balance_amount)->toBe(5000.0)
        ->and($voucher->due_date->toDateString())->toBe('2026-08-10');

    Artisan::call('fee:repair-vouchers');
    expect(Artisan::output())->toContain('Totals fixed: 0. Due dates fixed: 0.');

    $this->get(route('fee.reports.defaulters'))
        ->assertInertia(fn ($page) => $page->has('defaulters.data', 1));
});

it('refuses a concession above the balance with the amount in the message', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);

    try {
        app(FeePaymentService::class)->record(payOf($voucher, 1000, ['concession' => 9999, 'concession_reason' => 'Test']));
        $this->fail('Expected a validation error.');
    } catch (ValidationException $e) {
        expect($e->errors()['concession'][0])->toContain('Concession cannot be more than the balance (Rs 5,000.00)');
    }
});

it('shows the concession reason on the voucher page and the receipt', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $payment = app(FeePaymentService::class)->record(payOf($voucher, 4500, ['concession' => 500, 'concession_reason' => 'Orphan']));

    $this->get(route('fee.vouchers.show', $voucher->id))
        ->assertInertia(fn ($page) => $page
            ->where('voucher.concessions.0.reason', 'Orphan')
            ->where('voucher.concessions.0.amount', 500));

    $this->get(route('fee.payments.receipt', $payment->id))
        ->assertInertia(fn ($page) => $page->where('payment.concession_reason', 'Orphan'));
});

it('sorts the ledger by billing month and fills the charged amount', function () {
    $this->world->generate(5);
    $this->world->generate(4);

    $this->get(route('fee.ledger.show', $this->world->student->id))
        ->assertInertia(fn ($page) => $page
            ->where('rows.0.debit', 5000)
            ->where('rows.1.debit', 5000)
            ->where('rows.0.description', fn ($d) => str_contains($d, 'April')));
});

it('splits the outstanding report by section', function () {
    $this->world->generate(4);

    $this->get(route('fee.reports.outstanding'))
        ->assertInertia(fn ($page) => $page->where('byClass.0.class.name', fn ($n) => str_contains($n, ' - ')));
});
