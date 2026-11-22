<?php

/**
 * Case 02 — fee structure and admission QA fixes: amount limit, at least one
 * fee head, delete guard, agreed amounts shown as discounts, optional charges,
 * the student's fee summary, and plain name messages.
 */

use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
});

function qaStructurePayload(array $items): array
{
    return [
        'title' => 'Fresh Structure',
        'session_id' => test()->world->school->session->id,
        'class_id' => test()->world->school->class->id,
        'status' => 'draft',
        'items' => $items,
    ];
}

it('refuses an amount larger than the column can hold and leaves no structure behind', function () {
    $response = $this->from(route('fee.structures.create'))->post(route('fee.structures.store'), qaStructurePayload([
        ['fee_head_id' => $this->world->school->monthlyHead->id, 'amount' => 99999999999],
    ]));

    $response->assertSessionHasErrors('items.0.amount');
    expect(FeeStructure::count())->toBe(0);
});

it('needs at least one fee head to create a structure', function () {
    $this->from(route('fee.structures.create'))
        ->post(route('fee.structures.store'), qaStructurePayload([]))
        ->assertSessionHasErrors('items');

    expect(FeeStructure::count())->toBe(0);
});

it('blocks deleting a structure that vouchers use and says how many', function () {
    $structure = $this->world->structureWithAllFrequencies();
    $this->world->enrollment->update(['fee_structure_id' => $structure->id]);
    $this->world->generate(4);

    $this->deleteJson(route('fee.structures.destroy', $structure->id))
        ->assertStatus(422)
        ->assertJsonPath('in_use', true)
        ->assertJsonPath('message', 'Used by 1 voucher. You can make it Inactive instead.');

    expect(FeeStructure::find($structure->id))->not->toBeNull();
});

it('deletes a structure nothing uses', function () {
    $structure = $this->world->structureWithAllFrequencies();

    $this->deleteJson(route('fee.structures.destroy', $structure->id))->assertOk();

    expect(FeeStructure::find($structure->id))->toBeNull();
});

it('shows an agreed lower amount as the full amount with a discount', function () {
    $structure = $this->world->structureWithAllFrequencies(['monthly' => 4500]);
    $this->world->enrollment->update([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [['fee_head_id' => $this->world->school->monthlyHead->id, 'amount' => 1500]],
    ]);

    $line = $this->world->generate(5)
        ->firstWhere('fee_head_id', $this->world->school->monthlyHead->id);

    expect((float) $line->amount)->toBe(4500.0)
        ->and((float) $line->discount_amount)->toBe(3000.0)
        ->and((float) $line->net_amount)->toBe(1500.0);
});

it('bills an optional charge only when the office agreed an amount for it', function () {
    $structure = $this->world->structureWithAllFrequencies();
    $extra = $this->world->admissionHead();
    FeeStructureItem::where('fee_structure_id', $structure->id)->where('fee_head_id', $extra->id)->delete();
    FeeStructureItem::create([
        'fee_structure_id' => $structure->id,
        'fee_head_id' => $extra->id,
        'amount' => 700,
        'frequency' => 'monthly',
        'is_optional' => true,
    ]);

    expect($this->world->generate(5)->pluck('fee_head_id'))->not->toContain($extra->id);
});

it('bills a yearly charge in the billing month the structure names', function () {
    $structure = $this->world->structureWithAllFrequencies();
    FeeStructureItem::where('fee_structure_id', $structure->id)
        ->where('fee_head_id', $this->world->school->annualHead->id)
        ->update(['billing_month_id' => $this->world->monthId(8)]);

    $annualId = $this->world->school->annualHead->id;

    expect($this->world->generate(4)->pluck('fee_head_id'))->not->toContain($annualId)
        ->and($this->world->generate(8)->pluck('fee_head_id'))->toContain($annualId);
});

it('saves the extra options of a charge added to a structure', function () {
    $structure = $this->world->structureWithAllFrequencies();
    $new = FeeHead::create(['name' => 'Bus', 'category' => 'transport', 'default_frequency' => 'monthly', 'is_recurring' => true, 'is_active' => true]);

    $this->post(route('fee.structures.items.store', $structure->id), [
        'fee_head_id' => $new->id,
        'amount' => 900,
        'frequency' => 'yearly',
        'is_optional' => true,
        'is_transport_related' => true,
        'starts_from_month_id' => $this->world->monthId(4),
        'ends_at_month_id' => $this->world->monthId(9),
        'billing_month_id' => $this->world->monthId(4),
        'billing_year' => 2026,
    ])->assertSessionHasNoErrors();

    $item = FeeStructureItem::where('fee_head_id', $new->id)->firstOrFail();

    expect($item->is_optional)->toBeTrue()
        ->and($item->is_transport_related)->toBeTrue()
        ->and($item->frequency->value)->toBe('yearly')
        ->and($item->billing_year)->toBe(2026);
});

it('rejects a one-sided month range on a charge', function () {
    $structure = $this->world->structureWithAllFrequencies();
    $new = FeeHead::create(['name' => 'Lab', 'category' => 'tuition', 'default_frequency' => 'monthly', 'is_recurring' => true, 'is_active' => true]);

    $this->post(route('fee.structures.items.store', $structure->id), [
        'fee_head_id' => $new->id,
        'amount' => 100,
        'starts_from_month_id' => $this->world->monthId(4),
    ])->assertSessionHasErrors('starts_from_month_id');
});

it('tells the form which month the annual fee is billed in', function () {
    $this->world->structureWithAllFrequencies();

    $this->getJson(route('fee.structures.by-scope', [
        'session_id' => $this->world->school->session->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
    ]))->assertOk()->assertJsonPath('data.annual_billed_in', 'April');
});

it('shows the real fee on the student profile instead of zero', function () {
    $structure = $this->world->structureWithAllFrequencies(['monthly' => 4000, 'yearly' => 6000, 'once' => 10000]);
    $this->world->enrollment->update(['fee_structure_id' => $structure->id]);
    $this->world->generate(4);

    $this->get(route('students.show', $this->world->student->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.fee_summary.monthly', 4000)
            ->where('student.fee_summary.annual', 6000)
            ->where('student.fee_summary.one_time', 10000)
            ->has('student.fee_summary.vouchers', 1));
});
