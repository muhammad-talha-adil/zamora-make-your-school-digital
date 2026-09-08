<?php

/**
 * Case 04 — the edit screen says how far a change reaches.
 *
 * Changing a structure changes what every future voucher raised from it will
 * charge. The office used to find that out from the next fee run; the edit
 * screen now carries the count of enrolled students and issued vouchers.
 *
 * Vouchers already issued are deliberately left alone — a bill the parent has
 * been given is not rewritten behind their back.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeVoucher;
use Inertia\Testing\AssertableInertia;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->structure = $this->world->structureWithAllFrequencies(['monthly' => 5000]);
});

/** Opens the edit screen and hands back its `usage` prop. */
function usageProp(FeeWorld $world): array
{
    $usage = null;

    test()->get(route('fee.structures.edit', $world->structure))
        ->assertInertia(function (AssertableInertia $page) use (&$usage) {
            $usage = $page->toArray()['props']['usage'];
        });

    return $usage;
}

it('reports nothing in use for a fresh structure', function () {
    expect(usageProp($this->world))->toBe(['enrollments' => 0, 'vouchers' => 0]);
});

it('counts the students enrolled on it', function () {
    $this->world->enrollment->update(['fee_structure_id' => $this->structure->id]);

    expect(usageProp($this->world)['enrollments'])->toBe(1);
});

it('counts the vouchers raised from it', function () {
    $this->world->enrollment->update(['fee_structure_id' => $this->structure->id]);

    FeeVoucher::create([
        'voucher_no' => 'V-USAGE-1',
        'student_id' => $this->world->student->id,
        'student_enrollment_record_id' => $this->world->enrollment->id,
        'session_id' => $this->world->school->session->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'voucher_month_id' => $this->world->monthId(4),
        'voucher_year' => 2026,
        'issue_date' => '2026-04-01',
        'due_date' => '2026-04-15',
        'status' => VoucherStatus::UNPAID,
    ]);

    expect(usageProp($this->world)['vouchers'])->toBe(1);
});

/** Raises the monthly tuition charge on the structure to a new amount. */
function repriceTuition(FeeWorld $world, float $amount): void
{
    $item = $world->structure->items()
        ->where('fee_head_id', $world->school->monthlyHead->id)
        ->firstOrFail();

    test()->put(route('fee.structures.items.update', [$world->structure, $item]), [
        'fee_head_id' => $world->school->monthlyHead->id,
        'amount' => $amount,
        'frequency' => 'monthly',
    ])->assertSessionHasNoErrors();
}

it('leaves an issued voucher at the old amount after the structure changes', function () {
    $this->world->enrollment->update(['fee_structure_id' => $this->structure->id]);

    $april = $this->world->generate(4)->firstWhere('fee_head_id', $this->world->school->monthlyHead->id);

    repriceTuition($this->world, 7000);

    // April was billed at 5,000 and stays billed at 5,000: a bill already in the
    // parent's hands is not rewritten behind their back.
    expect((float) $april->fresh()->amount)->toBe(5000.0);
});

it('charges the new amount on the next voucher', function () {
    $this->world->enrollment->update(['fee_structure_id' => $this->structure->id]);

    $this->world->generate(4);

    repriceTuition($this->world, 7000);

    $may = $this->world->generate(5)->firstWhere('fee_head_id', $this->world->school->monthlyHead->id);

    expect((float) $may->amount)->toBe(7000.0);
});
