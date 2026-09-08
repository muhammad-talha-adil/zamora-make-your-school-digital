<?php

/**
 * Case 14 — the enrollment history a student accumulates over the years.
 *
 * `student_enrollment_records` is the only place that remembers where a child
 * sat and on what fee. One row is open at a time (`leave_date` null) and every
 * earlier row is closed history, chained by `previous_enrollment_id`.
 *
 * Five years on, "which class was this child in during 2024-25, and what was
 * their fee" has to be answerable — which means a class change must open a new
 * period rather than overwrite the current one.
 */

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);

    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-HIST-1',
        'monthly_fee' => 4000,
    ]))->assertSessionHasNoErrors();

    $this->student = Student::firstOrFail();
});

/** The payload the edit form posts back, with the current values pre-filled. */
function editPayload(AdmissionWorld $world, Student $student, array $overrides = []): array
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
        // Required on update even though the admission form treats it as
        // optional; see Case 15.
        'father_phone' => '03211234567',
    ], $overrides);
}

it('starts a student with exactly one open enrollment', function () {
    expect(StudentEnrollmentRecord::count())->toBe(1)
        ->and($this->student->currentEnrollment)->not->toBeNull()
        ->and($this->student->currentEnrollment->leave_date)->toBeNull();
});

it('opens a new period when the class changes', function () {
    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student, [
            'class_id' => $this->world->classWithoutSections->id,
            'section_id' => null,
        ])
    )->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::count())->toBe(2);
});

it('closes the previous period instead of overwriting it', function () {
    $before = $this->student->currentEnrollment;

    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student, [
            'class_id' => $this->world->classWithoutSections->id,
            'section_id' => null,
        ])
    )->assertSessionHasNoErrors();

    $closed = StudentEnrollmentRecord::find($before->id);

    // The old row still says Class 5, which is the point of keeping it.
    expect($closed->class_id)->toBe($this->world->class->id)
        ->and($closed->leave_date)->not->toBeNull();
});

it('chains the new period back to the one it replaced', function () {
    $before = $this->student->currentEnrollment;

    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student, [
            'class_id' => $this->world->classWithoutSections->id,
            'section_id' => null,
        ])
    )->assertSessionHasNoErrors();

    $open = $this->student->fresh()->currentEnrollment;

    expect($open->previous_enrollment_id)->toBe($before->id)
        ->and($open->class_id)->toBe($this->world->classWithoutSections->id);
});

it('keeps the fee the student was on in the closed period', function () {
    $before = $this->student->currentEnrollment;

    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student, [
            'class_id' => $this->world->classWithoutSections->id,
            'section_id' => null,
            'monthly_fee' => 6000,
        ])
    )->assertSessionHasNoErrors();

    $closed = StudentEnrollmentRecord::find($before->id);
    $open = $this->student->fresh()->currentEnrollment;

    expect((float) $closed->monthly_fee)->toBe(4000.0)
        ->and((float) $open->monthly_fee)->toBe(6000.0);
});

it('opens a new period when only the section changes', function () {
    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student, [
            'section_id' => $this->world->otherSection->id,
        ])
    )->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::count())->toBe(2);
});

it('opens a new period when the campus changes', function () {
    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student, [
            'campus_id' => $this->world->otherCampus->id,
        ])
    )->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::count())->toBe(2);
});

it('edits the current period in place when only the fee changes', function () {
    $before = $this->student->currentEnrollment;

    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student, ['monthly_fee' => 5500])
    )->assertSessionHasNoErrors();

    // Same placement, so this is a correction, not a move.
    expect(StudentEnrollmentRecord::count())->toBe(1)
        ->and((float) StudentEnrollmentRecord::find($before->id)->monthly_fee)->toBe(5500.0);
});

it('leaves exactly one open period after several moves', function () {
    foreach ([$this->world->otherSection->id, $this->world->section->id] as $sectionId) {
        $this->put(
            route('students.update', $this->student),
            editPayload($this->world, $this->student->fresh(), ['section_id' => $sectionId])
        )->assertSessionHasNoErrors();
    }

    $open = StudentEnrollmentRecord::whereNull('leave_date')->get();

    expect(StudentEnrollmentRecord::count())->toBe(3)
        ->and($open)->toHaveCount(1);
});

it('can answer which class the student was in on a past date', function () {
    // Admitted six months ago, moved up a class today.
    $admittedOn = now()->subMonths(6)->toDateString();

    $this->student->currentEnrollment->update(['admission_date' => $admittedOn]);

    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student->fresh(), [
            'class_id' => $this->world->classWithoutSections->id,
            'section_id' => null,
            'admission_date' => now()->toDateString(),
        ])
    )->assertSessionHasNoErrors();

    // The question a school asks years later: on this date, where were they?
    $asOf = now()->subMonths(3)->toDateString();

    $period = StudentEnrollmentRecord::where('student_id', $this->student->id)
        ->whereDate('admission_date', '<=', $asOf)
        ->where(fn ($q) => $q->whereNull('leave_date')->orWhereDate('leave_date', '>=', $asOf))
        ->first();

    expect($period)->not->toBeNull()
        ->and($period->class_id)->toBe($this->world->class->id);
});

it('records the two periods back to back, with no gap', function () {
    $admittedOn = now()->subMonths(6)->toDateString();
    $movedOn = now()->toDateString();

    $this->student->currentEnrollment->update(['admission_date' => $admittedOn]);

    $this->put(
        route('students.update', $this->student),
        editPayload($this->world, $this->student->fresh(), [
            'class_id' => $this->world->classWithoutSections->id,
            'section_id' => null,
            'admission_date' => $movedOn,
        ])
    )->assertSessionHasNoErrors();

    $periods = StudentEnrollmentRecord::where('student_id', $this->student->id)
        ->orderBy('id')
        ->get();

    // The first period ends on the day the second begins, so every date in the
    // year falls inside exactly one of them.
    expect($periods)->toHaveCount(2)
        ->and($periods[0]->leave_date->toDateString())->toBe($movedOn)
        ->and($periods[1]->admission_date->toDateString())->toBe($movedOn);
});
