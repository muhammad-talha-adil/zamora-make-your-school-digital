<?php

/**
 * Case 01 — when the school day starts, which is not the same all year.
 *
 * "Late" cannot be a fixed time here. School starts an hour or more earlier
 * through Ramzan and later in winter in the north, so a child walking in at
 * 8:15 is late in one month and early in the next.
 */

use App\Models\AttendanceTiming;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->campus = $this->world->school->campus;
    $this->session = $this->world->school->session;
});

/** A clock for a period of the year. */
function timing(AttendanceWorld $world, string $name, string $from, string $to, string $starts, string $lateAfter): AttendanceTiming
{
    return AttendanceTiming::create([
        'campus_id' => $world->school->campus->id,
        'session_id' => $world->school->session->id,
        'name' => $name,
        'starts_on' => $from,
        'ends_on' => $to,
        'day_starts_at' => $starts,
        'late_after' => $lateAfter,
        'is_active' => true,
    ]);
}

it('finds nothing when a campus has set no clock', function () {
    // Lateness stays a matter of judgement, as it was before this existed.
    expect(AttendanceTiming::inForce($this->campus->id, '2026-04-06', $this->session->id))->toBeNull();
});

it('finds the clock in force on a date', function () {
    timing($this->world, 'Regular', '2026-04-01', '2027-03-31', '08:00', '08:15');

    $found = AttendanceTiming::inForce($this->campus->id, '2026-04-06', $this->session->id);

    expect($found?->name)->toBe('Regular');
});

it('prefers the narrower period', function () {
    timing($this->world, 'Regular', '2026-04-01', '2027-03-31', '08:00', '08:15');
    timing($this->world, 'Ramzan', '2027-02-01', '2027-03-02', '07:00', '07:10');

    // A campus states its regular timing across the session and drops Ramzan
    // inside it; nobody should have to switch the regular one off around it.
    expect(AttendanceTiming::inForce($this->campus->id, '2027-02-15', $this->session->id)?->name)
        ->toBe('Ramzan');
});

it('returns to the regular clock after the special period', function () {
    timing($this->world, 'Regular', '2026-04-01', '2027-03-31', '08:00', '08:15');
    timing($this->world, 'Ramzan', '2027-02-01', '2027-03-02', '07:00', '07:10');

    expect(AttendanceTiming::inForce($this->campus->id, '2027-03-10', $this->session->id)?->name)
        ->toBe('Regular');
});

it('ignores a clock that has been switched off', function () {
    timing($this->world, 'Regular', '2026-04-01', '2027-03-31', '08:00', '08:15')
        ->update(['is_active' => false]);

    expect(AttendanceTiming::inForce($this->campus->id, '2026-04-06', $this->session->id))->toBeNull();
});

it('calls an arrival after the deadline late', function () {
    $regular = timing($this->world, 'Regular', '2026-04-01', '2027-03-31', '08:00', '08:15');

    expect($regular->isLate('08:30'))->toBeTrue()
        ->and($regular->isLate('08:10'))->toBeFalse()
        // On the deadline is not past it.
        ->and($regular->isLate('08:15'))->toBeFalse();
});

it('says how many minutes late', function () {
    $regular = timing($this->world, 'Regular', '2026-04-01', '2027-03-31', '08:00', '08:15');

    expect($regular->minutesLate('08:45'))->toBe(30)
        ->and($regular->minutesLate('08:00'))->toBe(0);
});

it('calls the same arrival late in one month and not in another', function () {
    $regular = timing($this->world, 'Regular', '2026-04-01', '2027-01-31', '08:00', '08:15');
    $ramzan = timing($this->world, 'Ramzan', '2027-02-01', '2027-03-02', '07:00', '07:10');

    // The whole reason this table exists.
    expect($regular->isLate('08:10'))->toBeFalse()
        ->and($ramzan->isLate('08:10'))->toBeTrue();
});

it('ignores an arrival that was never recorded', function () {
    $regular = timing($this->world, 'Regular', '2026-04-01', '2027-03-31', '08:00', '08:15');

    expect($regular->isLate(null))->toBeFalse()
        ->and($regular->minutesLate(null))->toBe(0);
});

it('keeps one campus s clock away from another', function () {
    timing($this->world, 'Regular', '2026-04-01', '2027-03-31', '08:00', '08:15');

    expect(AttendanceTiming::inForce($this->world->school->otherCampus->id, '2026-04-06', $this->session->id))
        ->toBeNull();
});
