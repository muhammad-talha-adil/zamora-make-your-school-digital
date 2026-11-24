<?php

/**
 * Hiring gives the person a working login, shown once, and a login can be
 * made later from the profile.
 */

use App\Models\StaffProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
});

function hirePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sana Malik',
        'employment_type' => 'permanent',
        'basic_salary' => 30000,
        'payment_method' => 'cash',
    ], $overrides);
}

it('makes a login with a random password and shows it once', function () {
    $response = $this->postJson(route('staff.members.store'), hirePayload())->assertSuccessful();

    $login = $response->json('login');
    $user = StaffProfile::findOrFail($response->json('staff.id'))->user;

    expect($login['password'])->toHaveLength(12)
        ->and($login['password'])->not->toBe('password123')
        ->and(Hash::check($login['password'], $user->password))->toBeTrue()
        ->and($login['email'])->toBe($user->email);
});

it('makes no login when the owner unticks it', function () {
    $response = $this->postJson(route('staff.members.store'), hirePayload(['create_login' => false]))
        ->assertSuccessful();

    expect($response->json('login'))->toBeNull();
});

it('saves cnic, phone and photo on the same form', function () {
    Storage::fake('public');

    $response = $this->postJson(route('staff.members.store'), hirePayload([
        'cnic' => '35202-1234567-1',
        'phone' => '0300 1234567',
        'photo' => UploadedFile::fake()->image('sana.jpg'),
    ]))->assertSuccessful();

    $profile = StaffProfile::findOrFail($response->json('staff.id'));

    expect($profile->cnic)->toBe('35202-1234567-1')
        ->and($profile->phone)->toBe('0300-1234567')
        ->and($profile->photo)->toStartWith('/storage/staff/photos/');
});

it('rejects a badly written cnic', function () {
    $this->postJson(route('staff.members.store'), hirePayload(['cnic' => '123']))
        ->assertJsonValidationErrors('cnic');
});

it('makes a new login later from the profile', function () {
    $staff = $this->world->person('Hira Baig');

    $login = $this->postJson(route('staff.people.login', $staff->id))
        ->assertSuccessful()
        ->json('login');

    expect(Hash::check($login['password'], $staff->user->fresh()->password))->toBeTrue();
});
