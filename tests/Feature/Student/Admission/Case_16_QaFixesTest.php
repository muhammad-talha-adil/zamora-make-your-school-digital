<?php

/**
 * Case 16 — fixes from the browser QA pass: blank optional unique fields,
 * the default student list, the campus filter and the similar-phone warning.
 */

use App\Models\Guardian;
use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('saves a blank guardian CNIC as NULL so two admissions without one do not collide', function () {
    foreach (['ADM-QA-1', 'ADM-QA-2'] as $admissionNo) {
        $this->post(route('students.store'), $this->world->payload([
            'admission_no' => $admissionNo,
            'father_cnic' => '',
            'b_form' => '',
        ]))->assertSessionHasNoErrors();
    }

    expect(Guardian::whereNull('cnic')->count())->toBe(2)
        ->and(Guardian::where('cnic', '')->count())->toBe(0)
        ->and(Student::whereNull('b_form')->count())->toBe(2);
});

it('refuses a CNIC already on another guardian with a friendly message, not a SQL error', function () {
    Guardian::create(['phone' => '03001112222', 'cnic' => '35202-1111111-1']);

    $response = $this->post(route('students.store'), $this->world->payload([
        'father_phone' => '03339998888',
        'father_cnic' => '35202-1111111-1',
    ]));

    $response->assertSessionHasErrors('father_cnic');
    expect(session('errors')->first('father_cnic'))->toContain('belongs to another guardian')
        ->and(Student::count())->toBe(0);
});

it('lists students on the default page without a search', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-LIST-1']))
        ->assertSessionHasNoErrors();

    $this->get(route('students.index'))->assertInertia(fn ($page) => $page->has('tableStudents.data', 1));

    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-LIST-2']))
        ->assertSessionHasNoErrors();

    $this->get(route('students.index'))->assertInertia(fn ($page) => $page->has('tableStudents.data', 2));
});

it('lets an owner narrow the list to one campus with campus_id', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-CAMPUS-1']))
        ->assertSessionHasNoErrors();

    $this->get(route('students.index', ['campus_id' => $this->world->campus->id]))
        ->assertInertia(fn ($page) => $page->has('tableStudents.data', 1)->where('filters.campus_id', $this->world->campus->id));

    $this->get(route('students.index', ['campus_id' => $this->world->otherCampus->id]))
        ->assertInertia(fn ($page) => $page->has('tableStudents.data', 0)->where('filters.campus_id', $this->world->otherCampus->id));
});

it('offers a similar guardian when only the last six digits match, without linking it', function () {
    $existing = $this->world->existingGuardian('35201-1234567-1', '03001234567');

    $this->getJson(route('students.guardian-by-phone', ['phone' => '03459234567']))
        ->assertOk()
        ->assertJsonPath('found', false)
        ->assertJsonPath('similar.id', $existing->id);

    expect(Guardian::count())->toBe(1);
});

it('reports no similar guardian for a short or unrelated phone', function () {
    $this->world->existingGuardian('35201-1234567-1', '03001234567');

    $this->getJson(route('students.guardian-by-phone', ['phone' => '03459999999']))
        ->assertOk()->assertJsonPath('similar', null);

    $this->getJson(route('students.guardian-by-phone', ['phone' => '123']))
        ->assertOk()->assertJsonPath('similar', null);
});
