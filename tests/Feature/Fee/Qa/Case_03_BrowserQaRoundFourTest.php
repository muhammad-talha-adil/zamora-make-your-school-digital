<?php

/**
 * Case 03 — browser QA round: sibling email, unicode names, amount decimals,
 * optional charges out of monthly totals, empty structures, reversal reason.
 */

use App\Http\Requests\Fee\ReverseFeePaymentRequest;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use Tests\Support\AdmissionWorld;
use Tests\Support\FeeWorld;

it('ignores the email echoed back for an existing guardian', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);
    $existing = $world->existingGuardian();

    $payload = $world->payload([
        'guardian_id' => $existing->id,
        'father_email' => 'guardianali@mgsschool.invalid-domain-xyz.test',
    ]);
    unset($payload['father_name'], $payload['father_cnic']);

    $this->post(route('students.store'), $payload)->assertSessionHasNoErrors();
});

it('accepts Urdu names for student and guardian', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);

    $this->post(route('students.store'), $world->payload([
        'name' => 'محمد علی',
        'father_name' => 'احمد خان',
    ]))->assertSessionHasNoErrors();
});

it('still rejects digits in a name', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);

    $this->post(route('students.store'), $world->payload(['name' => 'Ali 123']))
        ->assertSessionHasErrors(['name' => "Name can only have letters, spaces and . ' - @"]);
});

it('says plainly when an amount has too many decimals', function () {
    $world = FeeWorld::make();
    $this->actingAs($world->school->actor);

    $this->from(route('fee.structures.create'))->post(route('fee.structures.store'), [
        'title' => 'Decimals',
        'session_id' => $world->school->session->id,
        'class_id' => $world->school->class->id,
        'status' => 'draft',
        'items' => [['fee_head_id' => $world->school->monthlyHead->id, 'amount' => 100.123]],
    ])->assertSessionHasErrors(['items.0.amount' => 'Amount can have at most 2 decimal places.']);
});

it('keeps optional monthly charges out of the monthly total', function () {
    $world = FeeWorld::make();
    $this->actingAs($world->school->actor);
    $structure = $world->structureWithAllFrequencies(['monthly' => 4000]);
    $extra = $world->addItem(
        FeeHead::create(['code' => 'LAB', 'name' => 'Lab', 'category' => 'laboratory', 'is_recurring' => true, 'default_frequency' => 'monthly', 'is_optional' => true, 'sort_order' => 9, 'is_active' => true]),
        500,
        'monthly'
    );
    $extra->update(['is_optional' => true]);

    $this->getJson(route('fee.structures.by-scope', [
        'session_id' => $world->school->session->id,
        'campus_id' => $world->school->campus->id,
        'class_id' => $world->school->class->id,
    ]))->assertOk()
        ->assertJsonPath('data.monthly_fee', 4000)
        ->assertJsonPath('data.optional_monthly_fee', 500);
});

it('does not offer a structure that has no items', function () {
    $world = FeeWorld::make();
    $this->actingAs($world->school->actor);
    $world->structureWithAllFrequencies()->items()->delete();

    $this->getJson(route('fee.structures.by-scope', [
        'session_id' => $world->school->session->id,
        'campus_id' => $world->school->campus->id,
        'class_id' => $world->school->class->id,
    ]))->assertOk()->assertJsonPath('success', false);

    expect(FeeStructure::count())->toBeGreaterThan(0);
});

it('shows the net monthly fee on the student profile', function () {
    $world = FeeWorld::make();
    $this->actingAs($world->school->actor);
    $structure = $world->structureWithAllFrequencies(['monthly' => 4000]);
    $world->enrollment->update(['fee_structure_id' => $structure->id]);

    $this->get(route('students.show', $world->student->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.fee_summary.monthly', 4000)
            ->where('student.fee_summary.monthly_net', 4000));
});

it('asks for a reason when reversing a payment', function () {
    $world = FeeWorld::make();
    $this->actingAs($world->school->actor);

    $request = new ReverseFeePaymentRequest;
    $validator = validator(['reason' => ''], $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('reason'))->toBe('Please write the reason.');
});
