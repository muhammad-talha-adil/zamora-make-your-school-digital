<?php

/**
 * Case 05 — billing a child in a class that has no sections.
 *
 * A class does not have to be split into sections, so the enrollment's
 * `section_id` is nullable. `fee_vouchers.section_id` was not, and the voucher
 * copies the enrollment's value straight across — so these students hit an
 * integrity violation. The fee run catches each student's error and only logs
 * it, so nobody noticed: the child was simply never billed, month after month.
 */

use App\Models\Fee\FeeVoucher;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Services\Fee\VoucherGenerationService;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->school = $this->world->school;
});

/**
 * A second child, enrolled into a class that has no sections at all.
 */
function enrolWithoutSection(FeeWorld $world): StudentEnrollmentRecord
{
    $student = Student::create([
        'user_id' => $world->school->actor->id,
        'registration_no' => 'REG-NOSEC-1',
        'student_code' => 'STU-000002',
        'admission_no' => 'ADM-NOSEC-1',
        'dob' => now()->subYears(9)->toDateString(),
        'gender_id' => $world->school->maleGender->id,
        'student_status_id' => $world->school->activeStatus->id,
        'admission_date' => '2026-04-01',
    ]);

    return StudentEnrollmentRecord::create([
        'student_id' => $student->id,
        'session_id' => $world->school->session->id,
        'class_id' => $world->school->classWithoutSections->id,
        'section_id' => null,
        'campus_id' => $world->school->campus->id,
        'admission_date' => '2026-04-01',
        'student_status_id' => $world->school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);
}

/** A structure covering the section-less class. */
function structureForSectionlessClass(FeeWorld $world): void
{
    $structure = $world->school->feeStructure([
        'title' => 'Sectionless Class Standard',
        'class_id' => $world->school->classWithoutSections->id,
        'section_id' => null,
        'effective_from' => '2026-04-01',
        'effective_to' => '2027-03-31',
    ]);

    $structure->items()->where('fee_head_id', $world->school->monthlyHead->id)->update(['amount' => 4000]);
}

it('bills a student who has no section', function () {
    $enrollment = enrolWithoutSection($this->world);
    structureForSectionlessClass($this->world);

    $result = app(VoucherGenerationService::class)
        ->generateMonthlyVouchers(4, 2026, ['class_id' => $this->school->classWithoutSections->id]);

    expect($result['errors'])->toBe([])
        ->and($result['generated'])->toBe(1);

    $voucher = FeeVoucher::where('student_enrollment_record_id', $enrollment->id)->first();

    expect($voucher)->not->toBeNull()
        ->and($voucher->section_id)->toBeNull();
});

it('charges the right amount to a student with no section', function () {
    $enrollment = enrolWithoutSection($this->world);
    structureForSectionlessClass($this->world);

    app(VoucherGenerationService::class)
        ->generateMonthlyVouchers(4, 2026, ['class_id' => $this->school->classWithoutSections->id]);

    $voucher = FeeVoucher::where('student_enrollment_record_id', $enrollment->id)->firstOrFail();
    $tuition = $voucher->items()->where('fee_head_id', $this->school->monthlyHead->id)->firstOrFail();

    expect((float) $tuition->amount)->toBe(4000.0);
});

it('does not skip the student in a whole-campus fee run', function () {
    enrolWithoutSection($this->world);
    structureForSectionlessClass($this->world);
    $this->world->structureWithAllFrequencies(['monthly' => 5000]);

    // Both children are billed: the one with a section and the one without.
    $result = app(VoucherGenerationService::class)
        ->generateMonthlyVouchers(4, 2026, ['campus_id' => $this->school->campus->id]);

    expect($result['errors'])->toBe([])
        ->and($result['generated'])->toBe(2);
});
