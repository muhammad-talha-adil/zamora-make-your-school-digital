<?php

/**
 * Case 02 — the family who has not admitted a child yet.
 *
 * A parent visits in March, asks about Class 5 and leaves. That conversation
 * was a name in a diary, and when they came back in June the school typed
 * everything again — if it remembered them at all.
 *
 * An enquiry is deliberately not a `Student`: it has no enrolment period, no
 * fee and no register, and making one a student would put every family who
 * never came back into every list in this system.
 */

use App\Models\AdmissionEnquiry;
use App\Models\StaffProfile;
use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

/** The details a counter conversation produces. */
function enquiry(AdmissionWorld $world, array $overrides = []): array
{
    return array_merge([
        'student_name' => 'Hamza Tariq',
        'phone' => '03001234567',
        'guardian_name' => 'Tariq Mehmood',
        'class_id' => $world->class->id,
        'campus_id' => $world->campus->id,
        'session_id' => $world->session->id,
        'follow_up_on' => now()->addDays(3)->toDateString(),
    ], $overrides);
}

it('records a walk-in with only a name and a number', function () {
    $this->postJson(route('students.enquiries.store'), [
        'student_name' => 'Hamza Tariq',
        'phone' => '03001234567',
    ])->assertCreated();

    expect(AdmissionEnquiry::count())->toBe(1)
        // The one thing a counter conversation reliably produces.
        ->and(AdmissionEnquiry::first()->phone)->toBe('03001234567');
});

it('refuses an enquiry with no way of reaching them', function () {
    $this->postJson(route('students.enquiries.store'), ['student_name' => 'Hamza Tariq'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('phone');
});

it('does not create a student', function () {
    $this->postJson(route('students.enquiries.store'), enquiry($this->world))->assertCreated();

    // A family who never comes back must not appear on the roll.
    expect(Student::count())->toBe(0);
});

it('starts an enquiry as open', function () {
    $this->postJson(route('students.enquiries.store'), enquiry($this->world));

    expect(AdmissionEnquiry::first()->status)->toBe(AdmissionEnquiry::STATUS_OPEN);
});

it('records who took the enquiry', function () {
    $this->postJson(route('students.enquiries.store'), enquiry($this->world));

    expect(AdmissionEnquiry::first()->handled_by)->toBe($this->world->actor->id);
});

it('lists who is due to be rung', function () {
    // Due yesterday.
    $this->postJson(route('students.enquiries.store'), enquiry($this->world, [
        'student_name' => 'Due Today',
        'follow_up_on' => now()->subDay()->toDateString(),
    ]));

    // Due next month.
    $this->postJson(route('students.enquiries.store'), enquiry($this->world, [
        'student_name' => 'Later',
        'phone' => '03007654321',
        'follow_up_on' => now()->addMonth()->toDateString(),
    ]));

    $due = $this->getJson(route('students.enquiries.index', ['due' => true]))
        ->assertSuccessful()
        ->json('data.data');

    // The list a school actually works from in admission season.
    expect($due)->toHaveCount(1)
        ->and($due[0]['student_name'])->toBe('Due Today');
});

it('stops chasing an enquiry once it has become a child', function () {
    $this->postJson(route('students.enquiries.store'), enquiry($this->world, [
        'follow_up_on' => now()->subDay()->toDateString(),
    ]));

    $this->post(route('students.store'), $this->world->payload())->assertSessionHasNoErrors();
    $student = Student::firstOrFail();
    $enquiry = AdmissionEnquiry::firstOrFail();

    $this->postJson(route('students.enquiries.admitted', $enquiry->id), [
        'student_id' => $student->id,
    ])->assertSuccessful();

    expect($enquiry->fresh()->status)->toBe(AdmissionEnquiry::STATUS_ADMITTED)
        ->and($enquiry->fresh()->converted_at)->not->toBeNull()
        ->and($this->getJson(route('students.enquiries.index', ['due' => true]))->json('data.data'))
        ->toBeEmpty();
});

it('fills the admission form in rather than making the school retype it', function () {
    $this->postJson(route('students.enquiries.store'), enquiry($this->world, [
        'dob' => '2015-06-01',
    ]));

    $this->getJson(route('students.enquiries.prefill', AdmissionEnquiry::first()->id))
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Hamza Tariq')
        ->assertJsonPath('data.father_name', 'Tariq Mehmood')
        ->assertJsonPath('data.father_phone', '03001234567')
        ->assertJsonPath('data.class_id', $this->world->class->id);
});

it('lets the office move an enquiry along', function () {
    $this->postJson(route('students.enquiries.store'), enquiry($this->world));
    $enquiry = AdmissionEnquiry::firstOrFail();

    $this->putJson(route('students.enquiries.update', $enquiry->id), enquiry($this->world, [
        'status' => 'visited',
        'notes' => 'Came in with the B-Form. Wants a place in April.',
    ]))->assertSuccessful();

    expect($enquiry->fresh()->status)->toBe(AdmissionEnquiry::STATUS_VISITED)
        ->and($enquiry->fresh()->notes)->toContain('B-Form');
});

it('shows a campus admin their own campus and the unplaced walk-ins', function () {
    $this->world->withFullRoles();

    $this->postJson(route('students.enquiries.store'), enquiry($this->world, [
        'student_name' => 'This Campus',
    ]));
    $this->postJson(route('students.enquiries.store'), enquiry($this->world, [
        'student_name' => 'Other Campus',
        'phone' => '03002222222',
        'campus_id' => $this->world->otherCampus->id,
    ]));
    $this->postJson(route('students.enquiries.store'), [
        'student_name' => 'Nobody Placed Yet',
        'phone' => '03003333333',
    ]);

    $admin = $this->world->userWithRole('campus_admin', 'admin.enq@school.test');
    StaffProfile::updateOrCreate(['user_id' => $admin->id], [
        'employee_no' => 'EMP-ENQ',
        'campus_id' => $this->world->campus->id,
        'employment_type' => 'permanent',
        'hire_date' => '2026-04-01',
        'is_active' => true,
    ]);

    $names = collect(
        $this->actingAs($admin->fresh())
            ->getJson(route('students.enquiries.index'))
            ->assertSuccessful()
            ->json('data.data')
    )->pluck('student_name');

    // A walk-in nobody has placed yet stays visible — hiding it loses the
    // family.
    expect($names)->toContain('This Campus')
        ->and($names)->toContain('Nobody Placed Yet')
        ->and($names)->not->toContain('Other Campus');
});

it('marks the enquiry admitted when the admission carries its id', function () {
    $this->postJson(route('students.enquiries.store'), enquiry($this->world));
    $enquiry = AdmissionEnquiry::firstOrFail();

    $this->post(route('students.store'), $this->world->payload(['enquiry_id' => $enquiry->id]))
        ->assertSessionHasNoErrors();

    $student = Student::firstOrFail();

    expect($enquiry->fresh()->status)->toBe(AdmissionEnquiry::STATUS_ADMITTED)
        ->and($enquiry->fresh()->student_id)->toBe($student->id)
        ->and($enquiry->fresh()->converted_at)->not->toBeNull();
});

it('leaves every enquiry untouched when an ordinary admission carries no enquiry id', function () {
    $this->postJson(route('students.enquiries.store'), enquiry($this->world));
    $enquiry = AdmissionEnquiry::firstOrFail();

    $this->post(route('students.store'), $this->world->payload())->assertSessionHasNoErrors();

    expect($enquiry->fresh()->status)->toBe(AdmissionEnquiry::STATUS_OPEN)
        ->and($enquiry->fresh()->student_id)->toBeNull();
});

it('does not let a driver read the enquiry book', function () {
    $this->world->withFullRoles();
    $driver = $this->world->userWithRole('driver', 'driver.enq@school.test');

    $this->actingAs($driver)
        ->getJson(route('students.enquiries.index'))
        ->assertForbidden();
});
