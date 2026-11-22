<?php

/**
 * Case 07 - round 01i browser QA fixes.
 */

use App\Models\Fee\FeeVoucher;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Services\Fee\VoucherGenerationService;
use App\Services\StudentService;
use Tests\Support\AdmissionWorld;
use Tests\Support\FeeWorld;

it('rejects a student name longer than 100 characters', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);

    $this->post(route('students.store'), $world->payload(['name' => str_repeat('a', 224)]))
        ->assertSessionHasErrors(['name' => 'Name can be at most 100 characters.']);
});

it('rejects long guardian names', function (string $field) {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);

    $this->post(route('students.store'), $world->payload([$field => str_repeat('a', 101)]))
        ->assertSessionHasErrors($field);
})->with(['father_name', 'other_name']);

it('sends the saved section and guardian phone to the edit page', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);
    $this->post(route('students.store'), $world->payload(['father_phone' => '0311-1110066']))->assertSessionHasNoErrors();

    $student = Student::firstOrFail();
    $data = app(StudentService::class)->getEditData($student);

    expect($data['student']['section_id'])->toBe($world->section->id);
});

it('skips vouchers for months before the admission month and allows the admission month', function () {
    $world = FeeWorld::make();
    $this->actingAs($world->school->actor);
    $world->structureWithAllFrequencies();

    StudentEnrollmentRecord::query()->update(['admission_date' => '2026-11-22']);

    $service = app(VoucherGenerationService::class);
    $early = $service->generateMonthlyVouchers(7, 2026);

    expect($early['generated'])->toBe(0)
        ->and($early['before_admission'])->toHaveCount(1)
        ->and($early['before_admission'][0]['message'])->toBe('Student was admitted in November 2026. Vouchers before that are not allowed.')
        ->and(FeeVoucher::count())->toBe(0);

    expect($service->generateMonthlyVouchers(11, 2026)['generated'])->toBe(1);
});
