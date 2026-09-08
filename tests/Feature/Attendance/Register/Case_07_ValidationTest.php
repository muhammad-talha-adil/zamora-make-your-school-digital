<?php

/**
 * Case 07 — what the register form accepts and what it turns away.
 *
 * The rules sat inline in the controller, against this project's own
 * convention, and two of them were missing entirely: `section_id` was checked
 * for presence and nothing else, so any integer reached the insert and came
 * back as "Failed to record attendance: SQLSTATE…"; and the check-in and
 * check-out times were validated on every path except the one the marking
 * screen actually posts to.
 */

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
});

/** Posts a register, with the given fields replaced. */
function submitRegister(AttendanceWorld $world, array $overrides = [])
{
    return test()->post(route('attendance.store'), array_merge($world->payload('P'), $overrides));
}

/** The first student's row in a payload, with the given fields replaced. */
function rowFor(AttendanceWorld $world, array $overrides): array
{
    return [array_merge([
        'student_id' => $world->students[0]->id,
        'attendance_status_id' => $world->status('P')->id,
    ], $overrides)];
}

it('accepts a complete register', function () {
    submitRegister($this->world)->assertRedirect(route('attendance.index'));
});

it('requires a date', function () {
    submitRegister($this->world, ['attendance_date' => ''])->assertSessionHasErrors('attendance_date');
});

it('requires at least one student', function () {
    submitRegister($this->world, ['attendances' => []])->assertSessionHasErrors('attendances');
});

it('rejects a section that does not exist', function () {
    // This used to reach the insert and surface as a database error.
    submitRegister($this->world, ['section_id' => 999999])->assertSessionHasErrors('section_id');
});

it('rejects a section belonging to another class', function () {
    $otherClass = $this->world->school->classWithoutSections;

    submitRegister($this->world, ['class_id' => $otherClass->id])
        ->assertSessionHasErrors('section_id');
});

it('accepts zero, which means the whole class', function () {
    submitRegister($this->world, ['section_id' => 0])->assertSessionHasNoErrors();
});

it('rejects a status that does not exist', function () {
    submitRegister($this->world, [
        'attendances' => rowFor($this->world, ['attendance_status_id' => 999999]),
    ])->assertSessionHasErrors('attendances.0.attendance_status_id');
});

it('rejects a status that has been switched off', function () {
    $status = $this->world->status('P');
    $status->update(['is_active' => false]);

    // A status the school retired should not keep arriving on new registers.
    submitRegister($this->world, [
        'attendances' => rowFor($this->world, ['attendance_status_id' => $status->id]),
    ])->assertSessionHasErrors('attendances.0.attendance_status_id');
});

it('rejects a check-out before the check-in', function () {
    // Accepted on this path before: a child leaving before they arrived.
    submitRegister($this->world, [
        'attendances' => rowFor($this->world, ['check_in' => '10:00', 'check_out' => '08:00']),
    ])->assertSessionHasErrors('attendances.0.check_out');
});

it('rejects a check-out with no check-in', function () {
    submitRegister($this->world, [
        'attendances' => rowFor($this->world, ['check_out' => '14:00']),
    ])->assertSessionHasErrors('attendances.0.check_in');
});

it('accepts times in the right order', function () {
    submitRegister($this->world, [
        'attendances' => rowFor($this->world, ['check_in' => '08:00', 'check_out' => '14:00']),
    ])->assertSessionHasNoErrors();
});

it('rejects a time in the wrong format', function () {
    submitRegister($this->world, [
        'attendances' => rowFor($this->world, ['check_in' => '8 in the morning']),
    ])->assertSessionHasErrors('attendances.0.check_in');
});

it('rejects the same student twice on one register', function () {
    $row = [
        'student_id' => $this->students[0]->id,
        'attendance_status_id' => $this->world->status('P')->id,
    ];

    // The second would silently overwrite the first.
    submitRegister($this->world, ['attendances' => [$row, $row]])
        ->assertSessionHasErrors('attendances');
});

it('rejects remarks longer than the column holds', function () {
    submitRegister($this->world, [
        'attendances' => rowFor($this->world, ['remarks' => str_repeat('x', 501)]),
    ])->assertSessionHasErrors('attendances.0.remarks');
});

it('writes nothing when the submission is rejected', function () {
    submitRegister($this->world, ['section_id' => 999999]);

    expect(Attendance::count())->toBe(0)
        ->and(AttendanceStudent::count())->toBe(0);
});

it('stores a check-in as a plain time', function () {
    submitRegister($this->world, [
        'attendances' => rowFor($this->world, ['check_in' => '08:30']),
    ])->assertRedirect();

    // Cast as a datetime, a clock time gained an arbitrary date part and
    // Eloquent wrote a full Y-m-d H:i:s back into a TIME column.
    $stored = AttendanceStudent::where('student_id', $this->students[0]->id)->value('check_in');

    expect($stored)->toStartWith('08:30')
        ->and($stored)->not->toContain('-');
});
