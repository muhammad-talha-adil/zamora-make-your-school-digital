<?php

/**
 * Case 02 — the late fine as schools here actually charge it.
 *
 * The rules could only say one thing at a time: a flat fine, or a daily one, or
 * a percentage. What is run in practice is a slab — due date, a few days'
 * grace, a fixed fine, then so much per day, and the whole thing capped so a
 * voucher nobody chased does not grow past the fee itself.
 */

use App\Enums\Fee\FineType;
use App\Models\Fee\FeeFineRule;

/** A rule of the given shape, unsaved — the arithmetic is what is under test. */
function fineRule(array $attributes): FeeFineRule
{
    return new FeeFineRule(array_merge([
        'fine_type' => FineType::SLAB,
        'fine_value' => 0,
        'initial_amount' => 0,
        'daily_amount' => 0,
        'max_fine_amount' => null,
        'grace_days' => 0,
    ], $attributes));
}

it('charges nothing inside the grace period', function () {
    $rule = fineRule(['grace_days' => 5, 'initial_amount' => 200, 'daily_amount' => 50]);

    expect($rule->calculateFine(5000, 5))->toBe(0.0);
});

it('charges the fixed part on the first day past grace', function () {
    $rule = fineRule(['grace_days' => 5, 'initial_amount' => 200, 'daily_amount' => 50]);

    // Day six is the first late day: Rs 200, not Rs 250.
    expect($rule->calculateFine(5000, 6))->toBe(200.0);
});

it('adds the daily part from the day after that', function () {
    $rule = fineRule(['grace_days' => 5, 'initial_amount' => 200, 'daily_amount' => 50]);

    // Ten days late is five days past grace: 200 + (50 × 4).
    expect($rule->calculateFine(5000, 10))->toBe(400.0);
});

it('stops at the cap however late the voucher is', function () {
    $rule = fineRule([
        'grace_days' => 5,
        'initial_amount' => 200,
        'daily_amount' => 50,
        'max_fine_amount' => 1000,
    ]);

    expect($rule->calculateFine(5000, 365))->toBe(1000.0);
});

it('caps a per-day fine too', function () {
    $rule = fineRule([
        'fine_type' => FineType::FIXED_PER_DAY,
        'fine_value' => 50,
        'max_fine_amount' => 500,
    ]);

    // An uncapped daily fine was the sharpest edge of the old design.
    expect($rule->calculateFine(5000, 100))->toBe(500.0);
});

it('still charges a flat fine the old way', function () {
    $rule = fineRule(['fine_type' => FineType::FIXED_ONCE, 'fine_value' => 500, 'grace_days' => 3]);

    expect($rule->calculateFine(5000, 30))->toBe(500.0)
        ->and($rule->calculateFine(5000, 2))->toBe(0.0);
});

it('still charges a percentage the old way', function () {
    $rule = fineRule(['fine_type' => FineType::PERCENT, 'fine_value' => 2]);

    expect($rule->calculateFine(5000, 10))->toBe(100.0);
});

it('never returns a negative fine', function () {
    $rule = fineRule(['grace_days' => 0, 'initial_amount' => 0, 'daily_amount' => 0]);

    expect($rule->calculateFine(5000, 30))->toBe(0.0);
});

it('charges only the fixed part when there is no daily rate', function () {
    $rule = fineRule(['grace_days' => 10, 'initial_amount' => 300, 'daily_amount' => 0]);

    expect($rule->calculateFine(5000, 40))->toBe(300.0);
});

it('rounds to paisa', function () {
    $rule = fineRule(['fine_type' => FineType::PERCENT, 'fine_value' => 1.5]);

    expect($rule->calculateFine(3333, 10))->toBe(50.0);
});
