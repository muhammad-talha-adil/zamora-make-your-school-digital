<?php

/**
 * A class or session that student records still use cannot be deleted, and the
 * 422 says which one and why instead of a raw error.
 */

use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->world->school->withFullRoles();
    $this->admin = $this->world->school->userWithRole('campus_admin', 'admin.'.uniqid().'@school.test');
    $this->world->enrol(2);
});

it('refuses to delete a class that student records use, naming it', function () {
    $class = $this->world->school->class;

    $this->actingAs($this->admin)->deleteJson(route('school-classes.destroy', $class))
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', fn ($message) => str_contains($message, $class->name) && str_contains($message, '2 student records'));

    expect(SchoolClass::find($class->id))->not->toBeNull();
});

it('refuses to delete a session that student records use, naming it', function () {
    $session = $this->world->school->otherSession;
    StudentEnrollmentRecord::query()->update(['session_id' => $session->id]);

    $this->actingAs($this->admin)->deleteJson(route('sessions.destroy', $session))
        ->assertStatus(422)
        ->assertJsonPath('message', fn ($message) => str_contains($message, $session->name) && str_contains($message, '2 student records'));

    expect(Session::find($session->id))->not->toBeNull();
});
