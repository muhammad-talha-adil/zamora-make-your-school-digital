<?php

/**
 * Case 04 — a yearly charge collected in instalments.
 *
 * A yearly charge dropped its whole amount into the first month of the session,
 * so a Rs 24,000 annual charge landed on top of that month's tuition and the
 * parent was handed a bill they could not pay. Most schools here split it in
 * two or three.
 */

use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000, 'yearly' => 12000]);
    $this->annualHead = $this->world->school->annualHead;

    $this->annualItem = $this->world->structure->items()
        ->where('fee_head_id', $this->annualHead->id)
        ->firstOrFail();
});

/** Splits the annual charge across the given months. */
function splitAcross(FeeWorld $world, array $monthNumbers): void
{
    $world->structure->items()
        ->where('fee_head_id', $world->school->annualHead->id)
        ->update([
            'instalment_count' => count($monthNumbers),
            'instalment_month_ids' => json_encode(
                array_map(fn ($month) => $world->monthId($month), $monthNumbers)
            ),
        ]);
}

/** The annual line on a month's voucher, if there is one. */
function annualLine(FeeWorld $world, int $month)
{
    return $world->generate($month)->firstWhere('fee_head_id', $world->school->annualHead->id);
}

it('bills the whole charge in the first month when it is not split', function () {
    expect((float) annualLine($this->world, 4)->amount)->toBe(12000.0);
});

it('bills half in each of two instalment months', function () {
    splitAcross($this->world, [4, 10]);

    expect((float) annualLine($this->world, 4)->amount)->toBe(6000.0);

    $this->world->generate(5);

    expect((float) annualLine($this->world, 10)->amount)->toBe(6000.0);
});

it('does not bill the charge in a month that is not an instalment month', function () {
    splitAcross($this->world, [4, 10]);

    $this->world->generate(4);

    expect(annualLine($this->world, 5))->toBeNull();
});

it('bills nothing in the first month when the instalments fall elsewhere', function () {
    splitAcross($this->world, [8, 1]);

    // April is the session's first month but carries no instalment.
    expect(annualLine($this->world, 4))->toBeNull();
});

it('labels each instalment so the parent can see which one it is', function () {
    splitAcross($this->world, [4, 10]);

    expect(annualLine($this->world, 4)->description)->toContain('Instalment 1 of 2');
});

it('lets the last instalment absorb the rounding', function () {
    $this->world->structure->items()
        ->where('fee_head_id', $this->annualHead->id)
        ->update(['amount' => 10000]);

    splitAcross($this->world, [4, 7, 10]);

    $first = (float) annualLine($this->world, 4)->amount;

    $this->world->generate(5);
    $this->world->generate(6);
    $second = (float) annualLine($this->world, 7)->amount;

    $this->world->generate(8);
    $this->world->generate(9);
    $third = (float) annualLine($this->world, 10)->amount;

    // 3,333.33 + 3,333.33 + 3,333.34, never Rs 9,999.99.
    expect($first)->toBe(3333.33)
        ->and($second)->toBe(3333.33)
        ->and($third)->toBe(3333.34)
        ->and(round($first + $second + $third, 2))->toBe(10000.0);
});

it('leaves the monthly charge alone', function () {
    splitAcross($this->world, [4, 10]);

    $tuition = $this->world->generate(4)
        ->firstWhere('fee_head_id', $this->world->school->monthlyHead->id);

    expect((float) $tuition->amount)->toBe(5000.0);
});

it('orders instalments by month, whatever order they were entered in', function () {
    splitAcross($this->world, [10, 4]);

    expect(annualLine($this->world, 4)->description)->toContain('Instalment 1 of 2');
});
