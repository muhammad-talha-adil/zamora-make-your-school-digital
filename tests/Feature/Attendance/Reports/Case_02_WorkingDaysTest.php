<?php

/**
 * Case 02 — how many days a child was actually expected.
 *
 * Without this a percentage has no denominator: the report divided by "days
 * somebody happened to mark", so a child marked on three days out of twenty-two
 * read as 100%. A day counts only if the campus works that weekday, it is not a
 * holiday, and the child was on the roll.
 */

use App\Models\Holiday;
use App\Services\Attendance\WorkingDayCalculator;
use Illuminate\Support\Carbon;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->calculator = app(WorkingDayCalculator::class);

    // April 2026: 30 days, starting on a Wednesday.
    $this->from = Carbon::create(2026, 4, 1)->startOfDay();
    $this->to = Carbon::create(2026, 4, 30)->endOfDay();
});

/** The enrollment of the child enrolled in beforeEach. */
function enrollment(AttendanceWorld $world)
{
    return $world->students[0]->currentEnrollment;
}

it('counts a six-day week', function () {
    $this->world->policy([1, 2, 3, 4, 5, 6]);

    // April 2026 has four Sundays.
    expect($this->calculator->expectedDaysFor(enrollment($this->world), $this->from, $this->to))
        ->toBe(26);
});

it('counts a five-day week', function () {
    $this->world->policy([1, 2, 3, 4, 5]);

    // Four Saturdays and four Sundays out.
    expect($this->calculator->expectedDaysFor(enrollment($this->world), $this->from, $this->to))
        ->toBe(22);
});

it('assumes a six-day week when the campus has set no policy', function () {
    // A school that has configured nothing still gets a sensible denominator.
    expect($this->calculator->expectedDaysFor(enrollment($this->world), $this->from, $this->to))
        ->toBe(26);
});

it('leaves holidays out', function () {
    $this->world->policy([1, 2, 3, 4, 5, 6]);

    Holiday::create([
        'title' => 'Eid',
        'start_date' => '2026-04-06',
        'end_date' => '2026-04-08',
        'is_national' => true,
        'is_attendance_allowed' => false,
    ]);

    expect($this->calculator->expectedDaysFor(enrollment($this->world), $this->from, $this->to))
        ->toBe(23);
});

it('keeps a holiday the school declared a working day', function () {
    $this->world->policy([1, 2, 3, 4, 5, 6]);

    Holiday::create([
        'title' => 'Exam day',
        'start_date' => '2026-04-06',
        'end_date' => '2026-04-06',
        'is_national' => true,
        'is_attendance_allowed' => true,
    ]);

    expect($this->calculator->expectedDaysFor(enrollment($this->world), $this->from, $this->to))
        ->toBe(26);
});

it('does not count days before the child was admitted', function () {
    $this->world->policy([1, 2, 3, 4, 5, 6]);

    // Admitted on the 20th: not absent for the first nineteen days.
    enrollment($this->world)->update(['admission_date' => '2026-04-20']);

    expect($this->calculator->expectedDaysFor(enrollment($this->world)->fresh(), $this->from, $this->to))
        ->toBe(10);
});

it('does not count days after the child left', function () {
    $this->world->policy([1, 2, 3, 4, 5, 6]);

    enrollment($this->world)->update(['leave_date' => '2026-04-10']);

    expect($this->calculator->expectedDaysFor(enrollment($this->world)->fresh(), $this->from, $this->to))
        ->toBe(9);
});

it('counts nothing for a child who was not enrolled that month', function () {
    $this->world->policy([1, 2, 3, 4, 5, 6]);

    enrollment($this->world)->update(['admission_date' => '2026-06-01']);

    expect($this->calculator->expectedDaysFor(enrollment($this->world)->fresh(), $this->from, $this->to))
        ->toBe(0);
});

it('counts a campus week without a child in it', function () {
    $this->world->policy([1, 2, 3, 4, 5]);

    expect($this->calculator->workingDaysBetween(
        $this->from,
        $this->to,
        $this->world->school->campus->id,
        $this->world->school->session->id
    ))->toBe(22);
});

it('counts a single day', function () {
    $this->world->policy([1, 2, 3, 4, 5, 6]);

    $monday = Carbon::create(2026, 4, 6);
    $sunday = Carbon::create(2026, 4, 5);

    expect($this->calculator->workingDaysBetween($monday, $monday, $this->world->school->campus->id))->toBe(1)
        ->and($this->calculator->workingDaysBetween($sunday, $sunday, $this->world->school->campus->id))->toBe(0);
});
