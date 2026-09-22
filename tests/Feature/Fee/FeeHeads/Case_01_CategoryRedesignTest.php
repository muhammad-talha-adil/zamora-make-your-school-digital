<?php

/**
 * Case 01 — the fee head category redesign.
 *
 * `category` used to double as billing frequency (monthly/annual/one_time)
 * as well as fee nature (transport/fine/discount/misc), duplicating the
 * separate `default_frequency` column. This case locks in the new
 * category set, its default-frequency mapping, and the migration that
 * remaps rows saved under the old values.
 */

use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\FeeHeadCategory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
});

it('accepts every new category value on create', function (string $category) {
    $response = $this->post(route('fee.heads.store'), [
        'name' => 'Test Head '.$category,
        'category' => $category,
        'default_frequency' => FeeFrequency::MONTHLY->value,
        'is_recurring' => true,
        'is_optional' => false,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('fee_heads', [
        'name' => 'Test Head '.$category,
        'category' => $category,
    ]);
})->with(fn () => collect(FeeHeadCategory::cases())->pluck('value')->all());

it('rejects the old conflated category values', function (string $oldCategory) {
    $response = $this->post(route('fee.heads.store'), [
        'name' => 'Old Category Head',
        'category' => $oldCategory,
        'default_frequency' => FeeFrequency::MONTHLY->value,
        'is_recurring' => true,
        'is_optional' => false,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('category');
})->with(['monthly', 'annual', 'one_time']);

it('maps each category to its expected default frequency', function (string $category, string $expected) {
    expect(FeeHeadCategory::from($category)->defaultFrequency())->toBe(FeeFrequency::from($expected));
})->with([
    ['tuition', 'monthly'],
    ['admission', 'once'],
    ['annual_charges', 'yearly'],
    ['examination', 'once'],
    ['transport', 'monthly'],
    ['library', 'yearly'],
    ['laboratory', 'yearly'],
    ['sports', 'yearly'],
    ['security', 'once'],
    ['fine', 'once'],
    ['discount', 'monthly'],
    ['misc', 'monthly'],
]);

it('remaps fee heads saved under the old category values when the migration runs', function () {
    // Roll the redesign migration back to restore the old enum column and
    // value set, insert rows the old way (bypassing the model cast, straight
    // against the raw column), then re-run the migration and confirm every
    // row lands on the documented new value.
    Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);

    $oldToNew = [
        'monthly' => 'tuition',
        'annual' => 'annual_charges',
        'one_time' => 'misc',
        'transport' => 'transport',
        'fine' => 'fine',
        'discount' => 'discount',
        'misc' => 'misc',
    ];

    $ids = [];

    foreach (array_keys($oldToNew) as $old) {
        $ids[$old] = DB::table('fee_heads')->insertGetId([
            'name' => 'Legacy '.$old,
            'code' => 'LEGACY_'.strtoupper($old),
            'category' => $old,
            'is_recurring' => false,
            'default_frequency' => 'once',
            'is_optional' => false,
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    Artisan::call('migrate', ['--force' => true]);

    foreach ($oldToNew as $old => $expectedNew) {
        expect(DB::table('fee_heads')->where('id', $ids[$old])->value('category'))->toBe($expectedNew);
    }
});
