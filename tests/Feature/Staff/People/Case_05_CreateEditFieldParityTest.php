<?php

/**
 * Staff/People/Show.vue's "Employment & Salary" card (added because none of
 * these fields were editable anywhere once a staff member existed) must
 * actually move every field it exposes through to the database — the same
 * set Staff/People/Create.vue sets at hire time. This proves the backend
 * contract both forms share, not just that the two Vue templates look alike.
 */

use App\Models\Campus;
use App\Models\CampusType;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
});

it('creates a staff member with every field Create.vue submits', function () {
    $campus = $this->world->school->campus;
    $department = StaffDepartment::create(['name' => 'Admin Office', 'is_active' => true]);
    $designation = StaffDesignation::create(['name' => 'Accountant', 'is_active' => true]);

    $response = $this->postJson(route('staff.members.store'), [
        'name' => 'Hira Baig',
        'email' => 'hira.baig@example.test',
        'employee_no' => 'EMP-HIRA-1',
        'campus_id' => $campus->id,
        'department_id' => $department->id,
        'designation_id' => $designation->id,
        'employment_type' => 'contract',
        'hire_date' => '2026-01-15',
        'basic_salary' => 55000,
        'allowance_amount' => 5000,
        'payment_method' => 'bank',
        'bank_name' => 'Meezan Bank',
        'account_no' => '1234567890',
        'is_active' => true,
    ]);

    $response->assertSuccessful();

    $profile = StaffProfile::where('employee_no', 'EMP-HIRA-1')->firstOrFail();

    expect($profile->user->name)->toBe('Hira Baig')
        ->and($profile->user->email)->toBe('hira.baig@example.test')
        ->and($profile->campus_id)->toBe($campus->id)
        ->and($profile->department_id)->toBe($department->id)
        ->and($profile->designation_id)->toBe($designation->id)
        ->and($profile->employment_type)->toBe('contract')
        ->and($profile->hire_date->toDateString())->toBe('2026-01-15')
        ->and((float) $profile->basic_salary)->toBe(55000.0)
        ->and((float) $profile->allowance_amount)->toBe(5000.0)
        ->and($profile->payment_method)->toBe('bank')
        ->and($profile->bank_name)->toBe('Meezan Bank')
        ->and($profile->account_no)->toBe('1234567890')
        ->and($profile->is_active)->toBeTrue();
});

it('updates every one of those same fields through the Show.vue edit card route', function () {
    $staff = $this->world->person('Bilal Shaukat');

    $otherCampus = Campus::create([
        'name' => 'Second Campus',
        'is_active' => true,
        'campus_type_id' => CampusType::firstOrCreate(['name' => 'Main'])->id,
    ]);
    $otherDepartment = StaffDepartment::create(['name' => 'Finance', 'is_active' => true]);
    $otherDesignation = StaffDesignation::create(['name' => 'Senior Accountant', 'is_active' => true]);

    $response = $this->putJson(route('staff.members.update', $staff), [
        'name' => 'Bilal Shaukat Jr',
        'email' => 'bilal.updated@example.test',
        'employee_no' => $staff->employee_no,
        'campus_id' => $otherCampus->id,
        'department_id' => $otherDepartment->id,
        'designation_id' => $otherDesignation->id,
        'employment_type' => 'part_time',
        'hire_date' => '2026-02-01',
        'basic_salary' => 75000,
        'allowance_amount' => 8000,
        'payment_method' => 'cash',
        'bank_name' => null,
        'account_no' => null,
        'is_active' => false,
    ]);

    $response->assertSuccessful();

    $fresh = $staff->fresh(['user']);

    expect($fresh->user->name)->toBe('Bilal Shaukat Jr')
        ->and($fresh->user->email)->toBe('bilal.updated@example.test')
        ->and($fresh->campus_id)->toBe($otherCampus->id)
        ->and($fresh->department_id)->toBe($otherDepartment->id)
        ->and($fresh->designation_id)->toBe($otherDesignation->id)
        ->and($fresh->employment_type)->toBe('part_time')
        ->and($fresh->hire_date->toDateString())->toBe('2026-02-01')
        ->and((float) $fresh->basic_salary)->toBe(75000.0)
        ->and((float) $fresh->allowance_amount)->toBe(8000.0)
        ->and($fresh->payment_method)->toBe('cash')
        ->and($fresh->bank_name)->toBeNull()
        ->and($fresh->account_no)->toBeNull()
        ->and($fresh->is_active)->toBeFalse();
});

it('updates the bank fields through the same route when payment method is bank', function () {
    $staff = $this->world->person('Nadia Yousaf');

    $response = $this->putJson(route('staff.members.update', $staff), [
        'name' => $staff->user->name,
        'email' => $staff->user->email,
        'employee_no' => $staff->employee_no,
        'campus_id' => $staff->campus_id,
        'department_id' => $staff->department_id,
        'designation_id' => $staff->designation_id,
        'employment_type' => $staff->employment_type,
        'hire_date' => $staff->hire_date->toDateString(),
        'basic_salary' => $staff->basic_salary,
        'allowance_amount' => $staff->allowance_amount,
        'payment_method' => 'bank',
        'bank_name' => 'HBL',
        'account_no' => '9988776655',
        'is_active' => true,
    ]);

    $response->assertSuccessful();

    $fresh = $staff->fresh();

    expect($fresh->bank_name)->toBe('HBL')
        ->and($fresh->account_no)->toBe('9988776655');
});
