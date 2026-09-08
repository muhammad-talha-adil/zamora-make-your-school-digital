<?php

/**
 * Case 04 — which concession applies when a student holds several.
 *
 * A child can qualify for more than one at once: sibling, staff child, merit.
 * Adding them together would run past the charge — the seeded types alone
 * include MERIT at 100% and STAFF at 50% — so only the largest applies to any
 * one fee head, and it can never exceed the charge itself.
 */

use App\Enums\Fee\ApprovalStatus;
use App\Models\Fee\DiscountType;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Fee\StudentDiscount;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000]);
    $this->monthlyHead = $this->world->school->monthlyHead;
});

/**
 * Records an approved concession against the student.
 */
function giveDiscount(FeeWorld $world, array $attributes): StudentDiscount
{
    $type = DiscountType::firstOrCreate(
        ['code' => $attributes['code'] ?? 'SIBLING'],
        [
            'name' => $attributes['code'] ?? 'Sibling',
            'value_type' => 'percent',
            'default_value' => 10,
            'is_active' => true,
            'requires_approval' => false,
        ]
    );

    return StudentDiscount::create([
        'student_id' => $world->student->id,
        'student_enrollment_record_id' => $world->enrollment->id,
        'discount_type_id' => $type->id,
        'fee_head_id' => $attributes['fee_head_id'] ?? null,
        'value_type' => $attributes['value_type'] ?? 'percent',
        'value' => $attributes['value'],
        'effective_from' => $attributes['effective_from'] ?? '2026-04-01',
        'effective_to' => $attributes['effective_to'] ?? null,
        'approval_status' => ApprovalStatus::APPROVED,
        'reason' => 'Test',
    ]);
}

/** The monthly tuition line on the given month's voucher. */
function tuitionLine(FeeWorld $world, int $month = 4): FeeVoucherItem
{
    return $world->generate($month)->firstWhere('fee_head_id', $world->school->monthlyHead->id);
}

it('applies a single concession', function () {
    giveDiscount($this->world, ['code' => 'SIBLING', 'value' => 10, 'fee_head_id' => $this->monthlyHead->id]);

    expect((float) tuitionLine($this->world)->discount_amount)->toBe(500.0);
});

it('applies only the largest when a student holds two', function () {
    giveDiscount($this->world, ['code' => 'SIBLING', 'value' => 10, 'fee_head_id' => $this->monthlyHead->id]);
    giveDiscount($this->world, ['code' => 'STAFF', 'value' => 50, 'fee_head_id' => $this->monthlyHead->id]);

    // 50% of 5,000 — not 10% + 50%.
    expect((float) tuitionLine($this->world)->discount_amount)->toBe(2500.0);
});

it('compares concessions in rupees, not in percent', function () {
    // 10% of 5,000 is Rs 500; a flat Rs 800 is larger despite the smaller number.
    giveDiscount($this->world, ['code' => 'SIBLING', 'value' => 10, 'fee_head_id' => $this->monthlyHead->id]);
    giveDiscount($this->world, [
        'code' => 'UNIFORM_BUNDLE',
        'value' => 800,
        'value_type' => 'fixed',
        'fee_head_id' => $this->monthlyHead->id,
    ]);

    expect((float) tuitionLine($this->world)->discount_amount)->toBe(800.0);
});

it('never discounts more than the charge itself', function () {
    giveDiscount($this->world, [
        'code' => 'MERIT',
        'value' => 9000,
        'value_type' => 'fixed',
        'fee_head_id' => $this->monthlyHead->id,
    ]);

    $line = tuitionLine($this->world);

    // Capped at the charge, so the voucher line cannot go negative.
    expect((float) $line->discount_amount)->toBe(5000.0)
        ->and((float) $line->net_amount)->toBe(0.0);
});

it('lets a full concession clear the charge', function () {
    giveDiscount($this->world, ['code' => 'ORPHAN', 'value' => 100, 'fee_head_id' => $this->monthlyHead->id]);

    expect((float) tuitionLine($this->world)->net_amount)->toBe(0.0);
});

it('applies a concession that covers every fee head', function () {
    giveDiscount($this->world, ['code' => 'STAFF', 'value' => 20, 'fee_head_id' => null]);

    $items = $this->world->generate(4)->keyBy('fee_head_id');

    expect((float) $items[$this->monthlyHead->id]->discount_amount)->toBe(1000.0)
        ->and((float) $items[$this->world->school->annualHead->id]->discount_amount)->toBe(2400.0);
});

it('settles each fee head on its own', function () {
    giveDiscount($this->world, ['code' => 'SIBLING', 'value' => 10, 'fee_head_id' => $this->monthlyHead->id]);
    giveDiscount($this->world, [
        'code' => 'ANNUAL_PAY',
        'value' => 25,
        'fee_head_id' => $this->world->school->annualHead->id,
    ]);

    $items = $this->world->generate(4)->keyBy('fee_head_id');

    // Concessions on different heads do not compete with each other.
    expect((float) $items[$this->monthlyHead->id]->discount_amount)->toBe(500.0)
        ->and((float) $items[$this->world->school->annualHead->id]->discount_amount)->toBe(3000.0);
});

it('applies a concession that was valid in the month being billed', function () {
    giveDiscount($this->world, [
        'code' => 'SIBLING',
        'value' => 10,
        'fee_head_id' => $this->monthlyHead->id,
        'effective_from' => '2026-04-01',
        'effective_to' => '2026-04-30',
    ]);

    // April's voucher is judged against April, whenever it is generated.
    expect((float) tuitionLine($this->world, 4)->discount_amount)->toBe(500.0);
});

it('ignores a concession that had not started yet', function () {
    giveDiscount($this->world, [
        'code' => 'SIBLING',
        'value' => 10,
        'fee_head_id' => $this->monthlyHead->id,
        'effective_from' => '2027-01-01',
    ]);

    expect((float) tuitionLine($this->world)->discount_amount)->toBe(0.0);
});

it('ignores a concession that is still awaiting approval', function () {
    $discount = giveDiscount($this->world, [
        'code' => 'NEED_BASED',
        'value' => 50,
        'fee_head_id' => $this->monthlyHead->id,
    ]);

    $discount->update(['approval_status' => ApprovalStatus::PENDING]);

    expect((float) tuitionLine($this->world)->discount_amount)->toBe(0.0);
});
