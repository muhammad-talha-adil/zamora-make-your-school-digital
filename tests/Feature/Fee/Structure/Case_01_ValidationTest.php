<?php

/**
 * Case 01 — what the fee structure form accepts and what it turns away.
 *
 * The rules used to be inline in the controller and covered only the title,
 * scope, fee head and amount. Every column that decides *when* a charge is
 * billed reached the database unchecked.
 */

use App\Models\Fee\FeeStructure;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

/**
 * A complete, valid create payload; overrides replace single keys.
 */
function structurePayload(AdmissionWorld $world, array $overrides = []): array
{
    return array_merge([
        'title' => 'Class 5 Standard',
        'session_id' => $world->session->id,
        'campus_id' => $world->campus->id,
        'class_id' => $world->class->id,
        'status' => 'active',
        'items' => [
            ['fee_head_id' => $world->monthlyHead->id, 'amount' => 5000],
        ],
    ], $overrides);
}

function postStructure(AdmissionWorld $world, array $overrides = [])
{
    return test()->post(route('fee.structures.store'), structurePayload($world, $overrides));
}

it('accepts a complete structure', function () {
    postStructure($this->world)->assertRedirect(route('fee.structures.index'));

    expect(FeeStructure::where('title', 'Class 5 Standard')->exists())->toBeTrue();
});

it('requires a title', function () {
    postStructure($this->world, ['title' => ''])->assertSessionHasErrors('title');
});

it('requires a session and a campus', function () {
    postStructure($this->world, ['session_id' => null, 'campus_id' => null])
        ->assertSessionHasErrors(['session_id', 'campus_id']);
});

it('rejects a status outside the allowed set', function () {
    postStructure($this->world, ['status' => 'archived'])->assertSessionHasErrors('status');
});

it('rejects a negative charge', function () {
    postStructure($this->world, ['items' => [
        ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => -100],
    ]])->assertSessionHasErrors('items.0.amount');
});

it('rejects an amount with more than two decimals', function () {
    postStructure($this->world, ['items' => [
        ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 5000.555],
    ]])->assertSessionHasErrors('items.0.amount');
});

it('rejects a fee head that does not exist', function () {
    postStructure($this->world, ['items' => [
        ['fee_head_id' => 999999, 'amount' => 5000],
    ]])->assertSessionHasErrors('items.0.fee_head_id');
});

it('rejects an unknown frequency', function () {
    postStructure($this->world, ['items' => [
        ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 5000, 'frequency' => 'fortnightly'],
    ]])->assertSessionHasErrors('items.0.frequency');
});

it('rejects the same fee head listed twice', function () {
    // Both lines would be billed, so the head is charged double.
    postStructure($this->world, ['items' => [
        ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 5000],
        ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 3000],
    ]])->assertSessionHasErrors('items');
});

it('rejects a month range with only one end given', function () {
    postStructure($this->world, ['items' => [
        [
            'fee_head_id' => $this->world->monthlyHead->id,
            'amount' => 5000,
            'starts_from_month_id' => $this->world->month(8)->id,
        ],
    ]])->assertSessionHasErrors('items.0.starts_from_month_id');
});

it('accepts a month range with both ends given', function () {
    postStructure($this->world, ['items' => [
        [
            'fee_head_id' => $this->world->monthlyHead->id,
            'amount' => 5000,
            'starts_from_month_id' => $this->world->month(8)->id,
            'ends_at_month_id' => $this->world->month(6)->id,
        ],
    ]])->assertSessionHasNoErrors();
});

it('rejects a billing year outside a sane range', function () {
    postStructure($this->world, ['items' => [
        ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 5000, 'billing_year' => 1990],
    ]])->assertSessionHasErrors('items.0.billing_year');
});

it('rejects a section the school does not have', function () {
    postStructure($this->world, ['section_ids' => [999999]])->assertSessionHasErrors('section_ids.0');
});
