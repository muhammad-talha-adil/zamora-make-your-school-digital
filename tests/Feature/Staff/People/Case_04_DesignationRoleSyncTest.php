<?php

/**
 * Every real staff member hired through `StaffController::storeStaff()` used
 * to get a working login and zero Spatie roles. A designation can now name a
 * Spatie role, and hiring or moving somebody into it grants that role.
 */

use App\Models\Role;
use App\Models\StaffDesignation;
use App\Models\User;
use App\Services\Staff\StaffAssignmentService;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);

    $this->teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'web']);
    $this->driverRole = Role::create(['name' => 'driver', 'guard_name' => 'web']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->mappedTeacherPost = StaffDesignation::create([
        'name' => 'Mapped Teacher',
        'role' => 'teacher',
        'is_active' => true,
    ]);

    $this->mappedDriverPost = StaffDesignation::create([
        'name' => 'Mapped Driver',
        'role' => 'driver',
        'is_active' => true,
    ]);

    $this->unmappedPost = StaffDesignation::create([
        'name' => 'Transport Supervisor',
        'role' => null,
        'is_active' => true,
    ]);
});

function baseStaffPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'New Hire',
        'email' => null,
        'employee_no' => null,
        'employment_type' => 'permanent',
        'basic_salary' => 40000,
        'payment_method' => 'bank',
    ], $overrides);
}

it('grants the mapped role when hiring somebody through the real create flow', function () {
    $response = $this->postJson(route('staff.members.store'), baseStaffPayload([
        'designation_id' => $this->mappedTeacherPost->id,
    ]));

    $response->assertSuccessful();

    $userId = $response->json('staff.user_id');
    expect(User::find($userId)->hasRole('teacher'))->toBeTrue();
});

it('assigns no role when the chosen designation has no mapped role', function () {
    $response = $this->postJson(route('staff.members.store'), baseStaffPayload([
        'designation_id' => $this->unmappedPost->id,
    ]));

    $response->assertSuccessful();

    $userId = $response->json('staff.user_id');
    expect(User::find($userId)->roles)->toBeEmpty();
});

it('assigns no role when no designation is chosen at all', function () {
    $response = $this->postJson(route('staff.members.store'), baseStaffPayload());

    $response->assertSuccessful();

    $userId = $response->json('staff.user_id');
    expect(User::find($userId)->roles)->toBeEmpty();
});

it('grants the new role when a designation change moves the person to a mapped post', function () {
    $staff = $this->world->person('Zara Khan');
    expect($staff->user->hasRole('teacher'))->toBeFalse();

    $this->putJson(route('staff.members.update', $staff), baseStaffPayload([
        'employee_no' => $staff->employee_no,
        'designation_id' => $this->mappedDriverPost->id,
    ]))->assertSuccessful();

    expect($staff->user->fresh()->hasRole('driver'))->toBeTrue();
});

it('does not touch roles when a resave keeps the same designation', function () {
    $staff = $this->world->person('Imran Latif');
    $staff->update(['designation_id' => $this->mappedTeacherPost->id]);
    $staff->user->assignRole('teacher');
    $staff->user->removeRole('teacher');

    // Resaving unrelated fields with the same designation must not silently
    // re-grant a role an admin deliberately removed.
    $this->putJson(route('staff.members.update', $staff), baseStaffPayload([
        'employee_no' => $staff->employee_no,
        'designation_id' => $this->mappedTeacherPost->id,
        'basic_salary' => 45000,
    ]))->assertSuccessful();

    expect($staff->user->fresh()->hasRole('teacher'))->toBeFalse();
});

it('grants the mapped role when a job assignment becomes the primary one', function () {
    $staff = $this->world->person('Adil Farooq');
    $jobs = app(StaffAssignmentService::class);

    $job = $jobs->give($staff, ['designation_id' => $this->mappedDriverPost->id]);
    $jobs->makePrimary($job);

    expect($staff->user->fresh()->hasRole('driver'))->toBeTrue();
});

it('rejects a designation role that is not a real Spatie role', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);

    $this->actingAs($viewer->user)->postJson(route('staff.designations.store'), [
        'name' => 'Ghost Post',
        'role' => 'not-a-real-role',
    ])->assertUnprocessable()->assertJsonValidationErrors('role');
});

it('accepts a real role name when creating a designation', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);

    $response = $this->actingAs($viewer->user)->postJson(route('staff.designations.store'), [
        'name' => 'Ghost Post',
        'role' => 'teacher',
    ]);

    $response->assertSuccessful();
    expect(StaffDesignation::where('name', 'Ghost Post')->first()->role)->toBe('teacher');
});

it('accepts leaving the role unmapped when creating a designation', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);

    $response = $this->actingAs($viewer->user)->postJson(route('staff.designations.store'), [
        'name' => 'Ghost Post',
    ]);

    $response->assertSuccessful();
    expect(StaffDesignation::where('name', 'Ghost Post')->first()->role)->toBeNull();
});
