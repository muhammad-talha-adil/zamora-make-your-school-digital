<?php

/**
 * Case 01 — a child leaving the school.
 *
 * Two faults, both silent. The leaving date was **always today**, so a child
 * who left in June and was entered in the register in September left in
 * September — and `leave_date` is what the fee run, the attendance register and
 * the exam roll all read to decide who was here.
 *
 * And `student_leave_records`, the table built to hold why they went, had a
 * model and a relation and had never once been written to.
 */

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentLeaveRecord;
use App\Models\StudentStatus;
use App\Services\Student\StudentEnrollmentService;
use Illuminate\Validation\ValidationException;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
    $this->service = app(StudentEnrollmentService::class);

    $this->post(route('students.store'), $this->world->payload([
        'admission_date' => '2026-04-01',
    ]))->assertSessionHasNoErrors();

    $this->student = Student::firstOrFail();
    $this->left = StudentStatus::where('name', 'Left')->firstOrFail();
});

it('closes the enrolment period on the day the child actually left', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id, 'Moved to Lahore.');

    $period = StudentEnrollmentRecord::where('student_id', $this->student->id)->firstOrFail();

    // Not today. June, because that is when they went.
    expect($period->leave_date->toDateString())->toBe('2026-06-15');
});

it('writes the leave record that was never written', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id, 'Moved to Lahore.');

    $record = StudentLeaveRecord::where('student_id', $this->student->id)->firstOrFail();

    expect($record->leave_date->toDateString())->toBe('2026-06-15')
        ->and($record->description)->toBe('Moved to Lahore.')
        ->and($record->student_status_id)->toBe($this->left->id);
});

it('defaults the date to today when the school does not say', function () {
    $this->service->leave($this->student);

    expect(StudentLeaveRecord::first()->leave_date->toDateString())
        ->toBe(now()->toDateString());
});

it('refuses a leaving date before the child joined', function () {
    expect(fn () => $this->service->leave($this->student, '2026-01-01'))
        ->toThrow(ValidationException::class);

    expect(StudentLeaveRecord::count())->toBe(0);
});

it('refuses a leaving date in the future', function () {
    // `leave_date` is what "still on the roll" is read from, so a future date
    // would take a child off today's register for a day that has not come.
    expect(fn () => $this->service->leave($this->student, now()->addMonth()->toDateString()))
        ->toThrow(ValidationException::class);
});

it('takes the child off the roll', function () {
    $this->service->leave($this->student, null, $this->left->id, 'Fees.');

    expect($this->student->fresh()->currentEnrollment)->toBeNull()
        ->and($this->student->fresh()->student_status_id)->toBe($this->left->id);
});

it('turns the child s login off with them', function () {
    $this->service->leave($this->student, null, $this->left->id);

    expect((bool) $this->student->fresh()->user->is_active)->toBeFalse();
});

it('refuses to close a child who is not on the roll', function () {
    $this->service->leave($this->student, null, $this->left->id);

    expect(fn () => $this->service->leave($this->student))
        ->toThrow(ValidationException::class);
});

it('leaves no phantom period behind', function () {
    $this->service->leave($this->student, null, $this->left->id);

    // The old code created a row with no session, class or campus when it could
    // not find an open one.
    expect(StudentEnrollmentRecord::where('student_id', $this->student->id)->count())->toBe(1);
});

it('deleting a child takes them off the roll too', function () {
    // The soft delete used to leave the period open, so the child vanished from
    // the student list and stayed on the class roll — billed, expected in the
    // register, registered for the exam.
    $this->delete(route('students.destroy', $this->student->id));

    $period = StudentEnrollmentRecord::where('student_id', $this->student->id)->firstOrFail();

    expect($period->leave_date)->not->toBeNull()
        ->and(Student::withTrashed()->find($this->student->id)->deleted_at)->not->toBeNull();
});
