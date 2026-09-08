<?php

/**
 * Case 07 — the father or guardian recorded against the child.
 *
 * Siblings share a guardian, so an admission can either create one or link to
 * an existing record. A CNIC or phone entered twice would split one family
 * across two guardian rows, which is what these cases guard against.
 */

use App\Models\Guardian;
use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('creates a guardian with the details entered', function () {
    $this->post(route('students.store'), $this->world->payload([
        'father_name' => 'Muhammad Ali',
        'father_cnic' => '35202-7654321-9',
        'father_phone' => '03211234567',
        'father_occupation' => 'Engineer',
        'father_address' => 'Model Town, Lahore',
    ]))->assertSessionHasNoErrors();

    $guardian = Guardian::firstOrFail();

    expect($guardian->cnic)->toBe('35202-7654321-9')
        ->and($guardian->phone)->toBe('03211234567')
        ->and($guardian->occupation)->toBe('Engineer')
        ->and($guardian->user->name)->toBe('Muhammad Ali');
});

it('links an existing guardian instead of creating a second one', function () {
    $existing = $this->world->existingGuardian();

    $payload = $this->world->payload(['guardian_id' => $existing->id]);
    unset($payload['father_name'], $payload['father_cnic']);

    $this->post(route('students.store'), $payload)->assertSessionHasNoErrors();

    $student = Student::with('guardians')->firstOrFail();

    expect(Guardian::count())->toBe(1)
        ->and($student->guardians->first()->id)->toBe($existing->id);
});

it('lets two siblings share one guardian', function () {
    $existing = $this->world->existingGuardian();

    foreach (['ADM-7001', 'ADM-7002'] as $admissionNo) {
        $payload = $this->world->payload([
            'admission_no' => $admissionNo,
            'guardian_id' => $existing->id,
        ]);
        unset($payload['father_name'], $payload['father_cnic']);

        $this->post(route('students.store'), $payload)->assertSessionHasNoErrors();
    }

    expect(Student::count())->toBe(2)
        ->and(Guardian::count())->toBe(1)
        ->and($existing->students()->count())->toBe(2);
});

it('refuses a CNIC already held by another guardian', function () {
    $this->world->existingGuardian(cnic: '35201-1111111-1');

    $this->post(route('students.store'), $this->world->payload([
        'father_cnic' => '35201-1111111-1',
    ]))->assertSessionHasErrors('father_cnic');

    expect(Student::count())->toBe(0);
});

it('allows the duplicate CNIC check to be skipped when linking that guardian', function () {
    $existing = $this->world->existingGuardian(cnic: '35201-2222222-2');

    $this->post(route('students.store'), $this->world->payload([
        'guardian_id' => $existing->id,
        'father_cnic' => '35201-2222222-2',
    ]))->assertSessionHasNoErrors();

    expect(Guardian::count())->toBe(1);
});

it('rejects a badly formatted father CNIC', function (string $cnic) {
    $this->post(route('students.store'), $this->world->payload(['father_cnic' => $cnic]))
        ->assertSessionHasErrors('father_cnic');
})->with([
    'no dashes' => '3520176543219',
    'letters' => '35202-ABCDEFG-9',
    'too short' => '3520-7654321-9',
]);

it('rejects a phone number containing letters', function () {
    $this->post(route('students.store'), $this->world->payload([
        'father_phone' => '0321-ABC-4567',
    ]))->assertSessionHasErrors('father_phone');
});

it('refuses the same phone number for both guardians', function () {
    $this->post(route('students.store'), $this->world->payload([
        'father_phone' => '03211234567',
        'other_name' => 'Uncle Bilal',
        'other_relation_id' => $this->world->motherRelation->id,
        'other_phone' => '0321-123-4567',
    ]))->assertSessionHasErrors('other_phone');

    expect(Student::count())->toBe(0);
});

it('accepts a second guardian with a different phone', function () {
    $this->post(route('students.store'), $this->world->payload([
        'father_phone' => '03211234567',
        'other_name' => 'Uncle Bilal',
        'other_relation_id' => $this->world->motherRelation->id,
        'other_phone' => '03009876543',
    ]))->assertSessionHasNoErrors();

    $student = Student::with('guardians')->firstOrFail();

    expect($student->guardians)->toHaveCount(2);
});

it('rejects a guardian id that does not exist', function () {
    $this->post(route('students.store'), $this->world->payload(['guardian_id' => 999999]))
        ->assertSessionHasErrors('guardian_id');
});

it('refuses a CNIC already used, given for the second guardian', function () {
    $this->world->existingGuardian(cnic: '35201-3333333-3');

    $this->post(route('students.store'), $this->world->payload([
        'other_name' => 'Uncle Bilal',
        'other_relation_id' => $this->world->motherRelation->id,
        'other_cnic' => '35201-3333333-3',
    ]))->assertSessionHasErrors('other_cnic');
});

it('still requires the relationship when linking an existing guardian', function () {
    $existing = $this->world->existingGuardian();

    $payload = $this->world->payload(['guardian_id' => $existing->id]);
    unset($payload['father_name'], $payload['father_cnic'], $payload['father_relation_id']);

    // The link row records how the guardian relates to this child, so the
    // relationship is needed whether the guardian is new or existing.
    $this->post(route('students.store'), $payload)
        ->assertSessionHasErrors('father_relation_id');

    expect(Student::count())->toBe(0);
});
