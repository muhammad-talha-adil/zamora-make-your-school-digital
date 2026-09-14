<?php

/**
 * `FeeDashboardController` ran every stat (`FeeVoucher::count()`,
 * `FeePayment::sum('received_amount')`, ...) with no campus scope at all,
 * unlike every other Fee query in the app, which goes through
 * `FeeVoucher::scopeVisibleTo()` / `FeePayment::scopeVisibleTo()`. A
 * campus-restricted viewer's dashboard silently showed every campus's totals,
 * not just their own.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\StaffProfile;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
});

/** Raises a voucher against the shared student, in the given campus. */
function raiseVoucherIn(FeeWorld $world, int $campusId, string $voucherNo): FeeVoucher
{
    return FeeVoucher::create([
        'voucher_no' => $voucherNo,
        'student_id' => $world->student->id,
        'student_enrollment_record_id' => $world->enrollment->id,
        'session_id' => $world->school->session->id,
        'campus_id' => $campusId,
        'class_id' => $world->school->class->id,
        'section_id' => $world->school->section->id,
        'voucher_month_id' => $world->monthId(4),
        'voucher_year' => 2026,
        'issue_date' => '2026-04-01',
        'due_date' => '2026-04-15',
        'status' => VoucherStatus::UNPAID,
        'net_amount' => 5000,
        'balance_amount' => 5000,
    ]);
}

it('only counts the viewer\'s own campus, not every campus', function () {
    raiseVoucherIn($this->world, $this->world->school->campus->id, 'V-DASH-OWN');
    raiseVoucherIn($this->world, $this->world->school->otherCampus->id, 'V-DASH-OTHER');

    $viewer = User::create([
        'name' => 'Campus Fee Clerk',
        'username' => 'campus.fee.clerk.'.uniqid(),
        'email' => 'campus.fee.clerk.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $viewer->givePermissionTo('fee.view');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    StaffProfile::create([
        'user_id' => $viewer->id,
        'employee_no' => 'EMP-FEE-1',
        'campus_id' => $this->world->school->otherCampus->id,
        'employment_type' => 'permanent',
        'hire_date' => '2026-04-01',
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewer)->get(route('fee.dashboard'));

    $response->assertOk();
    expect($response->viewData('page')['props']['stats']['totalVouchers'])->toBe(1);
});

it('scopes payments to the viewer\'s campus too', function () {
    FeePayment::create([
        'receipt_no' => 'R-OWN-1',
        'student_id' => $this->world->student->id,
        'student_enrollment_record_id' => $this->world->enrollment->id,
        'campus_id' => $this->world->school->campus->id,
        'received_amount' => 1000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'status' => 'posted',
        'received_by' => $this->world->school->actor->id,
    ]);

    FeePayment::create([
        'receipt_no' => 'R-OTHER-1',
        'student_id' => $this->world->student->id,
        'student_enrollment_record_id' => $this->world->enrollment->id,
        'campus_id' => $this->world->school->otherCampus->id,
        'received_amount' => 2000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'status' => 'posted',
        'received_by' => $this->world->school->actor->id,
    ]);

    $viewer = User::create([
        'name' => 'Campus Fee Clerk 2',
        'username' => 'campus.fee.clerk2.'.uniqid(),
        'email' => 'campus.fee.clerk2.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $viewer->givePermissionTo('fee.view');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    StaffProfile::create([
        'user_id' => $viewer->id,
        'employee_no' => 'EMP-FEE-2',
        'campus_id' => $this->world->school->campus->id,
        'employment_type' => 'permanent',
        'hire_date' => '2026-04-01',
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewer)->get(route('fee.dashboard'));

    $response->assertOk();
    expect((float) $response->viewData('page')['props']['stats']['totalCollected'])->toBe(1000.0);
});
