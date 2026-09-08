<?php

/**
 * Case 01 — how often each charge appears on a voucher.
 *
 * A fee structure item carries a frequency: monthly, yearly or once. Getting
 * this wrong is expensive in both directions — a yearly charge repeated twelve
 * times bills a parent Rs 144,000 for a Rs 12,000 item, and a monthly charge
 * dropped after the first payment means the school stops collecting tuition.
 */

use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Fee\StudentPaidOneTimeFee;
use App\Models\Month;
use App\Services\Fee\VoucherGenerationService;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->service = app(VoucherGenerationService::class);
});

/** Generates one month's vouchers and returns the student's items. */
function itemsFor(int $month, int $year = 2026)
{
    app(VoucherGenerationService::class)->generateMonthlyVouchers($month, $year);

    $monthId = Month::where('month_number', $month)->value('id');

    $voucher = FeeVoucher::where('voucher_year', $year)
        ->where('voucher_month_id', $monthId)
        ->latest('id')
        ->firstOrFail();

    return FeeVoucherItem::where('fee_voucher_id', $voucher->id)->get();
}

it('bills the monthly charge in the first month', function () {
    $items = itemsFor(4);

    expect($items->pluck('fee_head_id'))->toContain($this->world->school->monthlyHead->id);
});

it('bills the yearly charge only in the first month of the session', function () {
    $april = itemsFor(4);
    $may = itemsFor(5);

    expect($april->pluck('fee_head_id'))->toContain($this->world->school->annualHead->id)
        ->and($may->pluck('fee_head_id'))->not->toContain($this->world->school->annualHead->id);
});

it('bills the one-time charge only once', function () {
    $april = itemsFor(4);
    $may = itemsFor(5);

    $admissionHead = $this->world->admissionHead()->id;

    expect($april->pluck('fee_head_id'))->toContain($admissionHead)
        ->and($may->pluck('fee_head_id'))->not->toContain($admissionHead);
});

it('bills the monthly charge every month of the session', function () {
    $billedIn = [];

    foreach ([4, 5, 6] as $month) {
        if (itemsFor($month)->pluck('fee_head_id')->contains($this->world->school->monthlyHead->id)) {
            $billedIn[] = $month;
        }
    }

    expect($billedIn)->toBe([4, 5, 6]);
});

it('keeps billing tuition after an earlier month has been paid', function () {
    itemsFor(4);

    // Settle April in full, which is what used to poison every later month.
    $april = FeeVoucher::latest('id')->firstOrFail();
    $april->update(['status' => 'paid', 'paid_amount' => $april->net_amount, 'balance_amount' => 0]);

    expect(itemsFor(5)->pluck('fee_head_id'))
        ->toContain($this->world->school->monthlyHead->id);
});

it('charges the yearly amount once across the whole session', function () {
    foreach (range(4, 9) as $month) {
        itemsFor($month);
    }

    $annualTotal = FeeVoucherItem::where('fee_head_id', $this->world->school->annualHead->id)
        ->sum('amount');

    // Not 12,000 x 6 months.
    expect((float) $annualTotal)->toBe(12000.0);
});

it('charges the one-time amount once across the whole session', function () {
    foreach (range(4, 9) as $month) {
        itemsFor($month);
    }

    $onceTotal = FeeVoucherItem::where('fee_head_id', $this->world->admissionHead()->id)
        ->sum('amount');

    expect((float) $onceTotal)->toBe(20000.0);
});

it('records the one-time charge so a readmitted student is not billed again', function () {
    itemsFor(4);

    expect(StudentPaidOneTimeFee::hasPaid(
        $this->world->student->id,
        $this->world->admissionHead()->id,
    ))->toBeTrue();
});

it('marks recurring and one-off charges differently on the voucher', function () {
    $items = itemsFor(4)->keyBy('fee_head_id');

    $monthly = $items[$this->world->school->monthlyHead->id];
    $once = $items[$this->world->admissionHead()->id];

    expect($monthly->description)->toContain('April')
        ->and($once->description)->toContain('One Time');
});
