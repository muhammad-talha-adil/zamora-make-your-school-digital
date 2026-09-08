<?php

/**
 * Case 09 — the holiday cache, and counting a register.
 *
 * Saving a holiday used to call `Cache::flush()` in a loop, throwing away the
 * whole application cache — permissions, theme, everything — for every campus
 * and every signed-in user. And each of a register's counts ran its own
 * `whereHas` count, so listing registers cost three queries a row.
 */

use App\Models\Attendance;
use App\Models\Holiday;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(4);
    $this->service = app(AttendanceService::class);
});

it('keeps the rest of the cache when a holiday is saved', function () {
    Cache::forever('something:else', 'still here');

    Holiday::create([
        'title' => 'Eid',
        'start_date' => '2026-04-06',
        'end_date' => '2026-04-06',
        'is_national' => true,
        'is_attendance_allowed' => false,
    ]);

    // The permission cache, the theme palette and everything else used to go
    // with it.
    expect(Cache::get('something:else'))->toBe('still here');
});

it('still notices a holiday that was just created', function () {
    // Asked before the holiday exists, so the answer is cached as "no".
    expect($this->service->isAttendanceAllowed('2026-04-06', $this->world->school->campus->id))->toBeTrue();

    Holiday::create([
        'title' => 'Eid',
        'start_date' => '2026-04-06',
        'end_date' => '2026-04-06',
        'is_national' => true,
        'is_attendance_allowed' => false,
    ]);

    expect($this->service->isAttendanceAllowed('2026-04-06', $this->world->school->campus->id))->toBeFalse();
});

it('notices a holiday that was deleted', function () {
    $holiday = Holiday::create([
        'title' => 'Eid',
        'start_date' => '2026-04-06',
        'end_date' => '2026-04-06',
        'is_national' => true,
        'is_attendance_allowed' => false,
    ]);

    expect($this->service->isAttendanceAllowed('2026-04-06', null))->toBeFalse();

    $holiday->delete();

    expect($this->service->isAttendanceAllowed('2026-04-06', null))->toBeTrue();
});

it('notices a holiday that became a working day', function () {
    $holiday = Holiday::create([
        'title' => 'Exam day',
        'start_date' => '2026-04-06',
        'end_date' => '2026-04-06',
        'is_national' => true,
        'is_attendance_allowed' => false,
    ]);

    expect($this->service->isAttendanceAllowed('2026-04-06', null))->toBeFalse();

    $holiday->update(['is_attendance_allowed' => true]);

    expect($this->service->isAttendanceAllowed('2026-04-06', null))->toBeTrue();
});

it('counts a register in one query', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $register = Attendance::firstOrFail();

    DB::enableQueryLog();
    DB::flushQueryLog();

    $present = $register->present_count;
    $absent = $register->absent_count;
    $late = $register->late_count;
    $leave = $register->leave_count;

    // One grouped query for all four, where each used to run its own count.
    expect(DB::getQueryLog())->toHaveCount(1)
        ->and($present)->toBe(4)
        ->and($absent + $late + $leave)->toBe(0);

    DB::disableQueryLog();
});

it('counts without a query when the rows are already loaded', function () {
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    $register = Attendance::with('attendanceStudents.attendanceStatus')->firstOrFail();

    DB::enableQueryLog();
    DB::flushQueryLog();

    expect($register->absent_count)->toBe(4)
        ->and($register->total_students)->toBe(4)
        ->and(DB::getQueryLog())->toHaveCount(0);

    DB::disableQueryLog();
});

it('counts each status separately', function () {
    $world = $this->world;

    $this->post(route('attendance.store'), [
        'attendance_date' => '2026-04-06',
        'campus_id' => $world->school->campus->id,
        'session_id' => $world->school->session->id,
        'class_id' => $world->school->class->id,
        'section_id' => $world->school->section->id,
        'attendances' => [
            ['student_id' => $world->students[0]->id, 'attendance_status_id' => $world->status('P')->id],
            ['student_id' => $world->students[1]->id, 'attendance_status_id' => $world->status('A')->id],
            ['student_id' => $world->students[2]->id, 'attendance_status_id' => $world->status('LT')->id],
            ['student_id' => $world->students[3]->id, 'attendance_status_id' => $world->status('HD')->id],
        ],
    ])->assertRedirect();

    $register = Attendance::firstOrFail();

    expect($register->present_count)->toBe(1)
        ->and($register->absent_count)->toBe(1)
        ->and($register->late_count)->toBe(1)
        ->and($register->half_day_count)->toBe(1)
        ->and($register->total_students)->toBe(4);
});
