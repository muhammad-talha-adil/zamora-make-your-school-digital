<?php

/**
 * Case 14 - every way of writing attendance refuses a day that has not happened
 * yet, with the same friendly message, and the marking screen never loads a
 * roster for one.
 */

use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\Staff\StaffAttendance;
use Database\Seeders\AttendanceStatusesSeeder;
use Tests\Support\AttendanceWorld;
use Tests\Support\StaffWorld;

const FUTURE_DATE_MESSAGE = 'Attendance cannot be marked for a future date.';

it('refuses a future date on the class register save', function () {
    $world = AttendanceWorld::make();
    $this->actingAs($world->school->actor);
    $world->enrol(2);

    $this->post(route('attendance.store'), $world->payload('P', now()->addDays(35)->toDateString()))
        ->assertSessionHasErrors(['attendance_date' => FUTURE_DATE_MESSAGE]);

    expect(Attendance::count())->toBe(0);
});

it('refuses to load the roster for a future date through the api', function () {
    $world = AttendanceWorld::make();
    $this->actingAs($world->school->actor);

    $this->getJson(route('attendance.api.students', [
        'class_id' => $world->school->class->id,
        'date' => now()->addDays(35)->toDateString(),
    ]))->assertUnprocessable()->assertJsonValidationErrors(['date' => FUTURE_DATE_MESSAGE]);
});

it('sends no students to the marking screen for a future date', function () {
    $world = AttendanceWorld::make();
    $this->actingAs($world->school->actor);
    $world->enrol(2);

    $this->get(route('attendance.create', [
        'class_id' => $world->school->class->id,
        'session_id' => $world->school->session->id,
        'date' => now()->addDays(35)->toDateString(),
    ]))->assertInertia(fn ($page) => $page->where('students', []));
});

it('refuses correcting a register that sits on a future date', function () {
    $world = AttendanceWorld::make();
    $this->actingAs($world->school->actor);
    $student = $world->enrol(1)[0];
    $register = $world->register(now()->addDays(35)->toDateString());
    $row = $world->mark($register, $student, 'P');

    $this->put(route('attendance.update', $register), [
        'attendances' => [[
            'id' => $row->id,
            'student_id' => $student->id,
            'attendance_status_id' => $world->status('A')->id,
        ]],
    ])->assertSessionHasErrors(['attendance_date' => FUTURE_DATE_MESSAGE]);
});

it('refuses a future date on every staff attendance path', function () {
    $world = StaffWorld::make();
    (new AttendanceStatusesSeeder)->run();
    $person = $world->person('Zainab Ali');
    $present = AttendanceStatus::where('code', 'P')->firstOrFail();
    $future = now()->addDays(35)->toDateString();
    $actor = $world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.attendance.store', $person), [
        'attendance_date' => $future,
        'attendance_status_id' => $present->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['attendance_date' => FUTURE_DATE_MESSAGE]);

    $this->actingAs($actor)->postJson(route('staff.attendance.bulk'), [
        'attendance_date' => $future,
        'rows' => [['staff_profile_id' => $person->id, 'attendance_status_id' => $present->id]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['attendance_date' => FUTURE_DATE_MESSAGE]);

    $this->actingAs($actor)->postJson(route('staff.attendance.lock'), [
        'attendance_date' => $future,
    ])->assertUnprocessable()->assertJsonValidationErrors(['attendance_date' => FUTURE_DATE_MESSAGE]);

    expect(StaffAttendance::count())->toBe(0);
});
