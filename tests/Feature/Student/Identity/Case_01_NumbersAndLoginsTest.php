<?php

/**
 * Case 01 — the numbers that identify a child, and the login they are given.
 *
 * Three generators wrote the numbers three ways and all three raced: read then
 * write with nothing holding the gap, so two clerks admitting at the same
 * moment got the same number and the second admission was lost behind a 500.
 * One took a **string** maximum; one read the newest row rather than the
 * highest number and cut it with a blind `substr`.
 *
 * And every admission created a login whose password was generated, hashed,
 * and thrown away — so the account existed and nobody on earth could sign in
 * to it, which is the whole student portal.
 */

use App\Models\Student;
use App\Models\User;
use App\Repositories\StudentRepository;
use App\Services\Student\AdmissionCredentials;
use App\Services\StudentUserService;
use Illuminate\Support\Facades\Hash;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
    $this->repository = app(StudentRepository::class);
});

/* ------------------------------------------------------------------ numbers */

it('starts each sequence at one', function () {
    expect($this->repository->generateStudentCode())->toBe('STU-000001')
        ->and($this->repository->generateAdmissionNumber())->toBe('ADM-00001');
});

it('carries on from the highest number, not the newest row', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-00500']))
        ->assertSessionHasNoErrors();

    // A second child admitted with a *lower* number, so the newest row is not
    // the highest one.
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-00100',
        'student_email' => 'second@student.test',
    ]))->assertSessionHasNoErrors();

    expect($this->repository->generateAdmissionNumber())->toBe('ADM-00501');
});

it('reads the highest number even when the padding has grown', function () {
    Student::first()?->delete();

    // 'STU-1000000' is longer than 'STU-000999'. A string maximum picks the
    // wrong one; longest-first picks the right one.
    foreach (['STU-000999', 'STU-1000000'] as $index => $code) {
        Student::create([
            'registration_no' => 'REG-PAD-'.$index,
            'student_code' => $code,
            'admission_no' => 'ADM-PAD-'.$index,
            'dob' => '2014-01-01',
            'gender_id' => $this->world->maleGender->id,
            'student_status_id' => $this->world->activeStatus->id,
            'admission_date' => '2026-04-01',
        ]);
    }

    expect($this->repository->generateStudentCode())->toBe('STU-1000001');
});

it('does not restart the sequence because a school typed its own format', function () {
    Student::create([
        'registration_no' => 'REG-CUSTOM',
        'student_code' => 'STU-000004',
        // Their own format, not the generated one.
        'admission_no' => 'CITY/2026/A/17',
        'dob' => '2014-01-01',
        'gender_id' => $this->world->maleGender->id,
        'student_status_id' => $this->world->activeStatus->id,
        'admission_date' => '2026-04-01',
    ]);

    // Only rows carrying the prefix are read, so the odd one is left alone.
    expect($this->repository->generateAdmissionNumber())->toBe('ADM-00001')
        ->and($this->repository->generateStudentCode())->toBe('STU-000005');
});

it('never hands out a number a deleted child still holds', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-00042']))
        ->assertSessionHasNoErrors();

    $student = Student::firstOrFail();
    $code = $student->student_code;
    $this->delete(route('students.destroy', $student->id));

    // Their enrolment periods, vouchers and results are all still filed under
    // that number. Two children sharing one across the school's history is
    // worse than a gap in the sequence.
    expect($this->repository->generateStudentCode())->not->toBe($code);
});

it('will not let the edit form change an admission number at all', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-1001']))
        ->assertSessionHasNoErrors();
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-1002',
        'student_email' => 'second.edit@student.test',
    ]))->assertSessionHasNoErrors();

    $second = Student::where('admission_no', 'ADM-1002')->firstOrFail();

    // `prepareForValidation` pins the number to what the child already has, so
    // the field cannot collide — which is why the edit form needs no
    // uniqueness rule, and why adding one would be dead code that implies the
    // field is editable.
    $this->put(route('students.update', $second->id), $this->world->payload([
        'admission_no' => 'ADM-1001',
    ]))->assertSessionHasNoErrors();

    expect($second->fresh()->admission_no)->toBe('ADM-1002');
});

/* ------------------------------------------------------------------- logins */

it('hands back a password somebody can actually sign in with', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('new_logins');

    $logins = session('new_logins');
    $student = collect($logins)->firstWhere('for', 'student');

    expect($student)->not->toBeNull();

    // The point of the whole fix: the credentials work.
    $user = User::where('username', $student['username'])->firstOrFail();

    expect(Hash::check($student['password'], $user->password))->toBeTrue();
});

it('gives the guardian a login too', function () {
    $this->post(route('students.store'), $this->world->payload())->assertSessionHasNoErrors();

    expect(collect(session('new_logins'))->pluck('for'))->toContain('guardian');
});

it('holds the password nowhere after the page has had it', function () {
    $credentials = app(AdmissionCredentials::class);

    $credentials->record('student', 'Ahmed', 'STU-000001', 'secret123');

    expect($credentials->take())->toHaveCount(1)
        // Read once, by whatever is about to show them.
        ->and($credentials->all())->toBeEmpty();
});

it('does not build passwords out of rand()', function () {
    $passwords = collect(range(1, 40))
        ->map(fn () => app(StudentUserService::class)->generateSecurePassword());

    expect($passwords->unique())->toHaveCount(40)
        ->and($passwords->first())->toHaveLength(12);
});

it('leaves out the symbols a parent has to type off a printed slip', function () {
    $password = app(StudentUserService::class)->generateSecurePassword();

    // A password with `^` in it gets written down wrong, and the school then
    // resets it to something worse.
    expect($password)->toMatch('/^[A-Za-z0-9]+$/');
});
