<?php

/**
 * Case 15 — editing an admitted student.
 *
 * The edit form saves the student, their placement and their guardian in one
 * request. Identifiers the system issued at admission are not part of that:
 * they appear on printed vouchers and result cards, so they stay fixed for the
 * life of the record.
 */

use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);

    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-EDIT-1',
        'name' => 'Ahmed Ali',
        'father_name' => 'Muhammad Ali',
        'father_phone' => '03211111111',
        'monthly_fee' => 4000,
    ]))->assertSessionHasNoErrors();

    $this->student = Student::with('user', 'currentEnrollment')->firstOrFail();
});

/** The payload the edit form posts back, with current values pre-filled. */
function edit(AdmissionWorld $world, Student $student, array $overrides = []): array
{
    $current = $student->currentEnrollment;

    return array_merge([
        'admission_no' => $student->admission_no,
        'name' => $student->user->name,
        'dob' => $student->dob->toDateString(),
        'gender_id' => $student->gender_id,
        'student_status_id' => $student->student_status_id,
        'campus_id' => $current->campus_id,
        'session_id' => $current->session_id,
        'class_id' => $current->class_id,
        'section_id' => $current->section_id,
        'father_name' => 'Muhammad Ali',
        'father_relation_id' => $world->fatherRelation->id,
        'father_phone' => '03211111111',
    ], $overrides);
}

it('saves a change to the student name', function () {
    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'name' => 'Ahmed Hassan',
    ]))->assertRedirect()->assertSessionHasNoErrors();

    expect($this->student->fresh()->user->name)->toBe('Ahmed Hassan');
});

it('saves a change to the date of birth', function () {
    $dob = now()->subYears(11)->toDateString();

    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'dob' => $dob,
    ]))->assertSessionHasNoErrors();

    expect($this->student->fresh()->dob->toDateString())->toBe($dob);
});

it('saves a change to the B-Form', function () {
    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'b_form' => '35201-9998887-6',
    ]))->assertSessionHasNoErrors();

    expect($this->student->fresh()->b_form)->toBe('35201-9998887-6');
});

it('saves a change to the guardian details', function () {
    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'father_name' => 'Muhammad Bilal',
        'father_phone' => '03219999999',
        'father_occupation' => 'Doctor',
    ]))->assertSessionHasNoErrors();

    $guardian = Guardian::with('user')->firstOrFail();

    expect($guardian->user->name)->toBe('Muhammad Bilal')
        ->and($guardian->phone)->toBe('03219999999')
        ->and($guardian->occupation)->toBe('Doctor');
});

it('corrects the guardian phone in place rather than creating a second record', function () {
    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'father_phone' => '03219999999',
    ]))->assertSessionHasNoErrors();

    // A corrected number must not split the family across two guardian rows,
    // or the sibling lookup — which matches on phone — finds the wrong one.
    expect(Guardian::count())->toBe(1)
        ->and(Guardian::firstOrFail()->phone)->toBe('03219999999');
});

it('re-links to an existing guardian when the phone already belongs to one', function () {
    $other = $this->world->existingGuardian(cnic: '35201-4444444-4', phone: '03007776665');

    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'father_phone' => '03007776665',
    ]))->assertSessionHasNoErrors();

    $student = $this->student->fresh()->load('guardians');

    expect(Guardian::count())->toBe(2)
        ->and($student->guardians->pluck('id'))->toContain($other->id);
});

it('saves a change to the monthly fee without moving the student', function () {
    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'monthly_fee' => 5000,
    ]))->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::count())->toBe(1)
        ->and((float) $this->student->fresh()->currentEnrollment->monthly_fee)->toBe(5000.0);
});

it('keeps the admission number when the form sends a different one', function () {
    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'admission_no' => 'ADM-TAMPERED',
    ]))->assertSessionHasNoErrors();

    expect($this->student->fresh()->admission_no)->toBe('ADM-EDIT-1')
        ->and(Student::where('admission_no', 'ADM-TAMPERED')->exists())->toBeFalse();
});

it('keeps the registration number and student code', function () {
    $before = $this->student;

    $this->put(route('students.update', $this->student), edit($this->world, $this->student, [
        'registration_no' => 'REG-TAMPERED',
        'student_code' => 'STU-TAMPERED',
        'name' => 'Ahmed Hassan',
    ]))->assertSessionHasNoErrors();

    $after = $this->student->fresh();

    expect($after->registration_no)->toBe($before->registration_no)
        ->and($after->student_code)->toBe($before->student_code);
});

it('does not let one student take another student admission number', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-EDIT-2']))
        ->assertSessionHasNoErrors();

    $second = Student::where('admission_no', 'ADM-EDIT-2')->firstOrFail();

    $this->put(route('students.update', $second), edit($this->world, $second, [
        'admission_no' => 'ADM-EDIT-1',
    ]))->assertSessionHasNoErrors();

    // Pinned to its own number rather than the one it asked for.
    expect($second->fresh()->admission_no)->toBe('ADM-EDIT-2');
});

it('requires the guardian phone on the edit form', function () {
    $payload = edit($this->world, $this->student);
    unset($payload['father_phone']);

    $this->put(route('students.update', $this->student), $payload)
        ->assertSessionHasErrors('father_phone');
});

it('requires the guardian phone at admission too', function () {
    $payload = $this->world->payload(['admission_no' => 'ADM-EDIT-3']);
    unset($payload['father_phone']);

    $this->post(route('students.store'), $payload)
        ->assertSessionHasErrors('father_phone');
});

it('does not require a phone when an existing guardian is linked', function () {
    $existing = $this->world->existingGuardian(phone: '03005554443');

    $payload = $this->world->payload([
        'admission_no' => 'ADM-EDIT-4',
        'guardian_id' => $existing->id,
    ]);
    unset($payload['father_name'], $payload['father_cnic'], $payload['father_phone']);

    $this->post(route('students.store'), $payload)->assertSessionHasNoErrors();

    expect(Student::where('admission_no', 'ADM-EDIT-4')->exists())->toBeTrue();
});

it('rejects an edit that would clear a required field', function (string $field) {
    $payload = edit($this->world, $this->student);
    unset($payload[$field]);

    $this->put(route('students.update', $this->student), $payload)
        ->assertSessionHasErrors($field);
})->with([
    'name' => 'name',
    'date of birth' => 'dob',
    'gender' => 'gender_id',
    'campus' => 'campus_id',
    'session' => 'session_id',
    'class' => 'class_id',
]);

it('shows the edit form with the student loaded', function () {
    $this->get(route('students.edit', $this->student))->assertOk();
});

it('refuses the edit form to a role that cannot update students', function () {
    $teacher = $this->world->withFullRoles()->userWithRole('teacher', 'teacher.edit@school.test');

    $this->actingAs($teacher)
        ->get(route('students.edit', $this->student))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->put(route('students.update', $this->student), edit($this->world, $this->student))
        ->assertForbidden();
});
