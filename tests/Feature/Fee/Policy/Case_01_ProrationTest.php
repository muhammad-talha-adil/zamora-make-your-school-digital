<?php

/**
 * Case 01 — a child admitted part-way through the month.
 *
 * Schools here do not agree on this, so it is a campus setting rather than a
 * rule: some charge the full month whatever the date, some halve it after the
 * middle of the month, and some count the days. The default charges the full
 * month, which is how the system behaved before the setting existed.
 */

use App\Models\Fee\FeePolicy;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000, 'yearly' => 12000, 'once' => 20000]);
    $this->monthlyHead = $this->world->school->monthlyHead;
});

/** Sets the campus policy for these tests. */
function feePolicyFor(FeeWorld $world, array $attributes): FeePolicy
{
    return FeePolicy::create(array_merge([
        'campus_id' => $world->school->campus->id,
        'session_id' => $world->school->session->id,
    ], $attributes));
}

/** Moves the student's admission to a date inside April. */
function admittedOn(FeeWorld $world, string $date): void
{
    $world->student->update(['admission_date' => $date]);
    $world->enrollment->update(['admission_date' => $date]);
}

/** April's tuition line. */
function aprilTuition(FeeWorld $world)
{
    return $world->generate(4)->firstWhere('fee_head_id', $world->school->monthlyHead->id);
}

it('charges a full month when the campus has set no policy', function () {
    admittedOn($this->world, '2026-04-25');

    expect((float) aprilTuition($this->world)->amount)->toBe(5000.0);
});

it('charges a full month when the policy says not to prorate', function () {
    feePolicyFor($this->world, ['proration_method' => 'none']);
    admittedOn($this->world, '2026-04-25');

    expect((float) aprilTuition($this->world)->amount)->toBe(5000.0);
});

it('halves the month for a child admitted after the cut-off day', function () {
    feePolicyFor($this->world, ['proration_method' => 'half_month', 'proration_cutoff_day' => 16]);
    admittedOn($this->world, '2026-04-20');

    expect((float) aprilTuition($this->world)->amount)->toBe(2500.0);
});

it('charges the full month for a child admitted before the cut-off day', function () {
    feePolicyFor($this->world, ['proration_method' => 'half_month', 'proration_cutoff_day' => 16]);
    admittedOn($this->world, '2026-04-10');

    expect((float) aprilTuition($this->world)->amount)->toBe(5000.0);
});

it('charges by the day when the policy says so', function () {
    feePolicyFor($this->world, ['proration_method' => 'daily']);
    admittedOn($this->world, '2026-04-21');

    // April has 30 days; admitted on the 21st leaves 10 days including the day
    // itself, so a third of the month.
    expect((float) aprilTuition($this->world)->amount)->toBe(1666.67);
});

it('charges a whole month to a child admitted on the first', function () {
    feePolicyFor($this->world, ['proration_method' => 'daily']);
    admittedOn($this->world, '2026-04-01');

    expect((float) aprilTuition($this->world)->amount)->toBe(5000.0);
});

it('prorates only the admission month, not the ones after it', function () {
    feePolicyFor($this->world, ['proration_method' => 'half_month']);
    admittedOn($this->world, '2026-04-20');

    $this->world->generate(4);
    $may = $this->world->generate(5)->firstWhere('fee_head_id', $this->monthlyHead->id);

    expect((float) $may->amount)->toBe(5000.0);
});

it('does not prorate a yearly charge', function () {
    feePolicyFor($this->world, ['proration_method' => 'half_month']);
    admittedOn($this->world, '2026-04-20');

    $annual = $this->world->generate(4)
        ->firstWhere('fee_head_id', $this->world->school->annualHead->id);

    // Half a year's charge for arriving late in the month would be a windfall.
    expect((float) $annual->amount)->toBe(12000.0);
});

it('does not prorate a one-time charge', function () {
    feePolicyFor($this->world, ['proration_method' => 'half_month']);
    admittedOn($this->world, '2026-04-20');

    $admission = $this->world->generate(4)
        ->firstWhere('fee_head_id', $this->world->admissionHead()->id);

    expect((float) $admission->amount)->toBe(20000.0);
});

it('prefers the session policy over the campus default', function () {
    FeePolicy::create([
        'campus_id' => $this->world->school->campus->id,
        'session_id' => null,
        'proration_method' => 'none',
    ]);

    feePolicyFor($this->world, ['proration_method' => 'half_month']);
    admittedOn($this->world, '2026-04-20');

    expect((float) aprilTuition($this->world)->amount)->toBe(2500.0);
});

it('falls back to the campus default when the session has no policy of its own', function () {
    FeePolicy::create([
        'campus_id' => $this->world->school->campus->id,
        'session_id' => null,
        'proration_method' => 'half_month',
    ]);

    admittedOn($this->world, '2026-04-20');

    expect((float) aprilTuition($this->world)->amount)->toBe(2500.0);
});

it('ignores a policy that has been switched off', function () {
    feePolicyFor($this->world, ['proration_method' => 'half_month', 'is_active' => false]);
    admittedOn($this->world, '2026-04-20');

    expect((float) aprilTuition($this->world)->amount)->toBe(5000.0);
});
