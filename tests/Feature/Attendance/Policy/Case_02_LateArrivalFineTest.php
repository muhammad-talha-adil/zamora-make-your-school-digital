<?php

/**
 * Case 02 — charging for repeated late arrival.
 *
 * Most schools here do not, so this does nothing until a campus turns it on:
 * a fine nobody asked for, appearing on a parent's voucher, is worse than no
 * feature at all.
 *
 * Nobody is charged for being late once. The charge is for a habit, so a school
 * says how many are forgiven, what each one past that costs, and the most that
 * may be charged in a month.
 */

use App\Models\AttendancePolicy;
use App\Models\AttendanceSummary;
use App\Models\StudentLateArrivalFine;
use App\Services\Attendance\LateArrivalFineService;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->service = app(LateArrivalFineService::class);
});

/** Turns the charge on for the campus. */
function lateFinePolicy(AttendanceWorld $world, array $attributes = []): AttendancePolicy
{
    return AttendancePolicy::updateOrCreate(
        [
            'campus_id' => $world->school->campus->id,
            'session_id' => $world->school->session->id,
        ],
        array_merge([
            'working_days' => [1, 2, 3, 4, 5, 6],
            'late_fine_enabled' => true,
            'late_fine_grace_count' => 3,
            'late_fine_amount' => 100,
            'is_active' => true,
        ], $attributes)
    );
}

/** Records a month with a given number of late arrivals. */
function monthWithLates(AttendanceWorld $world, int $lateCount): AttendanceSummary
{
    return AttendanceSummary::updateOrCreate(
        [
            'student_id' => $world->students[0]->id,
            'session_id' => $world->school->session->id,
            'month' => 4,
            'year' => 2026,
        ],
        ['late_count' => $lateCount, 'total_days' => 22, 'expected_days' => 26]
    );
}

/** Works the charge out for April. */
function computeApril(AttendanceWorld $world): ?StudentLateArrivalFine
{
    return app(LateArrivalFineService::class)->computeFor(
        $world->students[0]->id,
        $world->school->session->id,
        4,
        2026
    );
}

it('charges nothing until a campus turns it on', function () {
    monthWithLates($this->world, 10);

    expect(computeApril($this->world))->toBeNull()
        ->and(StudentLateArrivalFine::count())->toBe(0);
});

it('forgives the first few', function () {
    lateFinePolicy($this->world);
    monthWithLates($this->world, 3);

    $fine = computeApril($this->world);

    // Nobody is fined for a habit they have not formed.
    expect($fine->charged_count)->toBe(0)
        ->and((float) $fine->amount)->toBe(0.0);
});

it('charges each one past the grace count', function () {
    lateFinePolicy($this->world);
    monthWithLates($this->world, 6);

    $fine = computeApril($this->world);

    expect($fine->late_count)->toBe(6)
        ->and($fine->charged_count)->toBe(3)
        ->and((float) $fine->amount)->toBe(300.0);
});

it('stops at the monthly cap', function () {
    lateFinePolicy($this->world, ['late_fine_monthly_cap' => 250]);
    monthWithLates($this->world, 12);

    // A difficult month should not produce a bill out of all proportion to it.
    expect((float) computeApril($this->world)->amount)->toBe(250.0);
});

it('explains itself in the words the office would use', function () {
    lateFinePolicy($this->world);
    monthWithLates($this->world, 6);

    expect(computeApril($this->world)->explanation())
        ->toBe('6 late arrival(s), 3 forgiven, 3 charged');
});

it('recomputes when the register is corrected', function () {
    lateFinePolicy($this->world);
    monthWithLates($this->world, 6);
    computeApril($this->world);

    // A late arrival turned out to be an approved leave.
    monthWithLates($this->world, 4);
    $fine = computeApril($this->world);

    expect($fine->charged_count)->toBe(1)
        ->and((float) $fine->amount)->toBe(100.0)
        ->and(StudentLateArrivalFine::count())->toBe(1);
});

it('leaves a charge that has already been billed alone', function () {
    lateFinePolicy($this->world);
    monthWithLates($this->world, 6);
    $fine = computeApril($this->world);

    $fine->update(['status' => StudentLateArrivalFine::STATUS_BILLED]);

    monthWithLates($this->world, 12);
    computeApril($this->world);

    // A charge a parent has already been given is not rewritten behind them.
    expect((float) $fine->fresh()->amount)->toBe(300.0);
});

it('forgives a charge without pretending it did not happen', function () {
    lateFinePolicy($this->world);
    monthWithLates($this->world, 6);
    $fine = computeApril($this->world);

    $waived = $this->service->waive($fine, 'Van was late all month');

    expect($waived->status)->toBe(StudentLateArrivalFine::STATUS_WAIVED)
        // The count stays: "this happened and we are not charging for it".
        ->and($waived->late_count)->toBe(6)
        ->and($waived->waiver_reason)->toBe('Van was late all month');
});

it('does not bill a waived charge', function () {
    lateFinePolicy($this->world);
    monthWithLates($this->world, 6);
    $this->service->waive(computeApril($this->world), 'Waived');

    expect(StudentLateArrivalFine::billable()->count())->toBe(0);
});

it('works out a whole month at once', function () {
    lateFinePolicy($this->world);
    monthWithLates($this->world, 6);

    $result = $this->service->computeMonth($this->world->school->session->id, 4, 2026);

    expect($result['computed'])->toBe(1)
        ->and($result['charged'])->toBe(1)
        ->and($result['total'])->toBe(300.0);
});
