<?php

/**
 * Case 10 - round 01l browser QA fixes.
 */

use App\Enums\Fee\FineType;
use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeFineRule;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
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
    $this->fineHead = FeeHead::firstOrCreate(['code' => 'LATE_FINE'], [
        'name' => 'Late Fee', 'category' => 'fine', 'is_recurring' => false,
        'default_frequency' => 'once', 'is_optional' => false, 'sort_order' => 70, 'is_active' => true,
    ]);
});

/**
 * @return array<string, mixed>
 */
function lPayOf(FeeVoucher $voucher, float $amount, array $extra = []): array
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

it('adds no late fee to a voucher carrying arrears, even with a fine rule', function () {
    FeeFineRule::create([
        'name' => 'Rule', 'campus_id' => $this->world->school->campus->id, 'session_id' => $this->world->school->session->id,
        'fine_type' => FineType::FIXED_ONCE, 'fine_value' => 100, 'grace_days' => 0,
        'effective_from' => '2026-01-01', 'is_active' => true,
    ]);
    $this->world->generate(4);
    app(VoucherGenerationService::class)->generateMonthlyVouchers(5, 2026, ['include_previous_unpaid' => true]);
    $may = $this->world->voucherFor(5)->fresh();

    expect((float) $may->fine_amount)->toBe(0.0)
        ->and((float) $may->net_amount)->toBe(10000.0)
        ->and(FeeVoucherItem::where('fee_voucher_id', $may->id)->where('fee_head_id', $this->fineHead->id)->exists())->toBeFalse();
});

it('keeps gross to items only and the late fee separate', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    app(FeePaymentService::class)->record(lPayOf($voucher, 1200, ['late_fee' => 200, 'received_amount' => 1400]));
    $voucher->refresh()->recalculateTotals();

    expect((float) $voucher->fresh()->gross_amount)->toBe(5000.0)
        ->and((float) $voucher->fresh()->fine_amount)->toBe(200.0)
        ->and((float) $voucher->fresh()->net_amount)->toBe(5200.0);
});

it('repairs wrongly auto-added late fees and gross, and is safe to run twice', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $voucher->items()->create([
        'fee_head_id' => $this->fineHead->id, 'description' => 'Late Fee - voucher FV-X (20 days late)',
        'amount' => 100, 'discount_amount' => 0, 'net_amount' => 100, 'source_type' => 'arrears', 'source_module' => 'fee',
    ]);
    $voucher->update(['gross_amount' => 5100, 'net_amount' => 5100, 'balance_amount' => 5100]);

    Artisan::call('fee:repair-vouchers');
    $voucher->refresh();

    expect((float) $voucher->net_amount)->toBe(5000.0)
        ->and((float) $voucher->gross_amount)->toBe(5000.0)
        ->and((float) $voucher->fine_amount)->toBe(0.0);

    Artisan::call('fee:repair-vouchers');
    expect(Artisan::output())->toContain('Auto late fees removed: 0');
});

it('shows a success flash after generating', function () {
    $this->world->generate(4);
    $classId = $this->world->school->class->id;

    $response = $this->post(route('fee.vouchers.generate.post'), [
        'session_id' => $this->world->school->session->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $classId,
        'month_ids' => [$this->world->monthId(4)],
        'year' => 2026,
    ]);

    $response->assertSessionHas('success', fn ($m) => str_contains($m, 'skipped: already made'));
});

it('shows the concession reason on the voucher page', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    app(FeePaymentService::class)->record(lPayOf($voucher, 900, ['concession' => 100, 'concession_reason' => 'Sibling']));

    $this->get(route('fee.vouchers.show', $voucher->id))
        ->assertInertia(fn ($page) => $page->where('voucher.concessions.0.reason', 'Sibling'));
});

it('rejects a typed future payment date with a message on the date field', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);

    $this->post(route('fee.payments.store'), lPayOf($voucher, 100, ['payment_date' => now()->addDays(3)->toDateString()]))
        ->assertSessionHasErrors(['payment_date' => 'Date cannot be in the future.']);
});

it('refuses a family payment above the balance with a clear message', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);

    $this->post(route('fee.payments.store-family'), [
        'student_id' => $voucher->student_id, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        'vouchers' => [['voucher_id' => $voucher->id, 'amount' => 9000]],
    ])->assertSessionHasErrors(['vouchers' => "Amount is more than the balance of {$voucher->voucher_no}."]);
});

it('tells the cashier a carried-forward voucher cannot be paid', function () {
    $this->world->generate(4);
    app(VoucherGenerationService::class)->generateMonthlyVouchers(5, 2026, ['include_previous_unpaid' => true]);
    $april = $this->world->voucherFor(4)->fresh();
    expect($april->status)->toBe(VoucherStatus::ADJUSTED);

    try {
        app(FeePaymentService::class)->record(lPayOf($april, 100, ['concession' => 50, 'concession_reason' => 'x']));
        $this->fail('Expected a validation error.');
    } catch (ValidationException $e) {
        expect($e->errors()['vouchers'][0])->toBe('This voucher was carried forward to a newer voucher; pay that one.');
    }
});

it('rejects a second payment on a voucher that is already settled', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    app(FeePaymentService::class)->record(lPayOf($voucher, 5000));

    try {
        app(FeePaymentService::class)->record(lPayOf($voucher, 5000, ['remarks' => 'second tab']));
        $this->fail('Expected a validation error.');
    } catch (ValidationException $e) {
        expect($e->errors()['vouchers'][0])->toBe('This voucher is already paid.');
    }
});

it('counts days overdue from the due date and shows the class on the defaulters list', function () {
    $this->world->generate(7);
    $voucher = $this->world->voucherFor(7);
    $voucher->update(['due_date' => '2026-07-10']);
    $this->travelTo(now()->setDate(2026, 11, 22));

    $this->get(route('fee.reports.defaulters'))
        ->assertInertia(fn ($page) => $page
            ->where('defaulters.data.0.days_overdue', 135)
            ->has('defaulters.data.0.class_name'));
});
