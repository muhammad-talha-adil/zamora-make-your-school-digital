<?php

/**
 * Case 09 — the Head Teacher role exists after seeding, and the staff form
 * is handed each designation's linked role so it can hide the role picker.
 */

use App\Models\Role;
use App\Models\StaffDesignation;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
});

it('seeds a head teacher with campus-wide attendance and marks verification', function () {
    (new PermissionsSeeder)->run();
    (new RolesSeeder)->run();

    $role = Role::where('name', 'head_teacher')->firstOrFail();

    expect($role->scope_level)->toBe(Role::SCOPE_CAMPUS)
        ->and($role->hasPermissionTo('attendance.view.campus-wide'))->toBeTrue()
        ->and($role->hasPermissionTo('exam.marks.verify'))->toBeTrue()
        ->and($role->hasPermissionTo('exam.marks.enter'))->toBeTrue();
});

it('sends designations with their linked role to the staff create form', function () {
    StaffDesignation::create(['name' => 'Principal Post', 'role' => 'teacher', 'is_active' => true]);
    StaffDesignation::create(['name' => 'Unlinked Post', 'role' => null, 'is_active' => true]);

    $this->get(route('staff.people.create'))->assertInertia(fn ($page) => $page
        ->component('Staff/People/Create')
        ->where('designations', fn ($designations) => collect($designations)->firstWhere('name', 'Principal Post')['role'] === 'teacher'
            && collect($designations)->firstWhere('name', 'Unlinked Post')['role'] === null));
});
