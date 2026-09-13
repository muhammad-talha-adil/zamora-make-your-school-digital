<?php

/**
 * Case 02 — bringing a child back.
 *
 * There were two routines for this and they disagreed. `handleReactivation()`
 * fell back to the last enrolment and threw where class or section were
 * missing; `readmit()` required them, wrote nulls where they were absent, and
 * chose its status by falling back from Active to **Left** and then to a
 * hard-coded row id — so a school that renamed its statuses re-admitted
 * children as having left.
 *
 * Neither closed the period that was still open, which the database refuses.
 */

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
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
    $this->active = StudentStatus::where('name', 'Active')->firstOrFail();
    $this->left = StudentStatus::where('name', 'Left')->firstOrFail();
});

it('brings a child back onto the roll', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id);

    $this->service->readmit($this->student, [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
        'section_id' => $this->world->section->id,
        'campus_id' => $this->world->campus->id,
        'admission_date' => '2026-08-01',
    ]);

    $current = $this->student->fresh()->currentEnrollment;

    expect($current)->not->toBeNull()
        ->and($current->admission_date->toDateString())->toBe('2026-08-01')
        ->and($current->class_id)->toBe($this->world->class->id);
});

it('sets the child active again, never "left"', function () {
    $this->service->leave($this->student, null, $this->left->id);
    $this->service->readmit($this->student, [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
    ]);

    // The old code fell back from Active to Left and then to a hard-coded 2.
    expect($this->student->fresh()->student_status_id)->toBe($this->active->id);
});

it('turns the login back on', function () {
    $this->service->leave($this->student, null, $this->left->id);
    $this->service->readmit($this->student, [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
    ]);

    expect((bool) $this->student->fresh()->user->is_active)->toBeTrue();
});

it('closes the open period first when nobody marked the child as left', function () {
    // The ordinary case: somebody forgot. This used to run straight into the
    // unique index and die with a 500.
    $this->service->readmit($this->student, [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
        'admission_date' => '2026-09-01',
    ]);

    $periods = StudentEnrollmentRecord::where('student_id', $this->student->id)
        ->orderBy('id')
        ->get();

    expect($periods)->toHaveCount(2)
        ->and($periods[0]->leave_date->toDateString())->toBe('2026-09-01')
        ->and($periods[1]->leave_date)->toBeNull();
});

it('never leaves a child in two classes at once', function () {
    $this->service->readmit($this->student, [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->classWithoutSections->id,
    ]);

    expect(StudentEnrollmentRecord::where('student_id', $this->student->id)
        ->whereNull('leave_date')->count())->toBe(1);
});

it('chains the new period to the one it followed', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id);
    $first = StudentEnrollmentRecord::where('student_id', $this->student->id)->firstOrFail();

    $this->service->readmit($this->student, [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
    ]);

    expect($this->student->fresh()->currentEnrollment->previous_enrollment_id)->toBe($first->id);
});

it('falls back to where the child was when the school does not say', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id);

    // Only the admission date is given.
    $this->service->readmit($this->student, ['admission_date' => '2026-08-01']);

    $current = $this->student->fresh()->currentEnrollment;

    expect($current->class_id)->toBe($this->world->class->id)
        ->and($current->section_id)->toBe($this->world->section->id)
        ->and($current->campus_id)->toBe($this->world->campus->id);
});

it('refuses a re-admission with nothing to go on', function () {
    // A child with no history at all and no class named.
    $orphan = Student::create([
        'registration_no' => 'REG-ORPHAN',
        'student_code' => 'STU-ORPHAN',
        'admission_no' => 'ADM-ORPHAN',
        'dob' => '2014-01-01',
        'gender_id' => $this->world->maleGender->id,
        'student_status_id' => $this->active->id,
        'admission_date' => '2026-04-01',
    ]);

    expect(fn () => $this->service->readmit($orphan, []))
        ->toThrow(ValidationException::class);
});

it('carries the fee across to the new period', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id);
    $before = StudentEnrollmentRecord::where('student_id', $this->student->id)->firstOrFail();

    $this->service->readmit($this->student, [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
        'monthly_fee' => $before->monthly_fee,
    ]);

    expect((float) $this->student->fresh()->currentEnrollment->monthly_fee)
        ->toBe((float) $before->monthly_fee);
});

it('goes through the same routine from the status screen', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id);

    // `changeStatus` with `is_reactivation` used to be a second, different
    // implementation. It is the same one now.
    $this->post(route('students.change-status', $this->student->id), [
        'is_reactivation' => true,
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
        'section_id' => $this->world->section->id,
        'campus_id' => $this->world->campus->id,
    ])->assertRedirect();

    expect($this->student->fresh()->currentEnrollment)->not->toBeNull()
        ->and($this->student->fresh()->student_status_id)->toBe($this->active->id);
});
