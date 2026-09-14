<?php

use App\Models\Fee\FeePayment;
use App\Models\Ledger\Ledger;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Tests\Support\FeeWorld;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard reports real counts instead of hardcoded ones', function () {
    $world = FeeWorld::make();

    // A second active student besides the one FeeWorld already enrolled.
    $secondStudent = Student::create([
        'user_id' => $world->school->actor->id,
        'registration_no' => 'REG-TEST-2',
        'student_code' => 'STU-000002',
        'admission_no' => 'ADM-TEST-2',
        'dob' => now()->subYears(9)->toDateString(),
        'gender_id' => $world->school->maleGender->id,
        'student_status_id' => $world->school->activeStatus->id,
        'admission_date' => now()->toDateString(),
    ]);

    StudentEnrollmentRecord::create([
        'student_id' => $secondStudent->id,
        'session_id' => $world->school->session->id,
        'class_id' => $world->school->class->id,
        'section_id' => $world->school->section->id,
        'campus_id' => $world->school->campus->id,
        'admission_date' => now()->toDateString(),
        'student_status_id' => $world->school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);

    FeePayment::create([
        'receipt_no' => 'RCPT-TEST-1',
        'student_id' => $world->student->id,
        'student_enrollment_record_id' => $world->enrollment->id,
        'campus_id' => $world->school->campus->id,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'received_amount' => 2500,
        'allocated_amount' => 0,
        'remaining_unallocated_amount' => 2500,
        'status' => 'posted',
        'received_by' => $world->school->actor->id,
    ]);

    // `PageController::dashboard()` reads fee collection from `Ledger`, the
    // same source `FinanceController::index()` reads from — not from
    // `FeePayment` directly, which never posts to the books on its own.
    Ledger::create([
        'ledger_number' => 'LDG-TEST-1',
        'transaction_type' => 'INCOME',
        'transaction_date' => now()->toDateString(),
        'amount' => 2500,
        'campus_id' => $world->school->campus->id,
        'student_id' => $world->student->id,
    ]);

    $viewer = User::factory()->create();
    $this->actingAs($viewer);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('stats.active_students', 2)
        ->where('fee_summary.today', 2500)
    );
});
