<?php

/**
 * Case 01 — a signed-off register cannot be changed.
 *
 * The lock existed and every screen honoured it, but the endpoint the marking
 * screen actually posts to did not: it authorised `create`, a class-level check
 * that never sees a record and so cannot see the lock either. Opening the same
 * class and date again silently overwrote a locked register.
 */

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->students = $this->world->enrol(3);

    /*
     * A teacher, not the developer: `Gate::before` grants developers every
     * ability without consulting a policy, so the lock would never be reached.
     * Given the whole class, since A13 scopes a teacher to what they are
     * responsible for and these cases are about the lock, not the scope.
     */
    $this->teacher = $this->world->staffUser('teacher', 'teacher.attendance@test.local');
    $this->world->assignTeacher($this->teacher, null);

    $this->actingAs($this->teacher);
});

it('records a register that does not exist yet', function () {
    $this->post(route('attendance.store'), $this->world->payload('P'))
        ->assertRedirect(route('attendance.index'));

    expect(AttendanceStudent::count())->toBe(3);
});

it('lets an unlocked register be marked again', function () {
    $this->post(route('attendance.store'), $this->world->payload('P'));
    $this->post(route('attendance.store'), $this->world->payload('A'))
        ->assertRedirect(route('attendance.index'));

    $absent = $this->world->status('A')->id;

    expect(AttendanceStudent::where('attendance_status_id', $absent)->count())->toBe(3);
});

it('refuses a bulk save onto a locked register', function () {
    $this->post(route('attendance.store'), $this->world->payload('P'));

    Attendance::query()->update(['is_locked' => true]);

    $this->post(route('attendance.store'), $this->world->payload('A'))
        ->assertForbidden();
});

it('leaves the marks untouched when the save is refused', function () {
    $this->post(route('attendance.store'), $this->world->payload('P'));
    $present = $this->world->status('P')->id;

    Attendance::query()->update(['is_locked' => true]);

    $this->post(route('attendance.store'), $this->world->payload('A'));

    // Every row still says present.
    expect(AttendanceStudent::where('attendance_status_id', $present)->count())->toBe(3)
        ->and(AttendanceStudent::count())->toBe(3);
});

it('lets the register be marked again once it is unlocked', function () {
    $this->post(route('attendance.store'), $this->world->payload('P'));

    $register = Attendance::firstOrFail();
    $register->lock();
    $register->unlock();

    $this->post(route('attendance.store'), $this->world->payload('A'))
        ->assertRedirect(route('attendance.index'));

    expect(AttendanceStudent::where('attendance_status_id', $this->world->status('A')->id)->count())
        ->toBe(3);
});

it('still refuses when only one section of a whole-class save is locked', function () {
    $otherSection = $this->world->school->otherSection;
    $this->world->enrol(1, $otherSection->id);

    // Mark the whole class, which files each child under their own section.
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06', null, 0));

    Attendance::where('section_id', $otherSection->id)->update(['is_locked' => true]);

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06', null, 0))
        ->assertForbidden();
});

it('does not lock out a register for a date that has not been marked', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    Attendance::query()->update(['is_locked' => true]);

    // A different day is a new register, not a change to the locked one.
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-07'))
        ->assertRedirect(route('attendance.index'));

    expect(Attendance::count())->toBe(2);
});

it('refuses to edit a locked register through the update endpoint', function () {
    $register = $this->world->register();
    $row = $this->world->mark($register, $this->students[0], 'P');
    $register->lock();

    $this->put(route('attendance.update', $register), [
        'attendances' => [[
            'id' => $row->id,
            'student_id' => $this->students[0]->id,
            'attendance_status_id' => $this->world->status('A')->id,
        ]],
    ])->assertForbidden();
});

it('refuses to delete a locked register', function () {
    $register = $this->world->register();
    $register->lock();

    $this->delete(route('attendance.destroy', $register))->assertForbidden();

    expect(Attendance::find($register->id))->not->toBeNull();
});
