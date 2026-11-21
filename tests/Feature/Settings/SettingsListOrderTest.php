<?php

use App\Models\SchoolClass;
use App\Models\Subject;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make()->withFullRoles();
    $this->admin = $this->world->userWithRole('campus_admin', 'campus.admin.'.uniqid().'@school.test');
});

it('lists classes in the order they were created', function () {
    $campusId = $this->world->campus->id;
    SchoolClass::query()->where('campus_id', $campusId)->forceDelete();
    foreach (['Nursery', 'Prep', 'Class 1', 'Class 2'] as $name) {
        SchoolClass::create(['campus_id' => $campusId, 'name' => $name, 'is_active' => true]);
    }

    $names = collect($this->actingAs($this->admin)->getJson(route('school-classes.all'))->assertOk()->json('data'))->pluck('name')->all();

    expect($names)->toBe(['Nursery', 'Prep', 'Class 1', 'Class 2']);
});

it('lists subjects alphabetically', function () {
    foreach (['Urdu', 'English', 'Maths'] as $name) {
        Subject::create(['name' => $name, 'is_active' => true]);
    }

    $names = collect($this->actingAs($this->admin)->getJson(route('subjects.all'))->assertOk()->json('data'))->pluck('name')->all();

    expect($names)->toBe(collect($names)->sort()->values()->all());
});
