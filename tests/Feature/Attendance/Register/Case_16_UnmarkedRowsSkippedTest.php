<?php

/**
 * Case 16 — students left on "Select" are skipped; marked ones still save.
 */

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use App\Models\Section;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(3);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
});

it('saves marked rows and skips rows left on Select', function () {
    $payload = test()->world->payload('P');
    $payload['attendances'][1]['attendance_status_id'] = 0;
    $payload['attendances'][2]['attendance_status_id'] = '';

    test()->post(route('attendance.store'), $payload)->assertSessionHasNoErrors();

    expect(AttendanceStudent::count())->toBe(1)
        ->and(AttendanceStudent::first()->student_id)->toBe(test()->students[0]->id);
});

it('rejects a submission where nothing is marked', function () {
    $payload = test()->world->payload('P');
    foreach ($payload['attendances'] as $i => $row) {
        $payload['attendances'][$i]['attendance_status_id'] = 0;
    }

    test()->post(route('attendance.store'), $payload)->assertSessionHasErrors('attendances');

    expect(Attendance::count())->toBe(0);
});

it('shows the class report placement from the open enrolment, matching the register', function () {
    $school = test()->world->school;
    $oldSection = Section::create(['class_id' => $school->class->id, 'name' => 'Old B', 'code' => 'OB', 'is_active' => true]);
    $student = test()->students[0];

    StudentEnrollmentRecord::create([
        'student_id' => $student->id,
        'session_id' => $school->session->id,
        'class_id' => $school->class->id,
        'section_id' => $oldSection->id,
        'campus_id' => $school->campus->id,
        'admission_date' => '2026-04-02',
        'leave_date' => '2026-04-05',
        'student_status_id' => $school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);

    $props = test()->get(route('attendance.class-report', [
        'class_id' => $school->class->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertSuccessful()->inertiaProps();

    $row = collect($props['summary'])->firstWhere('registration_no', $student->registration_no);

    expect($row['enrollment_info'])->toContain($school->section->name)
        ->not->toContain('Old B');
});
