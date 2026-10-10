<?php

/**
 * `campus_admin` is not an ordinary designation role: only an owner/developer
 * may grant or remove it, and a campus may only ever have one active holder.
 * Both rules are enforced centrally in `User::assignRole()`/`syncRoles()`, so
 * they apply no matter which call site tries to grant the role.
 */

use App\Models\Role;
use App\Models\StaffDesignation;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();

    Role::firstOrCreate(['name' => 'campus_admin', 'guard_name' => 'web']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->adminPost = StaffDesignation::create([
        'name' => 'Campus Admin',
        'role' => 'campus_admin',
        'is_active' => true,
    ]);
});

function campusAdminPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'New Hire',
        'email' => null,
        'employee_no' => null,
        'employment_type' => 'permanent',
        'basic_salary' => 40000,
        'payment_method' => 'bank',
        'designation_id' => null,
    ], $overrides);
}

it('lets the owner assign a campus_admin where the campus has none yet', function () {
    $this->actingAs($this->world->school->actor);

    $response = $this->postJson(route('staff.members.store'), campusAdminPayload([
        'campus_id' => $this->world->school->campus->id,
        'designation_id' => $this->adminPost->id,
    ]));

    $response->assertSuccessful();
    expect(User::find($response->json('staff.user_id'))->hasRole('campus_admin'))->toBeTrue();
});

it('refuses a non-owner trying to assign campus_admin', function () {
    $clerk = $this->world->person('Front Desk', ['staff.manage']);

    $this->actingAs($clerk->user)->postJson(route('staff.members.store'), campusAdminPayload([
        'campus_id' => $this->world->school->campus->id,
        'designation_id' => $this->adminPost->id,
    ]))->assertForbidden();
});

it('blocks a second campus_admin on a campus that already has one', function () {
    $this->actingAs($this->world->school->actor);

    $first = $this->postJson(route('staff.members.store'), campusAdminPayload([
        'campus_id' => $this->world->school->campus->id,
        'designation_id' => $this->adminPost->id,
    ]));
    $first->assertSuccessful();

    $second = $this->postJson(route('staff.members.store'), campusAdminPayload([
        'campus_id' => $this->world->school->campus->id,
        'designation_id' => $this->adminPost->id,
        'name' => 'Second Hire',
    ]));

    $second->assertUnprocessable();
    expect(User::where('name', 'Second Hire')->first()?->hasRole('campus_admin'))->not->toBeTrue();
});
