<?php

/**
 * Case 06 — "days present out of working days", for a report card.
 *
 * Every result card printed here carries it beside the marks, and the education
 * department asks for it. The figures already exist in the monthly summaries;
 * this is the join, stated once, so the exam module cannot arrive at a
 * different answer from the attendance reports.
 */

use App\Services\Attendance\ReportCardAttendanceService;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
    $this->service = app(ReportCardAttendanceService::class);
    $this->session = $this->world->school->session->id;
});

/** Marks the class on a day. */
function mark(AttendanceWorld $world, string $date, string $code): void
{
    test()->post(route('attendance.store'), $world->payload($code, $date));
}

/** The first child's figures for a span of months. */
function figuresFor(AttendanceWorld $world, int $from, int $to): array
{
    return app(ReportCardAttendanceService::class)->forStudent(
        $world->students[0]->id,
        $world->school->session->id,
        $from,
        $to,
        2026
    );
}

it('gives zeroes for a month with no register at all', function () {
    $figures = figuresFor($this->world, 4, 4);

    expect($figures['present'])->toBe(0.0)
        ->and($figures['percentage'])->toBe(0.0);
});

it('counts a month', function () {
    mark($this->world, '2026-04-06', 'P');
    mark($this->world, '2026-04-07', 'A');
    mark($this->world, '2026-04-08', 'P');

    $figures = figuresFor($this->world, 4, 4);

    expect($figures['present'])->toBe(2.0)
        ->and($figures['absent'])->toBe(1)
        ->and($figures['marked_days'])->toBe(3)
        ->and($figures['expected_days'])->toBe(26);
});

it('counts a half day as half', function () {
    mark($this->world, '2026-04-06', 'HD');

    expect(figuresFor($this->world, 4, 4)['present'])->toBe(0.5);
});

it('counts a late arrival as a full day', function () {
    mark($this->world, '2026-04-06', 'LT');

    // The child was in class; the lateness is chased separately.
    expect(figuresFor($this->world, 4, 4)['present'])->toBe(1.0)
        ->and(figuresFor($this->world, 4, 4)['late'])->toBe(1);
});

it('adds a term together', function () {
    mark($this->world, '2026-04-06', 'P');
    mark($this->world, '2026-05-06', 'P');
    mark($this->world, '2026-06-08', 'P');

    $term = figuresFor($this->world, 4, 6);

    // A result card covers the term it belongs to, not one month.
    expect($term['present'])->toBe(3.0)
        ->and($term['marked_days'])->toBe(3)
        ->and($term['expected_days'])->toBeGreaterThan(70);
});

it('shows the days nobody took a register', function () {
    mark($this->world, '2026-04-06', 'P');

    // The school's own gap, shown rather than buried — hiding it flatters
    // every child's percentage equally.
    expect(figuresFor($this->world, 4, 4)['unmarked_days'])->toBe(25);
});

it('measures against the days expected, not the days marked', function () {
    foreach (['06', '07', '08'] as $day) {
        mark($this->world, '2026-04-'.$day, 'P');
    }

    // Three out of three would be 100%; three out of twenty-six is not.
    expect(figuresFor($this->world, 4, 4)['percentage'])->toBeLessThan(15.0);
});

it('does not count days before a child was admitted', function () {
    $this->students[0]->currentEnrollment->update(['admission_date' => '2026-04-20']);

    expect(figuresFor($this->world, 4, 4)['expected_days'])->toBe(10);
});

it('prints the line a result card carries', function () {
    mark($this->world, '2026-04-06', 'P');
    mark($this->world, '2026-04-07', 'P');

    $line = $this->service->line($this->students[0]->id, $this->session, 4, 4, 2026);

    expect($line)->toContain('2 / 26');
});

it('gives a whole class in one pass', function () {
    mark($this->world, '2026-04-06', 'P');

    $class = $this->service->forClass(
        $this->world->school->class->id,
        $this->world->school->section->id,
        $this->session,
        4,
        4,
        2026
    );

    expect($class)->toHaveCount(2)
        ->and($class[$this->students[0]->id]['present'])->toBe(1.0);
});

it('agrees with the class report', function () {
    mark($this->world, '2026-04-06', 'P');

    $reportCard = figuresFor($this->world, 4, 4);

    $classRow = [];
    $this->get(route('attendance.class-report', [
        'class_id' => $this->world->school->class->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertInertia(function ($page) use (&$classRow) {
        $classRow = collect($page->toArray()['props']['summary'])
            ->firstWhere('registration_no', test()->students[0]->registration_no);
    });

    // The whole point of putting the join in one place.
    expect($reportCard['expected_days'])->toBe($classRow['expected_days'])
        ->and($reportCard['percentage'])->toBe($classRow['percentage']);
});
