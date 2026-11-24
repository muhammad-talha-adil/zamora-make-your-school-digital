<?php

/**
 * Case 13 - a register cannot be taken for a day that has not happened, and
 * the Attendance List shows the same Present/Absent counts as the detail page.
 */

use App\Models\Attendance;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(3);
});

it('rejects a future date with a friendly message', function () {
    $tomorrow = now()->addDay()->toDateString();

    $this->post(route('attendance.store'), $this->world->payload('P', $tomorrow))
        ->assertSessionHasErrors(['attendance_date' => 'Attendance cannot be marked for a future date.']);

    expect(Attendance::count())->toBe(0);
});

it('still accepts today', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', now()->toDateString()))
        ->assertSessionHasNoErrors();
});

it('sends present and absent counts with each register in the list', function () {
    $register = $this->world->register();
    $this->world->mark($register, $this->students[0], 'P');
    $this->world->mark($register, $this->students[1], 'P');
    $this->world->mark($register, $this->students[2], 'A');

    $this->get(route('attendance.index'))
        ->assertInertia(fn ($page) => $page
            ->where('attendances.data.0.present_count', 2)
            ->where('attendances.data.0.absent_count', 1));
});

it('names the student on the register detail page', function () {
    $register = $this->world->register();
    $this->world->mark($register, $this->students[0], 'P');

    $this->get(route('attendance.show', $register))
        ->assertInertia(fn ($page) => $page
            ->where('attendanceStudents.0.student.name', $this->students[0]->name));
});
