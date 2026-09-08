<?php

/**
 * Case 02 — one row per student per register.
 *
 * The migration declared a plain index under the name
 * `unique_student_attendance`, so the name promised a constraint the database
 * never had. Nothing stopped a child being marked present and absent on the
 * same day, and every count, report and percentage built on top of that was
 * quietly wrong.
 */

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use Illuminate\Database\QueryException;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
});

it('refuses a second row for the same student on the same register', function () {
    $register = $this->world->register();
    $this->world->mark($register, $this->students[0], 'P');

    // What two teachers saving at the same moment used to produce.
    expect(fn () => $this->world->mark($register, $this->students[0], 'A'))
        ->toThrow(QueryException::class);
});

it('allows the same student on two different days', function () {
    $monday = $this->world->register('2026-04-06');
    $tuesday = $this->world->register('2026-04-07');

    $this->world->mark($monday, $this->students[0], 'P');
    $this->world->mark($tuesday, $this->students[0], 'A');

    expect(AttendanceStudent::where('student_id', $this->students[0]->id)->count())->toBe(2);
});

it('allows two students on the same register', function () {
    $register = $this->world->register();

    $this->world->mark($register, $this->students[0], 'P');
    $this->world->mark($register, $this->students[1], 'A');

    expect(AttendanceStudent::where('attendance_id', $register->id)->count())->toBe(2);
});

it('changes the mark instead of adding a row when a class is saved twice', function () {
    $this->post(route('attendance.store'), $this->world->payload('P'));
    $this->post(route('attendance.store'), $this->world->payload('A'));

    expect(AttendanceStudent::count())->toBe(2)
        ->and(AttendanceStudent::where('attendance_status_id', $this->world->status('A')->id)->count())
        ->toBe(2);
});

it('keeps one register for a class and date however often it is saved', function () {
    $this->post(route('attendance.store'), $this->world->payload('P'));
    $this->post(route('attendance.store'), $this->world->payload('A'));
    $this->post(route('attendance.store'), $this->world->payload('L'));

    expect(Attendance::count())->toBe(1);
});

it('counts each student once after repeated saves', function () {
    foreach (['P', 'A', 'P', 'LT'] as $code) {
        $this->post(route('attendance.store'), $this->world->payload($code));
    }

    $register = Attendance::firstOrFail();

    // The count a report would read: two children, not eight rows.
    expect($register->attendanceStudents()->count())->toBe(2)
        ->and($register->present_count)->toBe(0)
        ->and($register->late_count)->toBe(2);
});
