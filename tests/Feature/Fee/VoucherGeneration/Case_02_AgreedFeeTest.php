<?php

/**
 * Case 02 — the fee the office agreed at admission.
 *
 * `fee_mode` records how the fee was set up: take the structure as it stands,
 * take it with the student's discounts, or use amounts typed by hand. Only the
 * last of those overrides the structure — otherwise every child whose fee was
 * simply confirmed would end up on a personal rate.
 */

use App\Enums\Fee\AssignmentType;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Fee\StudentFeeAssignment;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000]);
    $this->monthlyHead = $this->world->school->monthlyHead;
});

/** The monthly tuition line on April's voucher. */
function monthlyLine(FeeWorld $world): FeeVoucherItem
{
    return itemsFor(4)->firstWhere('fee_head_id', $world->school->monthlyHead->id);
}

it('bills the structure amount when the mode is structure', function () {
    $this->world->enrollment->update(['fee_mode' => 'structure']);

    expect((float) monthlyLine($this->world)->amount)->toBe(5000.0);
});

it('bills the structure amount when no mode was recorded', function () {
    $this->world->enrollment->update(['fee_mode' => null]);

    expect((float) monthlyLine($this->world)->amount)->toBe(5000.0);
});

it('bills the typed amount when the mode is manual', function () {
    $this->world->enrollment->update([
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->monthlyHead->id, 'amount' => 4000],
        ],
    ]);

    // The office negotiated Rs 4,000 against a Rs 5,000 structure.
    expect((float) monthlyLine($this->world)->amount)->toBe(4000.0);
});

it('falls back to the structure for heads the manual entry does not mention', function () {
    $this->world->enrollment->update([
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->monthlyHead->id, 'amount' => 4000],
        ],
    ]);

    $annual = itemsFor(4)->firstWhere('fee_head_id', $this->world->school->annualHead->id);

    expect((float) $annual->amount)->toBe(12000.0);
});

it('keeps the structure frequency for a manually priced head', function () {
    $this->world->enrollment->update([
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->school->annualHead->id, 'amount' => 9000],
        ],
    ]);

    itemsFor(4);
    itemsFor(5);

    // A hand-priced yearly charge is still yearly, not monthly.
    $total = FeeVoucherItem::where('fee_head_id', $this->world->school->annualHead->id)->sum('amount');

    expect((float) $total)->toBe(9000.0);
});

it('ignores typed amounts when the mode is not manual', function () {
    $this->world->enrollment->update([
        'fee_mode' => 'structure',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->monthlyHead->id, 'amount' => 4000],
        ],
    ]);

    expect((float) monthlyLine($this->world)->amount)->toBe(5000.0);
});

it('applies a mid-session override recorded against the student', function () {
    StudentFeeAssignment::create([
        'student_id' => $this->world->student->id,
        'student_enrollment_record_id' => $this->world->enrollment->id,
        'session_id' => $this->world->school->session->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'fee_head_id' => $this->monthlyHead->id,
        'assignment_type' => AssignmentType::OVERRIDE,
        'value_type' => 'fixed',
        'amount' => 3500,
        'effective_from' => '2026-04-01',
        'is_active' => true,
        'reason' => 'Revised after review',
    ]);

    expect((float) monthlyLine($this->world)->amount)->toBe(3500.0);
});

it('lets a mid-session override win over the amount agreed at admission', function () {
    $this->world->enrollment->update([
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->monthlyHead->id, 'amount' => 4000],
        ],
    ]);

    StudentFeeAssignment::create([
        'student_id' => $this->world->student->id,
        'student_enrollment_record_id' => $this->world->enrollment->id,
        'session_id' => $this->world->school->session->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'fee_head_id' => $this->monthlyHead->id,
        'assignment_type' => AssignmentType::OVERRIDE,
        'value_type' => 'fixed',
        'amount' => 3000,
        'effective_from' => '2026-04-01',
        'is_active' => true,
        'reason' => 'Hardship',
    ]);

    // The later decision is the one that stands.
    expect((float) monthlyLine($this->world)->amount)->toBe(3000.0);
});

it('does not copy the admission fee into a student fee assignment', function () {
    // Admission records the agreed fee on the enrollment. Writing it to
    // student_fee_assignments as well left the same figure in two places.
    expect(StudentFeeAssignment::count())->toBe(0);
});
