<?php

/**
 * Case 15 — approved leave pre-fills the marking screen, and "All Sections"
 * lists every section's students.
 */

use App\Models\AttendanceStudent;
use App\Models\LeaveType;
use App\Models\StudentLeave;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->leave = StudentLeave::create([
        'student_id' => $this->students[0]->id,
        'leave_type_id' => LeaveType::create(['name' => 'Sick', 'description' => 'x', 'is_active' => true])->id,
        'start_date' => '2026-04-06',
        'end_date' => '2026-04-07',
        'description' => 'Test',
        'status' => 'approved',
    ]);
});

it('pre-fills Leave for a student with approved leave and lists all sections', function () {
    $this->get(route('attendance.create', [
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => '',
        'date' => '2026-04-06',
    ]))->assertInertia(function ($page) {
        $students = collect($page->toArray()['props']['students']);
        expect($students)->toHaveCount(2)
            ->and($students->firstWhere('id', $this->students[0]->id)['existing_attendance']['attendance_status_id'])
            ->toBe($this->world->status('L')->id)
            ->and($students->firstWhere('id', $this->students[1]->id)['existing_attendance'])->toBeNull();
    });
});

it('saves a row with no status as Leave when approved leave covers the date', function () {
    $payload = $this->world->payload('P', '2026-04-06');
    $payload['attendances'][0]['attendance_status_id'] = 0;

    $this->post(route('attendance.store'), $payload)->assertSessionHasNoErrors();

    expect(AttendanceStudent::where('student_id', $this->students[0]->id)->first()->attendance_status_id)
        ->toBe($this->world->status('L')->id);
});
