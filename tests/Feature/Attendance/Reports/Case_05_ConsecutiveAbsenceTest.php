<?php

/**
 * Case 05 — children who have stopped coming.
 *
 * Three or more unexplained days in a row is the standard signal here that a
 * child is drifting out of school, and it is asked for in government returns.
 * Left to a person to notice, it is noticed late: a class teacher sees one
 * absence at a time, not the pattern across a fortnight.
 */

use App\Services\Attendance\ConsecutiveAbsenceService;
use Illuminate\Support\Carbon;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
    $this->service = app(ConsecutiveAbsenceService::class);
});

/** Marks the whole class with one status on a day. */
function markDay(AttendanceWorld $world, string $date, string $code): void
{
    test()->post(route('attendance.store'), $world->payload($code, $date));
}

/** The runs found for the shared class, up to a date. */
function runsUpTo(AttendanceWorld $world, string $date, int $threshold = 3)
{
    return app(ConsecutiveAbsenceService::class)->runsFor(
        $world->school->class->id,
        $world->school->section->id,
        $world->school->session->id,
        Carbon::parse($date),
        $threshold
    );
}

it('finds nobody when everyone is attending', function () {
    foreach (['2026-04-06', '2026-04-07', '2026-04-08'] as $date) {
        markDay($this->world, $date, 'P');
    }

    expect(runsUpTo($this->world, '2026-04-08'))->toHaveCount(0);
});

it('finds a child absent three days running', function () {
    foreach (['2026-04-06', '2026-04-07', '2026-04-08'] as $date) {
        markDay($this->world, $date, 'A');
    }

    $runs = runsUpTo($this->world, '2026-04-08');

    expect($runs)->toHaveCount(2)
        ->and($runs->first()['days'])->toBe(3)
        ->and($runs->first()['since'])->toBe('2026-04-06');
});

it('does not flag two days', function () {
    markDay($this->world, '2026-04-06', 'A');
    markDay($this->world, '2026-04-07', 'A');

    expect(runsUpTo($this->world, '2026-04-07'))->toHaveCount(0);
});

it('does not count approved leave as absence', function () {
    markDay($this->world, '2026-04-06', 'A');
    markDay($this->world, '2026-04-07', 'L');
    markDay($this->world, '2026-04-08', 'A');

    // The family told the school, which is the opposite of the signal being
    // looked for.
    expect(runsUpTo($this->world, '2026-04-08'))->toHaveCount(0);
});

it('counts across a leave day without breaking the run', function () {
    markDay($this->world, '2026-04-06', 'A');
    markDay($this->world, '2026-04-07', 'A');
    markDay($this->world, '2026-04-08', 'L');
    markDay($this->world, '2026-04-09', 'A');

    // Three absences either side of a day the family explained.
    expect(runsUpTo($this->world, '2026-04-09')->first()['days'])->toBe(3);
});

it('stops counting at the day the child came back', function () {
    markDay($this->world, '2026-04-06', 'A');
    markDay($this->world, '2026-04-07', 'A');
    markDay($this->world, '2026-04-08', 'A');
    markDay($this->world, '2026-04-09', 'P');

    // A run that has ended is not a child drifting away, and flagging them
    // buries the child who is.
    expect(runsUpTo($this->world, '2026-04-09'))->toHaveCount(0);
});

it('finds a run that is still going after an earlier return', function () {
    markDay($this->world, '2026-04-06', 'A');
    markDay($this->world, '2026-04-07', 'P');

    foreach (['2026-04-08', '2026-04-09', '2026-04-10'] as $date) {
        markDay($this->world, $date, 'A');
    }

    expect(runsUpTo($this->world, '2026-04-10')->first()['days'])->toBe(3);
});

it('reports the child and the dates', function () {
    foreach (['2026-04-06', '2026-04-07', '2026-04-08'] as $date) {
        markDay($this->world, $date, 'A');
    }

    $run = runsUpTo($this->world, '2026-04-08')->first();

    expect($run['student']->id)->toBeIn([$this->students[0]->id, $this->students[1]->id])
        ->and($run['since'])->toBe('2026-04-06')
        ->and($run['last'])->toBe('2026-04-08');
});

it('takes a higher threshold when a school wants one', function () {
    foreach (['2026-04-06', '2026-04-07', '2026-04-08'] as $date) {
        markDay($this->world, $date, 'A');
    }

    expect(runsUpTo($this->world, '2026-04-08', threshold: 5))->toHaveCount(0);
});

it('does not count a late arrival as an absence', function () {
    markDay($this->world, '2026-04-06', 'A');
    markDay($this->world, '2026-04-07', 'LT');
    markDay($this->world, '2026-04-08', 'A');

    expect(runsUpTo($this->world, '2026-04-08'))->toHaveCount(0);
});
