<?php

/**
 * Case 02 — marks and the register agreeing with each other.
 *
 * `exam_student_registrations` was populated by the bulk routine and then never
 * consulted. Marks could be entered for a child nobody had registered, and a
 * result header appeared for them on the spot — so two tables disagreed about
 * who sat the exam, and "how many are still to be marked" had no answer.
 *
 * The rule is **implicit and recorded**, not implicit and invisible: a teacher
 * marking a child who was missed off the list is not stopped, but the register
 * is written. Two things are still refused — a child who was not on the roll
 * when the exam was sat, and one deliberately withdrawn from it.
 */

use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamStudentRegistration;
use App\Services\Exam\ExamRegistrationService;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->world->paper('Mathematics');
});

/** Posts a mark for the shared child. */
function markMaths(ExamWorld $world, $student, $marks = 80)
{
    return test()->postJson(
        route('exam.marking.save-row'),
        $world->marksPayload($student, ['Mathematics' => ['obtained' => $marks]])
    );
}

it('registers a child who was missed off the list', function () {
    markMaths($this->world, $this->students[0])->assertSuccessful();

    expect(ExamStudentRegistration::where('exam_id', $this->world->exam->id)
        ->where('student_id', $this->students[0]->id)->exists())->toBeTrue();
});

it('records where the child was, not where the paper is', function () {
    markMaths($this->world, $this->students[0]);

    $registration = ExamStudentRegistration::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($registration->class_id)->toBe($this->world->school->class->id)
        ->and($registration->section_id)->toBe($this->world->school->section->id)
        ->and($registration->campus_id)->toBe($this->world->school->campus->id)
        ->and($registration->enrollment_id)->toBe($this->students[0]->currentEnrollment->id);
});

it('does not register the child twice when a mark is corrected', function () {
    markMaths($this->world, $this->students[0], 80);
    markMaths($this->world, $this->students[0], 60);

    expect(ExamStudentRegistration::where('exam_id', $this->world->exam->id)->count())->toBe(1);
});

it('keeps the registration made in bulk rather than making another', function () {
    app(ExamRegistrationService::class)->generateFromEnrollments(
        $this->world->exam->id, $this->world->school->class->id, null
    );

    $before = ExamStudentRegistration::where('student_id', $this->students[0]->id)->firstOrFail();

    markMaths($this->world, $this->students[0]);

    expect(ExamStudentRegistration::where('exam_id', $this->world->exam->id)->count())->toBe(1)
        ->and(ExamStudentRegistration::first()->id)->toBe($before->id);
});

it('refuses marks for a child who was not on the roll', function () {
    // Left in August; the exam ran in October.
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-08-31']);

    markMaths($this->world, $this->students[0])
        ->assertStatus(422)
        ->assertJsonValidationErrors('student_id');
});

it('writes no header for a child who was not on the roll', function () {
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-08-31']);

    markMaths($this->world, $this->students[0]);

    expect(ExamResultHeader::count())->toBe(0)
        ->and(ExamStudentRegistration::count())->toBe(0);
});

it('refuses marks for a child withdrawn from the exam', function () {
    markMaths($this->world, $this->students[0])->assertSuccessful();

    ExamStudentRegistration::where('student_id', $this->students[0]->id)
        ->update(['status' => 'withdrawn']);

    markMaths($this->world, $this->students[0], 95)
        ->assertStatus(422)
        ->assertJsonValidationErrors('student_id');
});

it('leaves the earlier mark alone when a withdrawn child is marked', function () {
    markMaths($this->world, $this->students[0], 80);

    ExamStudentRegistration::where('student_id', $this->students[0]->id)
        ->update(['status' => 'withdrawn']);

    markMaths($this->world, $this->students[0], 95);

    expect((float) ExamResultHeader::first()->total_obtained_cache)->toBe(80.0);
});

it('makes the registered count and the marked count agree', function () {
    $this->world->enrol(2);

    foreach ($this->world->students as $student) {
        markMaths($this->world, $student);
    }

    expect(ExamStudentRegistration::where('exam_id', $this->world->exam->id)->count())
        ->toBe(ExamResultHeader::where('exam_id', $this->world->exam->id)->count());
});
