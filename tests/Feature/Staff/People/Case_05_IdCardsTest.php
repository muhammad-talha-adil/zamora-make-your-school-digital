<?php

/**
 * Case 05 — the staff ID card, the parallel to the student one
 * (`tests/Feature/Student/Identity/Case_02_FamilyAndCardsTest.php`), now
 * carrying a scannable QR code that marks the wearer present for the day.
 */

use App\Models\User;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
});

it('prints an ID card for staff on a campus', function () {
    $this->world->person('Zainab Ali');
    $this->world->person('Bilal Khan');

    $page = $this->get(route('staff.people.id-cards', [
        'campus_id' => $this->world->school->campus->id,
    ]))->assertSuccessful()->getContent();

    expect(substr_count($page, 'Emp No'))->toBe(2)
        ->and($page)->toContain('Zainab Ali')
        ->and($page)->toContain('Bilal Khan');
});

it('embeds a scannable attendance QR code on each staff card', function () {
    $this->world->person('Zainab Ali');

    $page = $this->get(route('staff.people.id-cards', [
        'campus_id' => $this->world->school->campus->id,
    ]))->assertSuccessful()->getContent();

    expect(substr_count($page, 'class="qr"'))->toBe(1)
        ->and($page)->toContain('data:image/png;base64,');
});

it('prints named staff when given them', function () {
    $staff = $this->world->person('Zainab Ali');
    $this->world->person('Bilal Khan');

    $page = $this->get(route('staff.people.id-cards', [
        'staff_ids' => [$staff->id],
    ]))->assertSuccessful()->getContent();

    expect(substr_count($page, 'Emp No'))->toBe(1)
        ->and($page)->toContain('Zainab Ali');
});

it('refuses a user without staff.view', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('staff.people.id-cards'))
        ->assertForbidden();
});
