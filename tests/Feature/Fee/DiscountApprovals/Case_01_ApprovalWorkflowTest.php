<?php

/**
 * The other half of the discount approval workflow.
 *
 * `StudentRepository::createDiscountsFromAdmission()` has always created a
 * `pending` `StudentDiscount` for a `requires_approval` discount type, but
 * nothing let anyone move it out of that state — the ten `fee.*` permissions
 * did not even include an approve ability that was checked anywhere. This
 * covers the built controller/policy: Owner and Principal (`campus_admin`)
 * may approve or reject a pending discount, scoped to their own campus;
 * nobody else may.
 */

use App\Enums\Fee\ApprovalStatus;
use App\Enums\Fee\ValueType;
use App\Models\Fee\DiscountType;
use App\Models\Fee\StudentDiscount;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->world->school->withFullRoles();
});

function makePendingDiscount(FeeWorld $world, ?int $campusId = null): StudentDiscount
{
    $discountType = DiscountType::create([
        'name' => 'Merit Scholarship',
        'code' => 'MERIT-'.uniqid(),
        'value_type' => 'percent',
        'default_value' => 20,
        'is_active' => true,
        'requires_approval' => true,
    ]);

    return StudentDiscount::create([
        'student_id' => $world->student->id,
        'student_enrollment_record_id' => $world->enrollment->id,
        'discount_type_id' => $discountType->id,
        'value_type' => ValueType::PERCENT,
        'value' => 20,
        'effective_from' => '2026-04-01',
        'approval_status' => ApprovalStatus::PENDING,
    ]);
}

/** A staff member with the given role, campus and abilities. */
function campusStaff(FeeWorld $world, string $roleName, int $campusId): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName, 'guard_name' => 'web'],
        ['label' => ucfirst($roleName), 'scope_level' => Role::SCOPE_CAMPUS, 'is_active' => true]
    );

    $staff = User::create([
        'name' => ucfirst($roleName).' '.uniqid(),
        'username' => $roleName.'.'.uniqid(),
        'email' => $roleName.'.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $staff->assignRole($role);

    StaffProfile::create([
        'user_id' => $staff->id,
        'employee_no' => 'EMP-'.uniqid(),
        'campus_id' => $campusId,
        'employment_type' => 'permanent',
        'hire_date' => '2026-04-01',
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $staff->fresh();
}

it('lets a campus_admin approve a pending discount for their own campus', function () {
    $discount = makePendingDiscount($this->world);
    $admin = campusStaff($this->world, 'campus_admin', $this->world->school->campus->id);

    $response = $this->actingAs($admin)->patch(route('fee.discount-approvals.approve', $discount->id));

    $response->assertRedirect();
    $discount->refresh();
    expect($discount->approval_status)->toBe(ApprovalStatus::APPROVED);
    expect($discount->approved_by)->toBe($admin->id);
});

it('lets the owner approve a pending discount', function () {
    $discount = makePendingDiscount($this->world);
    $owner = campusStaff($this->world, 'owner', $this->world->school->campus->id);

    $response = $this->actingAs($owner)->patch(route('fee.discount-approvals.approve', $discount->id));

    $response->assertRedirect();
    expect($discount->refresh()->approval_status)->toBe(ApprovalStatus::APPROVED);
});

it('lets a campus_admin reject a pending discount', function () {
    $discount = makePendingDiscount($this->world);
    $admin = campusStaff($this->world, 'campus_admin', $this->world->school->campus->id);

    $response = $this->actingAs($admin)->patch(route('fee.discount-approvals.reject', $discount->id));

    $response->assertRedirect();
    $discount->refresh();
    expect($discount->approval_status)->toBe(ApprovalStatus::REJECTED);
    expect($discount->approved_by)->toBe($admin->id);
});

it('does not let a role without fee.discount.approve approve a discount', function () {
    $discount = makePendingDiscount($this->world);
    $clerk = campusStaff($this->world, 'clerk', $this->world->school->campus->id);

    $this->actingAs($clerk)->patch(route('fee.discount-approvals.approve', $discount->id))->assertForbidden();

    expect($discount->refresh()->approval_status)->toBe(ApprovalStatus::PENDING);
});

it('does not let a campus_admin from another campus approve the discount', function () {
    $discount = makePendingDiscount($this->world);
    $otherAdmin = campusStaff($this->world, 'campus_admin', $this->world->school->otherCampus->id);

    $this->actingAs($otherAdmin)->patch(route('fee.discount-approvals.approve', $discount->id))->assertForbidden();

    expect($discount->refresh()->approval_status)->toBe(ApprovalStatus::PENDING);
});

it('lists pending discounts scoped to the campus_admin campus', function () {
    $ownCampusDiscount = makePendingDiscount($this->world);
    $admin = campusStaff($this->world, 'campus_admin', $this->world->school->campus->id);

    $response = $this->actingAs($admin)->get(route('fee.discount-approvals.index'));

    $response->assertOk();
    $pending = $response->viewData('page')['props']['pendingDiscounts'];
    expect($pending)->toHaveCount(1);
    expect($pending[0]['id'])->toBe($ownCampusDiscount->id);
});
