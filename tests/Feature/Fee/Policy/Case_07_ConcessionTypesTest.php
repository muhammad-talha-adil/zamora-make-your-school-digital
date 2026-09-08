<?php

/**
 * Case 07 — the concessions schools here actually give.
 *
 * The seeded list held the generic ones. What a Pakistani school grants in
 * practice also includes a hafiz-e-Quran concession and a place met from the
 * zakat fund, both of which need an approval before they take effect.
 */

use App\Models\Fee\DiscountType;
use Database\Seeders\DiscountTypeSeeder;

beforeEach(function () {
    $this->seed(DiscountTypeSeeder::class);
});

it('seeds the local concession categories', function () {
    $codes = DiscountType::pluck('code')->all();

    expect($codes)->toContain('ORPHAN')
        ->and($codes)->toContain('HAFIZ')
        ->and($codes)->toContain('ZAKAT')
        ->and($codes)->toContain('STAFF_CHILD');
});

it('gates the ones that need a decision behind approval', function () {
    $gated = DiscountType::whereIn('code', ['HAFIZ', 'ZAKAT', 'STAFF_CHILD', 'ORPHAN'])
        ->pluck('requires_approval', 'code');

    // None of these should apply themselves; a person signs each one off.
    expect($gated->values()->every(fn ($needs) => (bool) $needs))->toBeTrue();
});

it('can be run again without duplicating what a school already uses', function () {
    $before = DiscountType::count();

    $this->seed(DiscountTypeSeeder::class);

    expect(DiscountType::count())->toBe($before);
});

it('keeps one row per code', function () {
    $this->seed(DiscountTypeSeeder::class);

    $codes = DiscountType::pluck('code');

    expect($codes->count())->toBe($codes->unique()->count());
});
