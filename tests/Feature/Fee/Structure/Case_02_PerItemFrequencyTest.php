<?php

/**
 * Case 02 — a charge's frequency belongs to the structure, not the fee head.
 *
 * The same head is billed differently by different schools and different
 * classes: a computer lab charge is monthly in Class 9 and yearly in Class 5.
 * The column was always per item, but the form's value was thrown away and the
 * head's default written instead.
 */

use App\Enums\Fee\FeeFrequency;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use Illuminate\Support\Collection;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

/**
 * Creates a structure and returns its items, keyed by fee head.
 *
 * @return Collection<int, FeeStructureItem>
 */
function itemsAfterCreate(AdmissionWorld $world, array $items, string $title = 'Frequency Test')
{
    test()->post(route('fee.structures.store'), [
        'title' => $title,
        'session_id' => $world->session->id,
        'campus_id' => $world->campus->id,
        'class_id' => $world->class->id,
        'status' => 'active',
        'items' => $items,
    ])->assertSessionHasNoErrors();

    $structure = FeeStructure::where('title', $title)->firstOrFail();

    return $structure->items()->get()->keyBy('fee_head_id');
}

it('stores the frequency chosen on the form', function () {
    $items = itemsAfterCreate($this->world, [
        ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 5000, 'frequency' => 'yearly'],
    ]);

    // The head's own default is monthly; this structure overrides it.
    expect($items[$this->world->monthlyHead->id]->frequency)->toBe(FeeFrequency::YEARLY);
});

it('falls back to the fee head default when the form gives none', function () {
    $items = itemsAfterCreate($this->world, [
        ['fee_head_id' => $this->world->annualHead->id, 'amount' => 12000],
    ]);

    expect($items[$this->world->annualHead->id]->frequency)->toBe(FeeFrequency::YEARLY);
});

it('bills the same head at different frequencies in two structures', function () {
    $classFive = itemsAfterCreate($this->world, [
        ['fee_head_id' => $this->world->optionalHead->id, 'amount' => 3000, 'frequency' => 'monthly'],
    ], 'Class 5 Sports');

    $classNine = itemsAfterCreate($this->world, [
        ['fee_head_id' => $this->world->optionalHead->id, 'amount' => 3000, 'frequency' => 'yearly'],
    ], 'Class 9 Sports');

    expect($classFive[$this->world->optionalHead->id]->frequency)->toBe(FeeFrequency::MONTHLY)
        ->and($classNine[$this->world->optionalHead->id]->frequency)->toBe(FeeFrequency::YEARLY);
});

it('stores the flags that decide when a charge applies', function () {
    $items = itemsAfterCreate($this->world, [
        [
            'fee_head_id' => $this->world->optionalHead->id,
            'amount' => 1500,
            'frequency' => 'once',
            'is_optional' => true,
            'applicable_on_admission' => true,
            'notes' => 'Charged with the admission form.',
        ],
    ]);

    $item = $items[$this->world->optionalHead->id];

    expect($item->frequency)->toBe(FeeFrequency::ONCE)
        ->and((bool) $item->is_optional)->toBeTrue()
        ->and((bool) $item->applicable_on_admission)->toBeTrue()
        ->and($item->notes)->toBe('Charged with the admission form.');
});

it('stores a part-year month range', function () {
    $items = itemsAfterCreate($this->world, [
        [
            'fee_head_id' => $this->world->monthlyHead->id,
            'amount' => 5000,
            'starts_from_month_id' => $this->world->month(8)->id,
            'ends_at_month_id' => $this->world->month(6)->id,
        ],
    ]);

    $item = $items[$this->world->monthlyHead->id];

    // August to June — the summer break is not billed.
    expect($item->starts_from_month_id)->toBe($this->world->month(8)->id)
        ->and($item->ends_at_month_id)->toBe($this->world->month(6)->id);
});
