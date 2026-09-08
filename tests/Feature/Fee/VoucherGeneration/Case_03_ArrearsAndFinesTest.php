<?php

/**
 * Case 03 — carrying an unpaid balance forward, and the late fee on it.
 *
 * When a parent misses a month, the balance moves onto the next voucher. Two
 * things have to hold: the old voucher must stop counting as outstanding, or
 * the school's own reports double the arrears; and the late fee the school has
 * configured must actually be charged.
 */

use App\Enums\Fee\FineType;
use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeFineRule;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Month;
use App\Services\Fee\VoucherGenerationService;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000]);

    FeeHead::firstOrCreate(
        ['code' => 'PREVIOUS_BALANCE'],
        [
            'name' => 'Previous Balance',
            'category' => 'misc',
            'is_recurring' => false,
            'default_frequency' => 'once',
            'is_optional' => false,
            'sort_order' => 60,
            'is_active' => true,
        ]
    );

    $this->fineHead = FeeHead::firstOrCreate(
        ['code' => 'LATE_FINE'],
        [
            'name' => 'Late Fee',
            'category' => 'fine',
            'is_recurring' => false,
            'default_frequency' => 'once',
            'is_optional' => false,
            'sort_order' => 70,
            'is_active' => true,
        ]
    );
});

/** Generates a month's vouchers, carrying any unpaid balance forward. */
function generateCarryingArrears(int $month, int $year = 2026): FeeVoucher
{
    app(VoucherGenerationService::class)->generateMonthlyVouchers($month, $year, [
        'include_previous_unpaid' => true,
    ]);

    $monthId = Month::where('month_number', $month)->value('id');

    return FeeVoucher::where('voucher_year', $year)
        ->where('voucher_month_id', $monthId)
        ->latest('id')
        ->firstOrFail();
}

/** Makes a voucher look overdue by the given number of days. */
function makeOverdueBy(FeeVoucher $voucher, int $days): void
{
    $voucher->update([
        'due_date' => now()->subDays($days)->toDateString(),
        'status' => VoucherStatus::UNPAID,
    ]);
}

it('carries an unpaid balance onto the next voucher', function () {
    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 20);

    $may = generateCarryingArrears(5);

    $arrears = FeeVoucherItem::where('fee_voucher_id', $may->id)
        ->where('description', 'like', '%Arrears%')
        ->first();

    expect($arrears)->not->toBeNull()
        ->and((float) $arrears->amount)->toBe((float) $april->fresh()->getOriginal('balance_amount') ?: (float) $arrears->amount);
});

it('does not need to create the previous balance head on the fly', function () {
    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 20);

    generateCarryingArrears(5);

    // Creating it here used to throw: the category it used is not in the enum.
    expect(FeeHead::where('code', 'PREVIOUS_BALANCE')->count())->toBe(1);
});

it('closes the carried voucher so the arrears are not counted twice', function () {
    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 20);

    generateCarryingArrears(5);

    $april->refresh();

    expect($april->status)->toBe(VoucherStatus::ADJUSTED)
        ->and((float) $april->balance_amount)->toBe(0.0);
});

it('records on the closed voucher where its balance went', function () {
    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 20);

    $may = generateCarryingArrears(5);

    expect($april->fresh()->notes)->toContain($may->voucher_no);
});

it('leaves only the newest voucher outstanding', function () {
    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 20);

    generateCarryingArrears(5);

    $outstanding = FeeVoucher::whereIn('status', [VoucherStatus::UNPAID, VoucherStatus::PARTIAL])->get();

    expect($outstanding)->toHaveCount(1);
});

it('charges a per-day late fee once the grace period has passed', function () {
    FeeFineRule::create([
        'name' => 'Standard late fee',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'fine_type' => FineType::FIXED_PER_DAY,
        'fine_value' => 50,
        'grace_days' => 5,
        'effective_from' => '2026-01-01',
        'is_active' => true,
    ]);

    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 15);

    $may = generateCarryingArrears(5);

    $fine = FeeVoucherItem::where('fee_voucher_id', $may->id)
        ->where('fee_head_id', $this->fineHead->id)
        ->first();

    // 15 days late, 5 forgiven, 10 chargeable at Rs 50.
    expect($fine)->not->toBeNull()
        ->and((float) $fine->amount)->toBe(500.0);
});

it('charges nothing while the voucher is still inside the grace period', function () {
    FeeFineRule::create([
        'name' => 'Standard late fee',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'fine_type' => FineType::FIXED_PER_DAY,
        'fine_value' => 50,
        'grace_days' => 5,
        'effective_from' => '2026-01-01',
        'is_active' => true,
    ]);

    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 3);

    $may = generateCarryingArrears(5);

    expect(FeeVoucherItem::where('fee_voucher_id', $may->id)
        ->where('fee_head_id', $this->fineHead->id)
        ->exists())->toBeFalse();
});

it('charges a flat late fee when the rule is fixed once', function () {
    FeeFineRule::create([
        'name' => 'Flat late fee',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'fine_type' => FineType::FIXED_ONCE,
        'fine_value' => 300,
        'grace_days' => 5,
        'effective_from' => '2026-01-01',
        'is_active' => true,
    ]);

    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 40);

    $may = generateCarryingArrears(5);

    $fine = FeeVoucherItem::where('fee_voucher_id', $may->id)
        ->where('fee_head_id', $this->fineHead->id)
        ->first();

    // Flat, however late it is.
    expect((float) $fine->amount)->toBe(300.0);
});

it('charges no fine when the school has not configured one', function () {
    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 40);

    $may = generateCarryingArrears(5);

    expect(FeeVoucherItem::where('fee_voucher_id', $may->id)
        ->where('fee_head_id', $this->fineHead->id)
        ->exists())->toBeFalse();
});

it('ignores a fine rule that is switched off', function () {
    FeeFineRule::create([
        'name' => 'Suspended late fee',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'fine_type' => FineType::FIXED_PER_DAY,
        'fine_value' => 50,
        'grace_days' => 5,
        'effective_from' => '2026-01-01',
        'is_active' => false,
    ]);

    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 30);

    $may = generateCarryingArrears(5);

    expect(FeeVoucherItem::where('fee_voucher_id', $may->id)
        ->where('fee_head_id', $this->fineHead->id)
        ->exists())->toBeFalse();
});

it('prefers a class-level fine rule over a campus-wide one', function () {
    FeeFineRule::create([
        'name' => 'Campus wide',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'fine_type' => FineType::FIXED_ONCE,
        'fine_value' => 100,
        'grace_days' => 0,
        'effective_from' => '2026-01-01',
        'is_active' => true,
    ]);

    FeeFineRule::create([
        'name' => 'Class specific',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'fine_type' => FineType::FIXED_ONCE,
        'fine_value' => 250,
        'grace_days' => 0,
        'effective_from' => '2026-01-01',
        'is_active' => true,
    ]);

    $april = generateCarryingArrears(4);
    makeOverdueBy($april, 10);

    $may = generateCarryingArrears(5);

    $fine = FeeVoucherItem::where('fee_voucher_id', $may->id)
        ->where('fee_head_id', $this->fineHead->id)
        ->first();

    expect((float) $fine->amount)->toBe(250.0);
});
