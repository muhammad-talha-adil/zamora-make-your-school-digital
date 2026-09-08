<?php

/**
 * Case 05 — the school cannot take a register on a day it is closed.
 *
 * The bulk path checked; the individual path computed the same answer and threw
 * it away; the whole-class path never checked at all. And the endpoint the
 * screen asks called any holiday a holiday, ignoring the ones a school has
 * marked as working days — so the screen blocked a date the save would accept.
 */

use App\Models\Attendance;
use App\Models\Holiday;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
});

/** A holiday covering one day. */
function holidayOn(AttendanceWorld $world, string $date, bool $attendanceAllowed = false): Holiday
{
    return Holiday::create([
        'title' => 'Eid',
        'start_date' => $date,
        'end_date' => $date,
        'is_national' => true,
        'campus_id' => null,
        'is_attendance_allowed' => $attendanceAllowed,
    ]);
}

it('refuses a bulk save on a holiday', function () {
    holidayOn($this->world, '2026-04-06');

    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    expect(Attendance::count())->toBe(0);
});

it('says why it refused', function () {
    holidayOn($this->world, '2026-04-06');

    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'))
        ->assertSessionHas('error');
});

it('allows a working day', function () {
    holidayOn($this->world, '2026-04-06');

    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-07'))
        ->assertRedirect(route('attendance.index'));

    expect(Attendance::count())->toBe(1);
});

it('allows a holiday the school has marked as a working day', function () {
    // An exam day in the holidays, a make-up class.
    holidayOn($this->world, '2026-04-06', attendanceAllowed: true);

    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'))
        ->assertRedirect(route('attendance.index'));

    expect(Attendance::count())->toBe(1);
});

it('refuses a whole-class save on a holiday too', function () {
    holidayOn($this->world, '2026-04-06');

    // This path never checked at all.
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06', null, 0));

    expect(Attendance::count())->toBe(0);
});

it('tells the screen a holiday is a holiday', function () {
    holidayOn($this->world, '2026-04-06');

    $this->getJson(route('attendance.api.check-holiday', ['date' => '2026-04-06']))
        ->assertSuccessful()
        ->assertJson(['is_holiday' => true, 'attendance_allowed' => false]);
});

it('tells the screen a working holiday is workable', function () {
    holidayOn($this->world, '2026-04-06', attendanceAllowed: true);

    // The screen used to block this date while the save would have taken it.
    $this->getJson(route('attendance.api.check-holiday', ['date' => '2026-04-06']))
        ->assertJson(['is_holiday' => false, 'attendance_allowed' => true]);
});

it('tells the screen an ordinary day is workable', function () {
    $this->getJson(route('attendance.api.check-holiday', ['date' => '2026-04-07']))
        ->assertJson(['is_holiday' => false, 'attendance_allowed' => true]);
});

it('still lets an existing register be corrected on a holiday', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    // The day is declared a holiday afterwards; the register already taken is
    // still the school's record and must stay correctable.
    holidayOn($this->world, '2026-04-06');

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'))
        ->assertRedirect(route('attendance.index'));

    expect(Attendance::count())->toBe(1);
});
