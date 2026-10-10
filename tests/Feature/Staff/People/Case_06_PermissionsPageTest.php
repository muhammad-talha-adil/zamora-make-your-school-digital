<?php

/**
 * Role-independent, per-staff-member direct permission grants. The list
 * excludes owner-only abilities even if a caller tries to force one through
 * — the real gate is server-side, not just what the UI offers.
 */

use App\Http\Controllers\Staff\StaffProfileController;
use App\Models\StaffProfile;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->staff = $this->world->person('Hira Baig');
});

it('lets campus_admin grant and revoke a direct permission for their own staff', function () {
    $this->actingAs($this->world->school->actor);

    $this->putJson(route('staff.people.permissions.update', $this->staff->id), [
        'permissions' => ['inventory.purchase.delete'],
    ])->assertSessionHasNoErrors();

    expect($this->staff->user->fresh()->hasDirectPermission('inventory.purchase.delete'))->toBeTrue();

    $this->putJson(route('staff.people.permissions.update', $this->staff->id), [
        'permissions' => [],
    ])->assertSessionHasNoErrors();

    expect($this->staff->user->fresh()->hasDirectPermission('inventory.purchase.delete'))->toBeFalse();
});

it('strips owner-only permissions even if the request includes one', function () {
    $this->actingAs($this->world->school->actor);

    $this->putJson(route('staff.people.permissions.update', $this->staff->id), [
        'permissions' => ['inventory.purchase.delete', 'users.role.assign'],
    ])->assertSessionHasNoErrors();

    // `users.role.assign` is not seeded in this world's lookup data, so
    // `hasDirectPermission()` (which requires the permission row to exist)
    // would throw rather than answer — read the raw grant instead.
    $names = $this->staff->user->fresh()->getDirectPermissions()->pluck('name');
    expect($names->contains('inventory.purchase.delete'))->toBeTrue();
    expect($names->contains('users.role.assign'))->toBeFalse();
});

it('never lists an owner-only permission on the page itself', function () {
    $this->actingAs($this->world->school->actor);

    $response = $this->get(route('staff.people.permissions', $this->staff->id));

    $response->assertOk();
    $groups = collect($response->viewData('page')['props']['permissionGroups'])->flatten();

    foreach (StaffProfileController::ownerOnlyPermissions() as $ownerOnly) {
        expect($groups->contains($ownerOnly))->toBeFalse();
    }
});

it("refuses a campus_admin editing another campus's staff permissions", function () {
    $otherCampusAdmin = User::factory()->create();
    $otherCampusAdmin->givePermissionTo('staff.manage');
    StaffProfile::create([
        'user_id' => $otherCampusAdmin->id,
        'employee_no' => 'EMP-OTHER-1',
        'campus_id' => $this->world->school->otherCampus->id,
        'employment_type' => 'permanent',
        'basic_salary' => 10000,
        'payment_method' => 'cash',
        'is_active' => true,
    ]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($otherCampusAdmin)
        ->putJson(route('staff.people.permissions.update', $this->staff->id), [
            'permissions' => ['inventory.purchase.delete'],
        ])->assertForbidden();
});
