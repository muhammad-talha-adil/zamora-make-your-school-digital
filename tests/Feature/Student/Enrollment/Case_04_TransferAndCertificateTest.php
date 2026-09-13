<?php

/**
 * Case 04 — moving children sideways, and the certificate they leave with.
 *
 * A section transfer is fifteen individual edits today, and each one should be
 * closing an enrolment period and opening another so that September's register
 * still says 9-A and October's says 9-B.
 *
 * The leaving certificate is the document a child cannot be admitted anywhere
 * else without. It reads the leave record that had never been written.
 */

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentStatus;
use App\Services\Student\StudentEnrollmentService;
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

/* ------------------------------------------------------------------ transfer */

it('moves a child into another section', function () {
    $this->service->transfer(
        [$this->student],
        $this->world->class->id,
        $this->world->otherSection->id,
        '2026-10-01'
    );

    expect($this->student->fresh()->currentEnrollment->section_id)
        ->toBe($this->world->otherSection->id);
});

it('keeps September in the old section and October in the new one', function () {
    $this->service->transfer(
        [$this->student], $this->world->class->id, $this->world->otherSection->id, '2026-10-01'
    );

    $periods = StudentEnrollmentRecord::where('student_id', $this->student->id)
        ->orderBy('id')->get();

    expect($periods)->toHaveCount(2)
        ->and($periods[0]->section_id)->toBe($this->world->section->id)
        ->and($periods[0]->leave_date->toDateString())->toBe('2026-10-01')
        ->and($periods[1]->admission_date->toDateString())->toBe('2026-10-01');
});

it('does not move a child who is already there', function () {
    $this->service->transfer(
        [$this->student], $this->world->class->id, $this->world->section->id
    );

    // Running the sheet twice must not lay down a second period.
    expect(StudentEnrollmentRecord::where('student_id', $this->student->id)->count())->toBe(1);
});

it('skips a child who is not on the roll', function () {
    $this->service->leave($this->student, null, $this->left->id);

    $moved = $this->service->transfer(
        [$this->student], $this->world->class->id, $this->world->otherSection->id
    );

    expect($moved)->toBe(0);
});

it('carries the fee into the new section', function () {
    $before = $this->student->currentEnrollment;

    $this->service->transfer(
        [$this->student], $this->world->class->id, $this->world->otherSection->id
    );

    expect((float) $this->student->fresh()->currentEnrollment->monthly_fee)
        ->toBe((float) $before->monthly_fee);
});

it('moves a whole group from the screen', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-T2',
        'student_email' => 'second.transfer@student.test',
    ]))->assertSessionHasNoErrors();

    $this->postJson(route('students.transfer'), [
        'student_ids' => Student::pluck('id')->all(),
        'class_id' => $this->world->class->id,
        'section_id' => $this->world->otherSection->id,
        'effective_from' => '2026-10-01',
    ])->assertSuccessful()->assertJsonPath('data.moved', 2);

    expect(StudentEnrollmentRecord::where('section_id', $this->world->otherSection->id)
        ->whereNull('leave_date')->count())->toBe(2);
});

/* --------------------------------------------------------------- certificate */

it('prints a leaving certificate', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id, 'Moved to Lahore.');

    $this->get(route('students.leaving-certificate', $this->student->id))
        ->assertSuccessful()
        ->assertSee('School Leaving Certificate', false)
        ->assertSee('Moved to Lahore.', false);
});

it('prints the dates the receiving school asks for', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id, 'Moved to Lahore.');

    $page = $this->get(route('students.leaving-certificate', $this->student->id))->getContent();

    expect($page)->toContain('01 Apr 2026')
        ->and($page)->toContain('15 Jun 2026')
        // How long they were here, which is the line read first.
        ->and($page)->toContain('2 months');
});

it('refuses a certificate for a child who has not left', function () {
    // Certifying a child who is still sitting in the class is the one thing it
    // must never do.
    $this->get(route('students.leaving-certificate', $this->student->id))
        ->assertStatus(302);
});

it('names the class the child last sat in', function () {
    $this->service->leave($this->student, '2026-06-15', $this->left->id);

    $this->get(route('students.leaving-certificate', $this->student->id))
        ->assertSee($this->world->class->name, false);
});
