<?php

/**
 * A designation is a job title plus a default permission template. Hiring
 * somebody COPIES the template onto their own direct permissions: after that
 * the person and the template are independent of each other.
 */

use App\Models\Permission;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
    foreach (['fee.view', 'fee.reports', 'attendance.view', 'transport.view', 'staff.salary.manage'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->accountant = StaffDesignation::create([
        'name' => 'Test Accountant',
        'is_active' => true,
        'permission_template' => ['fee.view', 'fee.reports'],
    ]);
});

function hire(array $overrides = []): User
{
    $response = test()->postJson(route('staff.members.store'), array_merge([
        'name' => 'New Hire',
        'employment_type' => 'permanent',
        'basic_salary' => 40000,
        'payment_method' => 'bank',
    ], $overrides))->assertSuccessful();

    return User::findOrFail($response->json('staff.user_id'));
}

it('copies the designation template onto the new person\'s direct permissions', function () {
    $user = hire(['designation_id' => $this->accountant->id]);

    expect($user->getDirectPermissions()->pluck('name')->sort()->values()->all())->toBe(['fee.reports', 'fee.view']);
});

it('gives no permissions when the designation has no template or none is chosen', function () {
    $empty = StaffDesignation::create(['name' => 'Empty Post', 'is_active' => true]);

    expect(hire(['designation_id' => $empty->id])->getDirectPermissions())->toBeEmpty();
    expect(hire(['name' => 'Another Hire'])->getDirectPermissions())->toBeEmpty();
});

it('never copies owner-only permissions even if a template carries one', function () {
    $post = StaffDesignation::create(['name' => 'Sneaky', 'is_active' => true, 'permission_template' => ['fee.view', 'staff.salary.manage']]);

    expect(hire(['designation_id' => $post->id])->getDirectPermissions()->pluck('name')->all())->toBe(['fee.view']);
});

it('editing one person does not change the template or other people', function () {
    $first = hire(['designation_id' => $this->accountant->id]);
    $second = hire(['name' => 'Second Hire', 'designation_id' => $this->accountant->id]);

    $profile = StaffProfile::where('user_id', $first->id)->firstOrFail();
    $this->putJson(route('staff.people.permissions.update', $profile), ['permissions' => ['attendance.view']])->assertRedirect();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($first->fresh()->getDirectPermissions()->pluck('name')->all())->toBe(['attendance.view'])
        ->and($second->fresh()->getDirectPermissions()->pluck('name')->sort()->values()->all())->toBe(['fee.reports', 'fee.view'])
        ->and($this->accountant->fresh()->permission_template)->toBe(['fee.view', 'fee.reports']);
});

it('editing the template changes only people hired afterwards', function () {
    $before = hire(['designation_id' => $this->accountant->id]);

    $this->putJson(route('staff.designations.update', $this->accountant), [
        'name' => 'Test Accountant',
        'permission_template' => ['transport.view'],
    ])->assertSuccessful();

    $after = hire(['name' => 'Later Hire', 'designation_id' => $this->accountant->id]);

    expect($before->fresh()->getDirectPermissions()->pluck('name')->sort()->values()->all())->toBe(['fee.reports', 'fee.view'])
        ->and($after->getDirectPermissions()->pluck('name')->all())->toBe(['transport.view']);
});

it('seeds the standard designations idempotently and keeps an existing template', function () {
    StaffDesignation::updateOrCreate(['name' => 'Teacher'], ['is_active' => true, 'permission_template' => ['fee.view']]);

    $this->artisan('staff:seed-designations')->assertSuccessful();
    $this->artisan('staff:seed-designations')->assertSuccessful();

    expect(StaffDesignation::whereIn('name', ['Principal', 'Driver', 'Security Guard', 'Coordinator'])->count())->toBe(4)
        ->and(StaffDesignation::where('name', 'Teacher')->count())->toBe(1)
        ->and(StaffDesignation::where('name', 'Teacher')->first()->permission_template)->toBe(['fee.view'])
        ->and(StaffDesignation::where('name', 'Driver')->first()->permission_template)->toBe(['transport.view']);
});
