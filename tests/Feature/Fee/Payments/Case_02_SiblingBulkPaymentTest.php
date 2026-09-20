<?php

/**
 * Case 02 — paying for siblings together (issue #115).
 *
 * A cashier with two of a family's children on the roll used to have to
 * repeat the whole payment form once per child. `FeePaymentController::
 * siblings()` and `storeBulk()` let them settle both from the same
 * submission, while `FeePayment` still stays one row per student.
 */

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentGuardian;
use App\Models\User;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();

    // Give the primary student a sibling: a second student who shares one of
    // their guardians, per `SiblingService::siblingsOf()`.
    $guardianUser = User::create([
        'name' => 'Shared Father',
        'username' => 'shared.father.'.uniqid(),
        'email' => 'shared.father.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $guardian = Guardian::create([
        'user_id' => $guardianUser->id,
        'cnic' => '35201-'.random_int(1000000, 9999999).'-1',
        'phone' => '0300'.random_int(1000000, 9999999),
        'occupation' => 'Business',
        'address' => 'Lahore',
    ]);

    StudentGuardian::create([
        'student_id' => $this->world->student->id,
        'guardian_id' => $guardian->id,
        'relation_id' => $this->world->school->fatherRelation->id,
        'is_primary' => true,
    ]);

    $this->sibling = Student::create([
        'user_id' => $this->world->school->actor->id,
        'registration_no' => 'REG-TEST-2',
        'student_code' => 'STU-000002',
        'admission_no' => 'ADM-TEST-2',
        'dob' => now()->subYears(8)->toDateString(),
        'gender_id' => $this->world->school->maleGender->id,
        'student_status_id' => $this->world->school->activeStatus->id,
        'admission_date' => '2026-04-01',
    ]);

    StudentEnrollmentRecord::create([
        'student_id' => $this->sibling->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'campus_id' => $this->world->school->campus->id,
        'admission_date' => '2026-04-01',
        'student_status_id' => $this->world->school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);

    StudentGuardian::create([
        'student_id' => $this->sibling->id,
        'guardian_id' => $guardian->id,
        'relation_id' => $this->world->school->fatherRelation->id,
        'is_primary' => true,
    ]);

    // Generate vouchers now that both children are on the roll.
    $this->world->generate(4);

    $this->primaryItems = FeeVoucherItem::whereHas(
        'voucher',
        fn ($q) => $q->where('student_id', $this->world->student->id)
    )->get();

    $this->siblingItems = FeeVoucherItem::whereHas(
        'voucher',
        fn ($q) => $q->where('student_id', $this->sibling->id)
    )->get();
});

it('lists the enrolled siblings of a student', function () {
    $response = $this->getJson(route('fee.payments.siblings', ['student_id' => $this->world->student->id]));

    $response->assertSuccessful();
    expect(collect($response->json())->pluck('id')->all())->toContain($this->sibling->id);
});

it('records one payment per sibling from a single bulk submission', function () {
    $primaryItem = $this->primaryItems->first();
    $siblingItem = $this->siblingItems->first();

    $response = $this->post(route('fee.payments.store-bulk'), [
        'payments' => [
            [
                'student_id' => $this->world->student->id,
                'payment_date' => '2026-04-10',
                'payment_method' => 'cash',
                'received_amount' => (float) $primaryItem->net_amount,
                'charges' => [[
                    'voucher_id' => $primaryItem->fee_voucher_id,
                    'fee_voucher_item_id' => $primaryItem->id,
                    'student_account_charge_id' => $primaryItem->student_account_charge_id,
                    'amount' => (float) $primaryItem->net_amount,
                ]],
            ],
            [
                'student_id' => $this->sibling->id,
                'payment_date' => '2026-04-10',
                'payment_method' => 'cash',
                'received_amount' => (float) $siblingItem->net_amount,
                'charges' => [[
                    'voucher_id' => $siblingItem->fee_voucher_id,
                    'fee_voucher_item_id' => $siblingItem->id,
                    'student_account_charge_id' => $siblingItem->student_account_charge_id,
                    'amount' => (float) $siblingItem->net_amount,
                ]],
            ],
        ],
    ]);

    $response->assertRedirect();
    expect(FeePayment::where('student_id', $this->world->student->id)->count())->toBe(1)
        ->and(FeePayment::where('student_id', $this->sibling->id)->count())->toBe(1);
});

it('rolls back the whole batch if one sibling row is invalid', function () {
    $primaryItem = $this->primaryItems->first();

    $this->post(route('fee.payments.store-bulk'), [
        'payments' => [
            [
                'student_id' => $this->world->student->id,
                'payment_date' => '2026-04-10',
                'payment_method' => 'cash',
                'received_amount' => (float) $primaryItem->net_amount,
                'charges' => [[
                    'voucher_id' => $primaryItem->fee_voucher_id,
                    'fee_voucher_item_id' => $primaryItem->id,
                    'student_account_charge_id' => $primaryItem->student_account_charge_id,
                    'amount' => (float) $primaryItem->net_amount,
                ]],
            ],
            [
                'student_id' => $this->sibling->id,
                'payment_date' => '2026-04-10',
                'payment_method' => 'cash',
                'received_amount' => (float) $primaryItem->net_amount + 999999,
                'charges' => [[
                    'voucher_id' => $primaryItem->fee_voucher_id,
                    'fee_voucher_item_id' => $primaryItem->id,
                    'student_account_charge_id' => $primaryItem->student_account_charge_id,
                    'amount' => (float) $primaryItem->net_amount,
                ]],
            ],
        ],
    ]);

    expect(FeePayment::count())->toBe(0);
});

it('a person without fee.payment.collect may not list siblings or bulk pay', function () {
    $outsider = User::create([
        'name' => 'No Rights',
        'username' => 'no.rights.'.uniqid(),
        'email' => 'no.rights.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $this->actingAs($outsider)
        ->getJson(route('fee.payments.siblings', ['student_id' => $this->world->student->id]))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->post(route('fee.payments.store-bulk'), ['payments' => []])
        ->assertForbidden();
});
