<?php

/**
 * Case 08 - round 01j browser QA fixes.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\StudentEnrollmentRecord;
use App\Services\Fee\FeePaymentService;
use App\Services\Fee\VoucherGenerationService;
use Illuminate\Validation\ValidationException;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000, 'yearly' => 0, 'once' => 0]);
    $this->school = $this->world->school;

    FeeHead::firstOrCreate(['code' => 'PREVIOUS_BALANCE'], [
        'name' => 'Previous Balance', 'category' => 'misc', 'is_recurring' => false,
        'default_frequency' => 'once', 'is_optional' => false, 'sort_order' => 60, 'is_active' => true,
    ]);
});

/**
 * @return array<string, mixed>
 */
function paymentFor(FeeVoucher $voucher, float $amount, array $extra = []): array
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

it('adds the previous balance as an arrears line in the totals', function () {
    $this->world->generate(4);
    $april = $this->world->voucherFor(4);
    $first = (float) $april->net_amount;

    app(VoucherGenerationService::class)->generateMonthlyVouchers(5, 2026, ['include_previous_unpaid' => true]);
    $may = $this->world->voucherFor(5)->fresh();

    expect($first)->toBeGreaterThan(0)
        ->and((float) $may->net_amount)->toBe($may->items()->sum('net_amount') + 0.0)
        ->and((float) $may->net_amount)->toBe($first + 5000.0)
        ->and((float) $may->balance_amount)->toBe($first + 5000.0)
        ->and($may->items()->where('description', 'like', '%Arrears%')->exists())->toBeTrue()
        ->and($april->fresh()->status)->toBe(VoucherStatus::ADJUSTED);
});

it('rejects a payment dated in the future', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);

    $this->post(route('fee.payments.store'), paymentFor($voucher, 1000, ['payment_date' => now()->addDay()->toDateString()]))
        ->assertSessionHasErrors(['payment_date' => 'Date cannot be in the future.']);

    expect(FeePayment::count())->toBe(0);
});

it('refuses an identical second submit and an overpay from a second tab', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $service = app(FeePaymentService::class);

    $service->record(paymentFor($voucher, 2000));

    expect(fn () => $service->record(paymentFor($voucher, 2000)))->toThrow(ValidationException::class);

    expect(fn () => $service->record(paymentFor($voucher, 4000)))->toThrow(ValidationException::class)
        ->and(FeePayment::count())->toBe(1)
        ->and((float) $voucher->fresh()->balance_amount)->toBe(3000.0);
});

it('loads the outstanding report', function () {
    $this->world->generate(4);

    $this->get(route('fee.reports.outstanding'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.voucher_count', 1)
            ->where('topDefaulters.0.student.name', fn ($name) => filled($name)));
});

it('leaves reversed payments out of the collection and method reports', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $first = app(FeePaymentService::class)->record(paymentFor($voucher, 1000));
    $second = app(FeePaymentService::class)->record(paymentFor($voucher, 2000));

    $this->post(route('fee.payments.reverse', $second->id), ['reason' => 'Mistake'])->assertSessionHasNoErrors();

    $this->get(route('fee.reports.collection'))
        ->assertInertia(fn ($page) => $page
            ->where('summary.total_received', fn ($v) => (float) $v === 1000.0)
            ->where('summary.reversed_total', fn ($v) => (float) $v === 2000.0));

    $this->get(route('fee.reports.payment-methods'))
        ->assertInertia(fn ($page) => $page->where('byMethod.0.total', fn ($v) => (float) $v === 1000.0));

    expect($first->id)->not->toBe($second->id);
});

it('shows the student name and registration number, newest first, on the payments list', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $old = app(FeePaymentService::class)->record(paymentFor($voucher, 1000, ['payment_date' => now()->subDays(3)->toDateString()]));
    $new = app(FeePaymentService::class)->record(paymentFor($voucher, 500));

    $this->get(route('fee.payments.index'))
        ->assertInertia(fn ($page) => $page
            ->where('payments.data.0.id', $new->id)
            ->where('payments.data.1.id', $old->id)
            ->where('payments.data.0.student.name', $this->world->student->name)
            ->where('payments.data.0.student.registration_number', $this->world->student->registration_no));

    $this->get(route('fee.payments.index', ['search' => $this->world->student->registration_no]))
        ->assertInertia(fn ($page) => $page->has('payments.data', 2));
});

it('searches vouchers by number, registration number and student name', function (string $field) {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $term = match ($field) {
        'voucher_no' => $voucher->voucher_no,
        'registration' => $this->world->student->registration_no,
        'name' => substr($this->world->student->name, 0, 4),
    };

    $this->get(route('fee.vouchers.index', ['search' => $term]))
        ->assertInertia(fn ($page) => $page->has('vouchers.data', 1));

    $this->get(route('fee.vouchers.index', ['search' => 'zzzzqq']))
        ->assertInertia(fn ($page) => $page->has('vouchers.data', 0));
})->with(['voucher_no', 'registration', 'name']);

it('lists an old month voucher as a defaulter because it is due on the 10th', function () {
    $this->world->generate(4);

    $voucher = $this->world->voucherFor(4);
    expect($voucher->due_date->toDateString())->toBe('2026-04-10');

    $this->get(route('fee.reports.defaulters'))
        ->assertInertia(fn ($page) => $page
            ->has('defaulters.data', 1)
            ->where('defaulters.data.0.student.name', $this->world->student->name));
});

it('carries an overdue voucher balance as arrears without counting it twice', function () {
    $this->world->generate(4);
    $april = $this->world->voucherFor(4);
    $april->update(['status' => VoucherStatus::OVERDUE]);
    $owed = (float) $april->net_amount;

    app(VoucherGenerationService::class)->generateMonthlyVouchers(5, 2026, ['include_previous_unpaid' => true]);
    $may = $this->world->voucherFor(5)->fresh();

    expect((float) $may->gross_amount)->toBe($owed + 5000.0)
        ->and((float) $may->balance_amount)->toBe($owed + 5000.0)
        ->and((float) $april->fresh()->balance_amount)->toBe(0.0)
        ->and((float) FeeVoucher::whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance_amount'))->toBe($owed + 5000.0);
});

it('tells the user when every student was admitted after the billed month', function () {
    StudentEnrollmentRecord::query()->update(['admission_date' => '2026-11-22']);

    $this->post(route('fee.vouchers.generate.post'), [
        'session_id' => $this->school->session->id,
        'campus_id' => $this->school->campus->id,
        'class_id' => $this->school->class->id,
        'year' => 2026,
        'month_ids' => [$this->world->monthId(6)],
    ])->assertSessionHas('error', 'Student was admitted in November 2026. Vouchers before that are not allowed.');
});

it('shows the fee ledger with a running balance, including reversals', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $payment = app(FeePaymentService::class)->record(paymentFor($voucher, 2000));

    $this->get(route('fee.ledger.show', $this->world->student->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rows', 2)
            ->where('rows.1.balance', 3000)
            ->where('closingBalance', 3000));

    $this->post(route('fee.payments.reverse', $payment->id), ['reason' => 'Mistake']);

    $this->get(route('fee.ledger.show', $this->world->student->id))
        ->assertInertia(fn ($page) => $page->has('rows', 3)->where('closingBalance', 5000));
});

it('adds a late fee and a concession and puts them back on reversal', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);

    $payment = app(FeePaymentService::class)->record(paymentFor($voucher, 4000, [
        'received_amount' => 4100,
        'late_fee' => 100,
        'concession' => 1000,
        'concession_reason' => 'Orphan',
    ]));
    $voucher->refresh();

    expect((float) $voucher->fine_amount)->toBe(100.0)
        ->and((float) $voucher->net_amount)->toBe(4100.0)
        ->and((float) $voucher->discount_amount)->toBe(1000.0)
        ->and($voucher->status)->toBe(VoucherStatus::PAID)
        ->and((float) $payment->fresh()->concession_amount)->toBe(1000.0);

    $this->post(route('fee.payments.reverse', $payment->id), ['reason' => 'Mistake'])->assertSessionHasNoErrors();
    $voucher->refresh();

    expect((float) $voucher->fine_amount)->toBe(0.0)
        ->and((float) $voucher->net_amount)->toBe(5000.0)
        ->and((float) $voucher->discount_amount)->toBe(0.0)
        ->and((float) $voucher->balance_amount)->toBe(5000.0)
        ->and($voucher->status)->toBe(VoucherStatus::UNPAID);
});

it('validates late fee and concession', function (array $extra, string $field) {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);

    $this->post(route('fee.payments.store'), paymentFor($voucher, 1000, $extra))->assertSessionHasErrors($field);
    expect(FeePayment::count())->toBe(0);
})->with([
    'negative late fee' => [['late_fee' => -5], 'late_fee'],
    'three decimals' => [['late_fee' => 1.234], 'late_fee'],
    'no reason' => [['concession' => 100], 'concession_reason'],
    'more than balance' => [['concession' => 9000, 'concession_reason' => 'x'], 'concession'],
]);

it('pays a family together, oldest first, and rejects an overpay', function () {
    $this->world->generate(4);
    $this->world->generate(5);
    $april = $this->world->voucherFor(4);
    $may = $this->world->voucherFor(5);

    $this->post(route('fee.payments.store-family'), [
        'student_id' => $this->world->student->id,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'vouchers' => [['voucher_id' => $may->id, 'amount' => 6000], ['voucher_id' => $april->id, 'amount' => 5000]],
    ])->assertSessionHasErrors('vouchers');
    expect(FeePayment::count())->toBe(0);

    $this->post(route('fee.payments.store-family'), [
        'student_id' => $this->world->student->id,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'vouchers' => [['voucher_id' => $may->id, 'amount' => 2000], ['voucher_id' => $april->id, 'amount' => 5000]],
    ])->assertSessionHasNoErrors();

    expect(FeePayment::count())->toBe(1)
        ->and((float) FeePayment::first()->received_amount)->toBe(7000.0)
        ->and($april->fresh()->status)->toBe(VoucherStatus::PAID)
        ->and((float) $may->fresh()->balance_amount)->toBe(3000.0);

    $this->get(route('fee.payments.family', $this->world->student->id))->assertOk();
});
