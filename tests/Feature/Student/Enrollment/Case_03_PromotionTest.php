<?php

/**
 * Case 03 — moving a class up at the end of the year.
 *
 * `students.promote` has been seeded since the beginning and nothing has ever
 * used it. A school promotes a **section at a time**, with three outcomes that
 * are all ordinary here: promoted, detained, and promoted on condition.
 *
 * Whether the child passed is the exam module's answer. This records the
 * decision and opens next year's period.
 */

use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
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

    // This year's class, and the one after it.
    $this->world->class->update(['level' => 5]);
    $this->nextClass = SchoolClass::create([
        'name' => 'Class 6',
        'level' => 6,
        'is_active' => true,
    ]);

    $this->nextSession = Session::create([
        'name' => '2027-2028',
        'start_date' => '2027-04-01',
        'end_date' => '2028-03-31',
        'is_active' => true,
    ]);
});

it('opens next year in the next class', function () {
    $this->service->promote($this->student, $this->nextSession->id, null, null,
        StudentEnrollmentService::PROMOTED, '2027-04-01');

    $current = $this->student->fresh()->currentEnrollment;

    expect($current->session_id)->toBe($this->nextSession->id)
        ->and($current->class_id)->toBe($this->nextClass->id);
});

it('closes this year on the day the next one starts', function () {
    $this->service->promote($this->student, $this->nextSession->id, null, null,
        StudentEnrollmentService::PROMOTED, '2027-04-01');

    $first = StudentEnrollmentRecord::where('student_id', $this->student->id)
        ->orderBy('id')->firstOrFail();

    expect($first->leave_date->toDateString())->toBe('2027-04-01');
});

it('never leaves the child in two years at once', function () {
    $this->service->promote($this->student, $this->nextSession->id);

    expect(StudentEnrollmentRecord::where('student_id', $this->student->id)
        ->whereNull('leave_date')->count())->toBe(1);
});

it('keeps a detained child in the same class, in the new session', function () {
    $this->service->promote($this->student, $this->nextSession->id, null, null,
        StudentEnrollmentService::DETAINED);

    $current = $this->student->fresh()->currentEnrollment;

    // The session changed, so the period must — the fee, the register and the
    // exam roll all hang off it.
    expect($current->session_id)->toBe($this->nextSession->id)
        ->and($current->class_id)->toBe($this->world->class->id);
});

it('records the decision on the period', function () {
    $this->service->promote($this->student, $this->nextSession->id, null, null,
        StudentEnrollmentService::PROMOTED_ON_CONDITION);

    expect($this->student->fresh()->currentEnrollment->description)
        ->toBe('Promoted on condition.');
});

it('takes the class it is told, over the one it would guess', function () {
    $other = SchoolClass::create(['name' => 'Class 6 Boys', 'level' => 6, 'is_active' => true]);

    $this->service->promote($this->student, $this->nextSession->id, $other->id);

    expect($this->student->fresh()->currentEnrollment->class_id)->toBe($other->id);
});

it('says so plainly when there is no class after this one', function () {
    // Class 10 finishing: there is nothing to promote into, and that is the
    // right answer rather than a guess.
    $this->world->class->update(['level' => 99]);

    expect(fn () => $this->service->promote($this->student, $this->nextSession->id))
        ->toThrow(ValidationException::class);
});

it('asks which class when the levels have not been filled in', function () {
    $this->world->class->update(['level' => null]);

    expect(fn () => $this->service->promote($this->student, $this->nextSession->id))
        ->toThrow(ValidationException::class);

    // Named explicitly, it works without levels at all.
    $this->service->promote($this->student, $this->nextSession->id, $this->nextClass->id);

    expect($this->student->fresh()->currentEnrollment->class_id)->toBe($this->nextClass->id);
});

it('does not promote the same child twice', function () {
    $this->service->promote($this->student, $this->nextSession->id);
    $this->service->promote($this->student, $this->nextSession->id);

    // Running the sheet twice is what a school does when the screen hangs.
    expect(StudentEnrollmentRecord::where('student_id', $this->student->id)->count())->toBe(2);
});

it('undoes a promotion made by mistake', function () {
    $this->service->promote($this->student, $this->nextSession->id, null, null,
        StudentEnrollmentService::PROMOTED, '2027-04-01');

    $this->service->revertPromotion($this->student, $this->nextSession->id);

    $current = $this->student->fresh()->currentEnrollment;

    // Back where they were, with the old period open again.
    expect(StudentEnrollmentRecord::where('student_id', $this->student->id)->count())->toBe(1)
        ->and($current->class_id)->toBe($this->world->class->id)
        ->and($current->leave_date)->toBeNull();
});

it('runs a whole section from the screen', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-P2',
        'student_email' => 'second@student.test',
    ]))->assertSessionHasNoErrors();

    $students = Student::all();

    $this->postJson(route('students.promotion.run'), [
        'to_session_id' => $this->nextSession->id,
        'to_class_id' => $this->nextClass->id,
        'starting_on' => '2027-04-01',
        'students' => $students->map(fn ($s) => [
            'student_id' => $s->id,
            'outcome' => 'promoted',
        ])->all(),
    ])->assertSuccessful()->assertJsonPath('data.promoted', 2);

    expect(StudentEnrollmentRecord::where('session_id', $this->nextSession->id)->count())->toBe(2);
});

it('shows the office each child s year beside their name', function () {
    $this->getJson(route('students.promotion.preview', [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('data.suggested_class.id', $this->nextClass->id)
        ->assertJsonCount(1, 'data.students');
});

it('does not let a teacher promote a section that is not theirs', function () {
    // The real roles, because a fence tested against a stub is not a fence.
    $teacher = $this->world->withFullRoles()
        ->userWithRole('teacher', 'teacher.promote@school.test');

    $this->actingAs($teacher)
        ->postJson(route('students.promotion.run'), [
            'to_session_id' => $this->nextSession->id,
            'to_class_id' => $this->nextClass->id,
            'students' => [['student_id' => $this->student->id, 'outcome' => 'promoted']],
        ])
        ->assertForbidden();

    expect(StudentEnrollmentRecord::where('session_id', $this->nextSession->id)->count())->toBe(0);
});
