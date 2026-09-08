<?php

/**
 * Case 04 — a child marked "on leave" is linked to the leave they applied for.
 *
 * The lookup that found the leave status indexed a word-keyed array with a
 * code — every caller passes 'L', the keys are 'leave' — so it returned null
 * every single time and the detection behind it never once ran. Nothing ever
 * wrote `student_leave_id`, and the leave register and the attendance register
 * never met.
 */

use App\Models\AttendanceStudent;
use App\Models\LeaveType;
use App\Models\StudentLeave;
use App\Services\AttendanceService;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);

    $this->leaveType = LeaveType::create([
        'name' => 'Sick Leave',
        'description' => 'Illness',
        'is_active' => true,
    ]);
});

/** An approved leave covering the given dates. */
function approvedLeave(int $studentId, int $leaveTypeId, string $from, string $to): StudentLeave
{
    return StudentLeave::create([
        'student_id' => $studentId,
        'leave_type_id' => $leaveTypeId,
        'start_date' => $from,
        'end_date' => $to,
        'description' => 'Test',
        'status' => 'approved',
    ]);
}

it('looks a status up by its code', function () {
    $service = app(AttendanceService::class);

    // This returned null for every code before.
    expect($service->getStatusId('L'))->toBe($this->world->status('L')->id)
        ->and($service->getStatusId('P'))->toBe($this->world->status('P')->id)
        ->and($service->getStatusId('LT'))->toBe($this->world->status('LT')->id);
});

it('still looks a status up by name', function () {
    $service = app(AttendanceService::class);

    expect($service->getStatusId('leave'))->toBe($this->world->status('L')->id);
});

it('returns nothing for a code it does not know', function () {
    expect(app(AttendanceService::class)->getStatusId('ZZ'))->toBeNull();
});

it('recognises the leave status when the id arrives as a string', function () {
    $service = app(AttendanceService::class);
    $leaveId = $this->world->status('L')->id;

    // A form post delivers "3", not 3; a strict comparison was false in exactly
    // the cases that mattered.
    expect($service->isLeaveStatus((string) $leaveId))->toBeTrue()
        ->and($service->isLeaveStatus($leaveId))->toBeTrue()
        ->and($service->isLeaveStatus($this->world->status('P')->id))->toBeFalse();
});

it('links the approved leave when a child is marked on leave', function () {
    $leave = approvedLeave($this->students[0]->id, $this->leaveType->id, '2026-04-06', '2026-04-08');

    $this->post(route('attendance.store'), $this->world->payload('L', '2026-04-06'));

    $row = AttendanceStudent::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($row->student_leave_id)->toBe($leave->id);
});

it('leaves the link empty when the child is present', function () {
    approvedLeave($this->students[0]->id, $this->leaveType->id, '2026-04-06', '2026-04-08');

    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $row = AttendanceStudent::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($row->student_leave_id)->toBeNull();
});

it('ignores a leave that does not cover the day', function () {
    approvedLeave($this->students[0]->id, $this->leaveType->id, '2026-04-10', '2026-04-12');

    $this->post(route('attendance.store'), $this->world->payload('L', '2026-04-06'));

    $row = AttendanceStudent::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($row->student_leave_id)->toBeNull();
});

it('ignores a leave that is still awaiting approval', function () {
    $leave = approvedLeave($this->students[0]->id, $this->leaveType->id, '2026-04-06', '2026-04-08');
    $leave->update(['status' => 'pending']);

    $this->post(route('attendance.store'), $this->world->payload('L', '2026-04-06'));

    $row = AttendanceStudent::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($row->student_leave_id)->toBeNull();
});

it('links the leave when a register is edited to say leave', function () {
    $leave = approvedLeave($this->students[0]->id, $this->leaveType->id, '2026-04-06', '2026-04-08');

    $register = $this->world->register('2026-04-06');
    $row = $this->world->mark($register, $this->students[0], 'P');

    $this->put(route('attendance.update', $register), [
        'attendances' => [[
            'id' => $row->id,
            'student_id' => $this->students[0]->id,
            'attendance_status_id' => $this->world->status('L')->id,
        ]],
    ])->assertRedirect(route('attendance.index'));

    expect($row->fresh()->student_leave_id)->toBe($leave->id);
});

it('clears the link when the mark is changed back to present', function () {
    approvedLeave($this->students[0]->id, $this->leaveType->id, '2026-04-06', '2026-04-08');

    $this->post(route('attendance.store'), $this->world->payload('L', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $row = AttendanceStudent::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($row->student_leave_id)->toBeNull();
});
