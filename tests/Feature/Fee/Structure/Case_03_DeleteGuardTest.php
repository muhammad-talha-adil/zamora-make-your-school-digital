<?php

/**
 * Case 03 — a structure that is in use cannot be deleted.
 *
 * Deletion is soft, so removing a structure students are enrolled on left
 * `student_enrollment_records.fee_structure_id` pointing at a trashed row: the
 * bill could no longer be explained, and regeneration found no structure.
 * The school deactivates it instead, which keeps the history readable.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeVoucher;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->structure = $this->world->structureWithAllFrequencies();
});

/** Puts the student on the structure. */
function enrolOnStructure(FeeWorld $world): void
{
    $world->enrollment->update(['fee_structure_id' => $world->structure->id]);
}

/** Raises one voucher against the student's enrollment. */
function raiseVoucher(FeeWorld $world): FeeVoucher
{
    return FeeVoucher::create([
        'voucher_no' => 'V-GUARD-1',
        'student_id' => $world->student->id,
        'student_enrollment_record_id' => $world->enrollment->id,
        'session_id' => $world->school->session->id,
        'campus_id' => $world->school->campus->id,
        'class_id' => $world->school->class->id,
        'section_id' => $world->school->section->id,
        'voucher_month_id' => $world->monthId(4),
        'voucher_year' => 2026,
        'issue_date' => '2026-04-01',
        'due_date' => '2026-04-15',
        'status' => VoucherStatus::UNPAID,
    ]);
}

it('deletes a structure nothing depends on', function () {
    $this->delete(route('fee.structures.destroy', $this->structure))
        ->assertRedirect(route('fee.structures.index'))
        ->assertSessionHas('success');

    expect(FeeStructure::find($this->structure->id))->toBeNull();
});

it('refuses to delete a structure students are enrolled on', function () {
    enrolOnStructure($this->world);

    $this->delete(route('fee.structures.destroy', $this->structure))
        ->assertSessionHas('error');

    expect(FeeStructure::find($this->structure->id))->not->toBeNull();
});

it('refuses to delete a structure that vouchers were raised from', function () {
    enrolOnStructure($this->world);
    raiseVoucher($this->world);

    $this->delete(route('fee.structures.destroy', $this->structure))
        ->assertSessionHas('error');

    expect(FeeStructure::find($this->structure->id))->not->toBeNull();
});

it('names the counts so the office knows what is attached', function () {
    enrolOnStructure($this->world);
    raiseVoucher($this->world);

    $message = $this->delete(route('fee.structures.destroy', $this->structure))
        ->assertSessionHas('error')
        ->getSession()
        ->get('error');

    expect($message)->toContain('1 student(s)')
        ->and($message)->toContain('1 voucher(s)')
        ->and($message)->toContain('Deactivate');
});

it('answers a JSON delete with a 422 rather than a redirect', function () {
    enrolOnStructure($this->world);

    $this->deleteJson(route('fee.structures.destroy', $this->structure))
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('lets the school deactivate what it cannot delete', function () {
    enrolOnStructure($this->world);

    $this->patchJson(route('fee.structures.deactivate', $this->structure))
        ->assertSuccessful();

    expect($this->structure->fresh()->status->value)->toBe('inactive');
});
