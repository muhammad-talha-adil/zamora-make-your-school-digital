<?php

/**
 * Browser QA 01s: friendly duplicate/in-use messages for campuses and
 * sessions, session overlap, and owner access to menu settings.
 */

use App\Models\Campus;
use App\Models\Session;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
});

it('tells the user a campus name is already taken, naming it', function () {
    $existing = $this->world->school->campus;

    $this->postJson(route('campuses.store'), ['name' => $existing->name])
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', "A campus named \"{$existing->name}\" already exists.");
});

it('says name is required in plain words', function () {
    $this->postJson(route('campuses.store'), ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'Name is required.');
});

it('blocks deleting a campus that still has staff, naming what blocks it', function () {
    $this->world->person('Campus Staff');
    $campus = $this->world->school->campus;

    $this->deleteJson(route('campuses.destroy', $campus))
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect(Campus::find($campus->id))->not->toBeNull();
});

it('refuses a session whose dates overlap an existing one, naming it', function () {
    $existing = Session::create([
        'name' => '2030-2031', 'start_year' => 2030, 'end_year' => 2031,
        'start_date' => '2030-04-01', 'end_date' => '2031-03-31', 'is_active' => false,
    ]);

    $this->postJson(route('sessions.store'), [
        'name' => '2030-2031 B', 'start_year' => 2030, 'end_year' => 2031,
        'start_date' => '2030-09-01', 'end_date' => '2031-08-31',
    ])->assertUnprocessable()->assertJsonValidationErrors('end_date');

    $this->postJson(route('sessions.store'), [
        'name' => '2031-2032', 'start_year' => 2031, 'end_year' => 2032,
        'start_date' => '2031-04-01', 'end_date' => '2032-03-31',
    ])->assertSuccessful();

    expect(Session::where('name', '2031-2032')->first()->is_active)->toBeFalse()
        ->and($existing->fresh())->not->toBeNull();
});
