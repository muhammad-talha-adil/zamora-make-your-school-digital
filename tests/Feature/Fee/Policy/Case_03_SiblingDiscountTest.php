<?php

/**
 * Case 03 — the sibling concession, worked out rather than typed in.
 *
 * Siblings are already known through their shared guardian, so "ten percent off
 * the second child" can be applied by the fee run. Entered by hand it was
 * forgotten whenever a family's circumstances changed — most often when the
 * eldest finished school and the younger children should have moved up a rank.
 */

use App\Enums\Fee\ApprovalStatus;
use App\Models\Fee\DiscountType;
use App\Models\Fee\FeePolicy;
use App\Models\Fee\FeeSiblingDiscountRule;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Fee\StudentDiscount;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000]);
    $this->guardian = $this->world->school->existingGuardian();

    // The child FeeWorld enrolled is the eldest of the family.
    $this->world->student->update(['dob' => '2014-01-01']);
    $this->world->student->guardians()->syncWithoutDetaching([
        $this->guardian->id => ['relation_id' => $this->world->school->fatherRelation->id],
    ]);

    FeePolicy::create([
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'sibling_discount_enabled' => true,
    ]);
});

/** Adds a younger brother or sister sharing the same guardian. */
function addSibling(FeeWorld $world, int $guardianId, string $dob, string $suffix): StudentEnrollmentRecord
{
    $student = Student::create([
        'user_id' => $world->school->actor->id,
        'registration_no' => 'REG-SIB-'.$suffix,
        'student_code' => 'STU-SIB-'.$suffix,
        'admission_no' => 'ADM-SIB-'.$suffix,
        'dob' => $dob,
        'gender_id' => $world->school->maleGender->id,
        'student_status_id' => $world->school->activeStatus->id,
        'admission_date' => '2026-04-01',
    ]);

    $student->guardians()->syncWithoutDetaching([
        $guardianId => ['relation_id' => $world->school->fatherRelation->id],
    ]);

    return StudentEnrollmentRecord::create([
        'student_id' => $student->id,
        'session_id' => $world->school->session->id,
        'class_id' => $world->school->class->id,
        'section_id' => $world->school->section->id,
        'campus_id' => $world->school->campus->id,
        'admission_date' => '2026-04-01',
        'student_status_id' => $world->school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);
}

/** "Ten percent off the second child." */
function siblingRule(FeeWorld $world, int $rank, float $value, ?int $feeHeadId = null): FeeSiblingDiscountRule
{
    return FeeSiblingDiscountRule::create([
        'campus_id' => $world->school->campus->id,
        'session_id' => $world->school->session->id,
        'child_rank' => $rank,
        'fee_head_id' => $feeHeadId,
        'value_type' => 'percent',
        'value' => $value,
    ]);
}

/** The tuition line on a given enrollment's April voucher. */
function tuitionFor(FeeWorld $world, StudentEnrollmentRecord $enrollment)
{
    return FeeVoucherItem::query()
        ->whereHas('voucher', fn ($q) => $q->where('student_enrollment_record_id', $enrollment->id))
        ->where('fee_head_id', $world->school->monthlyHead->id)
        ->first();
}

it('leaves the eldest child paying in full', function () {
    siblingRule($this->world, 2, 10);
    addSibling($this->world, $this->guardian->id, '2018-01-01', 'B');

    $this->world->generate(4);

    expect((float) tuitionFor($this->world, $this->world->enrollment)->discount_amount)->toBe(0.0);
});

it('takes the second child rate off the younger one', function () {
    siblingRule($this->world, 2, 10);
    $younger = addSibling($this->world, $this->guardian->id, '2018-01-01', 'B');

    $this->world->generate(4);

    expect((float) tuitionFor($this->world, $younger)->discount_amount)->toBe(500.0);
});

it('takes the third child rate off the youngest', function () {
    siblingRule($this->world, 2, 10);
    siblingRule($this->world, 3, 20);

    $second = addSibling($this->world, $this->guardian->id, '2017-01-01', 'B');
    $third = addSibling($this->world, $this->guardian->id, '2019-01-01', 'C');

    $this->world->generate(4);

    expect((float) tuitionFor($this->world, $second)->discount_amount)->toBe(500.0)
        ->and((float) tuitionFor($this->world, $third)->discount_amount)->toBe(1000.0);
});

it('does nothing until the campus switches it on', function () {
    FeePolicy::query()->update(['sibling_discount_enabled' => false]);
    siblingRule($this->world, 2, 10);
    $younger = addSibling($this->world, $this->guardian->id, '2018-01-01', 'B');

    $this->world->generate(4);

    expect((float) tuitionFor($this->world, $younger)->discount_amount)->toBe(0.0);
});

it('does nothing for an only child', function () {
    siblingRule($this->world, 2, 10);

    $this->world->generate(4);

    expect((float) tuitionFor($this->world, $this->world->enrollment)->discount_amount)->toBe(0.0);
});

it('ignores a sibling who has already left', function () {
    siblingRule($this->world, 2, 10);
    $younger = addSibling($this->world, $this->guardian->id, '2018-01-01', 'B');

    // With the eldest gone, the younger child is now the eldest and pays in full.
    $this->world->enrollment->update(['leave_date' => '2026-03-31']);

    $this->world->generate(4);

    expect((float) tuitionFor($this->world, $younger)->discount_amount)->toBe(0.0);
});

it('gives the larger of the automatic and the entered concession', function () {
    siblingRule($this->world, 2, 10);
    $younger = addSibling($this->world, $this->guardian->id, '2018-01-01', 'B');

    $type = DiscountType::firstOrCreate(
        ['code' => 'STAFF'],
        ['name' => 'Staff', 'value_type' => 'percent', 'default_value' => 50, 'is_active' => true, 'requires_approval' => false]
    );

    StudentDiscount::create([
        'student_id' => $younger->student_id,
        'student_enrollment_record_id' => $younger->id,
        'discount_type_id' => $type->id,
        'fee_head_id' => $this->world->school->monthlyHead->id,
        'value_type' => 'percent',
        'value' => 40,
        'effective_from' => '2026-04-01',
        'approval_status' => ApprovalStatus::APPROVED,
        'reason' => 'Staff child',
    ]);

    $this->world->generate(4);

    // 40% beats the sibling 10%; they do not add up to 50%.
    expect((float) tuitionFor($this->world, $younger)->discount_amount)->toBe(2000.0);
});

it('uses the sibling rate when it is the larger of the two', function () {
    siblingRule($this->world, 2, 30);
    $younger = addSibling($this->world, $this->guardian->id, '2018-01-01', 'B');

    $type = DiscountType::firstOrCreate(
        ['code' => 'EARLY_BIRD'],
        ['name' => 'Early', 'value_type' => 'percent', 'default_value' => 5, 'is_active' => true, 'requires_approval' => false]
    );

    StudentDiscount::create([
        'student_id' => $younger->student_id,
        'student_enrollment_record_id' => $younger->id,
        'discount_type_id' => $type->id,
        'fee_head_id' => $this->world->school->monthlyHead->id,
        'value_type' => 'percent',
        'value' => 5,
        'effective_from' => '2026-04-01',
        'approval_status' => ApprovalStatus::APPROVED,
        'reason' => 'Early payment',
    ]);

    $this->world->generate(4);

    expect((float) tuitionFor($this->world, $younger)->discount_amount)->toBe(1500.0);
});

it('applies a rule written for one fee head only to that head', function () {
    siblingRule($this->world, 2, 10, $this->world->school->annualHead->id);
    $younger = addSibling($this->world, $this->guardian->id, '2018-01-01', 'B');

    $this->world->generate(4);

    expect((float) tuitionFor($this->world, $younger)->discount_amount)->toBe(0.0);
});
