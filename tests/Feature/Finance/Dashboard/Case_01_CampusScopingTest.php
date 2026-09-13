<?php

/**
 * `FinanceController::index()` read `auth()->user()->campus_id` to pick a
 * default campus for a viewer who did not ask for one explicitly — `User` has
 * no such attribute (the accessor every other module reads is `campusId()`,
 * through the staff profile). The bare read always returned null, so the
 * fallback fired for everyone: a campus-limited viewer's dashboard defaulted
 * to whichever campus sorted first, not their own.
 */

use App\Models\StaffProfile;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
});

it('defaults to the viewer\'s own campus, not whichever sorts first', function () {
    // `$this->world->campus` was created before `otherCampus`, so it is the
    // one `Campus::first()` would return — the wrong answer for this viewer.
    $viewer = User::create([
        'name' => 'Campus Accountant',
        'username' => 'campus.accountant.'.uniqid(),
        'email' => 'campus.accountant.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $viewer->givePermissionTo('finance.view');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    StaffProfile::create([
        'user_id' => $viewer->id,
        'employee_no' => 'EMP-FIN-1',
        'campus_id' => $this->world->otherCampus->id,
        'employment_type' => 'permanent',
        'hire_date' => '2026-04-01',
        'is_active' => true,
    ]);

    $response = $this->actingAs($viewer)->get(route('finance.dashboard'));

    $response->assertOk();
    expect($response->viewData('page')['props']['selected_campus'])->toBe($this->world->otherCampus->id);
});
