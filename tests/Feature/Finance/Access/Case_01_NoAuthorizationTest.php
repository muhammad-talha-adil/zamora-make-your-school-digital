<?php

/**
 * Who may open Finance at all.
 *
 * The six `finance.*` permissions had been seeded and never checked — every
 * route in `routes/finance.php` sat on `auth` alone. This module had never
 * been reviewed before, and had no tests of any kind until this one.
 */

use App\Models\User;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();

    $this->outsider = User::create([
        'name' => 'No Rights',
        'username' => 'no.rights.'.uniqid(),
        'email' => 'no.rights.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
});

it('turns away an unpermissioned account from the finance dashboard', function () {
    $this->actingAs($this->outsider)->get(route('finance.dashboard'))->assertForbidden();
});

it('turns away an unpermissioned account from receiving a payment', function () {
    $this->actingAs($this->outsider)->get(route('finance.receive.create'))->assertForbidden();
});

it('turns away an unpermissioned account from managing categories', function () {
    $this->actingAs($this->outsider)->get(route('finance.categories.index'))->assertForbidden();
});

it('lets the actor with finance abilities through', function () {
    $this->actingAs($this->world->actor)->get(route('finance.dashboard'))->assertOk();
});
