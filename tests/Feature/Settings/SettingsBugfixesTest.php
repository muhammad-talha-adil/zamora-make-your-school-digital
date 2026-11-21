<?php

use App\Models\Campus;
use App\Models\Session;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make()->withFullRoles();
    $this->owner = $this->world->userWithRole('owner', 'owner.'.uniqid().'@school.test');
});

it('attaches a session created with no campuses to every active campus', function () {
    Session::query()->update(['is_active' => false]);
    $second = Campus::create([
        'name' => 'Second Campus',
        'campus_type_id' => $this->world->campus->campus_type_id,
        'is_active' => true,
    ]);

    $this->actingAs($this->owner)->post(route('sessions.store'), [
        'name' => '2031-2032',
        'is_active' => true,
        'start_year' => 2031,
        'end_year' => 2032,
        'start_date' => '2031-04-01',
        'end_date' => '2032-03-31',
        'campus_ids' => [],
    ])->assertRedirect(route('sessions.index'));

    $session = Session::where('name', '2031-2032')->firstOrFail();
    expect($session->campuses()->pluck('campuses.id')->all())
        ->toContain($this->world->campus->id, $second->id);

    $this->actingAs($this->owner)->getJson(route('sessions.index'))
        ->assertOk()
        ->assertJsonFragment(['name' => '2031-2032']);

    $this->actingAs($this->owner)->get(route('sessions.index'))
        ->assertInertia(fn ($page) => $page->where('hasActiveSession', true));
});

it('redirects former dead create/edit pages to their index', function (string $routeName, string $param, string $target) {
    $params = match ($param) {
        'campus' => ['campus' => $this->world->campus],
        'session' => ['session' => $this->world->session],
        'class' => ['schoolClass' => $this->world->class],
        'none' => [],
    };

    $this->actingAs($this->owner)->get(route($routeName, $params))
        ->assertRedirect(route($target));
})->with([
    ['campuses.create', 'none', 'campuses.index'],
    ['campuses.edit', 'campus', 'campuses.index'],
    ['sessions.create', 'none', 'sessions.index'],
    ['sessions.edit', 'session', 'sessions.index'],
    ['school-classes.create', 'none', 'school-classes.index'],
    ['school-classes.edit', 'class', 'school-classes.index'],
    ['subjects.create', 'none', 'subjects.index'],
    ['sections.create', 'none', 'sections.index'],
    ['exam-types.index', 'none', 'exam.settings.index-page'],
    ['exam-types.create', 'none', 'exam.settings.index-page'],
]);

it('rejects a non-phone value on the school profile', function (string $phone, bool $valid) {
    $response = $this->actingAs($this->owner)->post(route('school-profile.update'), [
        'name' => 'Test School',
        'phone' => $phone,
    ]);

    $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('phone');
})->with([
    'letters' => ['abc', false],
    'formatted' => ['+92 (300) 123-4567', true],
]);
