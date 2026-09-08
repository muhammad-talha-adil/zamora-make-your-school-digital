<?php

/**
 * Case 01 — registering a class for an exam.
 *
 * This had never once worked. The query asked for `status = 'active'` and
 * `student_enrollment_records` has no `status` column at all, so every call
 * threw `Unknown column`.
 *
 * With that fixed, the next question is *which* roll — and the answer is the
 * roll as it stood when the exam was sat, not as it stands today. A child who
 * has since moved section is registered where they were; one who has since left
 * is still registered for the exam they actually sat.
 */

use App\Models\Exam\ExamStudentRegistration;
use App\Models\StudentEnrollmentRecord;
use App\Services\Exam\ExamRegistrationService;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(3);
    $this->service = app(ExamRegistrationService::class);
});

/** Registers the shared class for the exam. */
function registerClass(ExamWorld $world, ?int $sectionId = null): int
{
    return app(ExamRegistrationService::class)->generateFromEnrollments(
        $world->exam->id,
        $world->school->class->id,
        $sectionId
    );
}

it('registers a class without throwing', function () {
    // It threw `Unknown column 'status'` on every call before.
    expect(registerClass($this->world))->toBe(3);
});

it('registers each child once', function () {
    registerClass($this->world);

    expect(ExamStudentRegistration::where('exam_id', $this->world->exam->id)->count())->toBe(3);
});

it('does not register the same child twice', function () {
    registerClass($this->world);
    $second = registerClass($this->world);

    expect($second)->toBe(0)
        ->and(ExamStudentRegistration::count())->toBe(3);
});

it('records where the child was', function () {
    registerClass($this->world);

    $registration = ExamStudentRegistration::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($registration->class_id)->toBe($this->world->school->class->id)
        ->and($registration->section_id)->toBe($this->world->school->section->id)
        ->and($registration->campus_id)->toBe($this->world->school->campus->id);
});

it('narrows to one section when asked', function () {
    $other = $this->world->school->otherSection;

    StudentEnrollmentRecord::where('student_id', $this->students[2]->id)
        ->update(['section_id' => $other->id]);

    expect(registerClass($this->world, $this->world->school->section->id))->toBe(2);
});

it('registers a child who has since left', function () {
    // The exam ran 5–15 October; they left in December.
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-12-31']);

    registerClass($this->world);

    expect(ExamStudentRegistration::where('student_id', $this->students[0]->id)->exists())
        ->toBeTrue();
});

it('leaves out a child who had already gone', function () {
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-08-31']);

    registerClass($this->world);

    expect(ExamStudentRegistration::where('student_id', $this->students[0]->id)->exists())
        ->toBeFalse();
});

it('leaves out a child admitted after the exam', function () {
    $this->students[0]->currentEnrollment->update(['admission_date' => '2027-01-05']);

    registerClass($this->world);

    expect(ExamStudentRegistration::where('student_id', $this->students[0]->id)->exists())
        ->toBeFalse();
});

it('registers a moved child under the section they sat in', function () {
    $enrollment = $this->students[0]->currentEnrollment;

    // Sat the exam in the first section, moved in November.
    $enrollment->update(['leave_date' => '2026-11-01']);

    StudentEnrollmentRecord::create([
        'student_id' => $this->students[0]->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->otherSection->id,
        'campus_id' => $this->world->school->campus->id,
        'admission_date' => '2026-11-01',
        'student_status_id' => $this->world->school->activeStatus->id,
        'previous_enrollment_id' => $enrollment->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);

    registerClass($this->world, $this->world->school->section->id);

    $registration = ExamStudentRegistration::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($registration->section_id)->toBe($this->world->school->section->id);
});

it('falls back to today s roll for an exam with no dates', function () {
    $this->world->exam->update(['start_date' => null, 'end_date' => null]);

    // Nothing better to go on than who is on the roll now.
    expect(registerClass($this->world))->toBe(3);
});
