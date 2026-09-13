<?php

/**
 * `fee.view.own` — seeded on the `student`/`guardian` roles since the
 * beginning, so a child (a family signs in as the child, same as the exam
 * portal) can read their own voucher and receipt. Closing FF1's "anybody can
 * see anything" hole without wiring this back in would have locked every
 * family out of their own fee records — the same `isTheirOwn` pattern
 * `ExamResultHeaderPolicy` already uses.
 */

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->world->structureWithAllFrequencies();
});

/** A second student, with their own portal login holding only `fee.view.own`. */
function anotherFeeStudent(FeeWorld $world): array
{
    $portalUser = User::create([
        'name' => 'A Family',
        'username' => 'family.'.uniqid(),
        'email' => 'family.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $portalUser->givePermissionTo('fee.view.own');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $student = Student::create([
        'user_id' => $portalUser->id,
        'registration_no' => 'REG-OWN-1',
        'student_code' => 'STU-OWN-1',
        'admission_no' => 'ADM-OWN-1',
        'dob' => now()->subYears(10)->toDateString(),
        'gender_id' => $world->school->maleGender->id,
        'student_status_id' => $world->school->activeStatus->id,
        'admission_date' => '2026-04-01',
    ]);

    StudentEnrollmentRecord::create([
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

    return [$portalUser, $student->fresh('currentEnrollment')];
}

it('does not let a family read a voucher that belongs to someone else', function () {
    [$portalUser] = anotherFeeStudent($this->world);

    $this->actingAs($this->world->school->actor);
    $this->world->generate(4);

    // Both the world's own student and the new portal student share a class
    // and structure, so `generate()` bills both — pick the one that is not
    // the portal user's, deterministically.
    $othersVoucher = FeeVoucher::where('student_id', $this->world->student->id)->firstOrFail();

    $this->actingAs($portalUser)->get(route('fee.vouchers.show', $othersVoucher->id))->assertForbidden();
});

it('lets a family read a voucher that really is their own', function () {
    [$portalUser, $student] = anotherFeeStudent($this->world);

    $this->actingAs($this->world->school->actor);
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $voucher->update(['student_id' => $student->id]);

    $this->actingAs($portalUser)->get(route('fee.vouchers.show', $voucher->id))->assertOk();
});

it('does not let a family read a payment receipt that is not their own', function () {
    [$portalUser] = anotherFeeStudent($this->world);

    $this->actingAs($this->world->school->actor);
    $this->world->generate(4);
    $ownVoucher = FeeVoucher::where('student_id', $this->world->student->id)->firstOrFail();
    $item = $ownVoucher->items()->first();

    $this->post(route('fee.payments.store'), [
        'student_id' => $this->world->student->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => (float) $item->net_amount,
        'charges' => [[
            'voucher_id' => $item->fee_voucher_id,
            'fee_voucher_item_id' => $item->id,
            'student_account_charge_id' => $item->student_account_charge_id,
            'amount' => (float) $item->net_amount,
        ]],
    ])->assertRedirect();

    $payment = FeePayment::firstOrFail();

    $this->actingAs($portalUser)->get(route('fee.payments.show', $payment->id))->assertForbidden();
});

it('lets a family list their own voucher history by student id', function () {
    [$portalUser, $student] = anotherFeeStudent($this->world);

    $this->actingAs($portalUser)->get(route('fee.vouchers.by-student', $student->id))->assertOk();
});

it('does not let a family list another student\'s voucher history', function () {
    [$portalUser] = anotherFeeStudent($this->world);

    $this->actingAs($portalUser)->get(route('fee.vouchers.by-student', $this->world->student->id))->assertForbidden();
});
