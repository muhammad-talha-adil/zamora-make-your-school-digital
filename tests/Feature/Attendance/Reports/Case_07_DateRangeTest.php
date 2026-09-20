<?php

/**
 * Case 07 — a date range for the class report (#107), for when the office
 * wants to know how a class did across a stretch that is not a whole
 * calendar month.
 */

use Inertia\Testing\AssertableInertia;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
});

/** Runs the class report and hands back its props. */
function classReportProps(array $query): array
{
    $props = [];

    test()->get(route('attendance.class-report', $query))
        ->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) use (&$props) {
            $props = $page->toArray()['props'];
        });

    return $props;
}

it('aggregates across a date range instead of a whole month', function () {
    test()->post(route('attendance.store'), test()->world->payload('P', '2026-04-06'));
    test()->post(route('attendance.store'), test()->world->payload('A', '2026-04-07'));
    // Outside the requested range, so it must not be counted.
    test()->post(route('attendance.store'), test()->world->payload('P', '2026-04-20'));

    $props = classReportProps([
        'class_id' => test()->world->school->class->id,
        'date_from' => '2026-04-06',
        'date_to' => '2026-04-08',
    ]);

    $row = collect($props['summary'])->first();

    expect($row['present'])->toBe(1)
        ->and($row['absent'])->toBe(1)
        ->and($row['total'])->toBe(2)
        ->and($props['dateFrom'])->toBe('2026-04-06')
        ->and($props['dateTo'])->toBe('2026-04-08');
});

it('still supports a plain month when no range is given', function () {
    test()->post(route('attendance.store'), test()->world->payload('P', '2026-04-06'));

    $props = classReportProps([
        'class_id' => test()->world->school->class->id,
        'month' => 4,
        'year' => 2026,
    ]);

    expect($props['dateFrom'])->toBeNull()
        ->and(collect($props['summary'])->first()['present'])->toBe(1);
});
