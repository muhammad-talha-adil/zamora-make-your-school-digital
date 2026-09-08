<?php

/**
 * Case 03 — the monthly summary, which nothing used to write.
 *
 * The table, the model and the relation on `Student` all existed and no row was
 * ever inserted, so every report recomputed from scratch and anyone who trusted
 * the relation got an empty collection.
 *
 * It is a cache and nothing more: every figure is derived from the register,
 * a month is always recomputed rather than adjusted, and the whole thing can be
 * thrown away and rebuilt.
 */

use App\Models\AttendanceSummary;
use App\Services\Attendance\AttendanceSummaryService;
use Illuminate\Database\QueryException;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
    $this->summaries = app(AttendanceSummaryService::class);
});

/** The summary row for the first child, in April 2026. */
function aprilSummary(AttendanceWorld $world): ?AttendanceSummary
{
    return AttendanceSummary::where('student_id', $world->students[0]->id)
        ->where('month', 4)
        ->where('year', 2026)
        ->first();
}

it('writes a summary when a register is saved', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    expect(aprilSummary($this->world))->not->toBeNull();
});

it('counts the marks', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-07'));
    $this->post(route('attendance.store'), $this->world->payload('LT', '2026-04-08'));

    $summary = aprilSummary($this->world);

    expect($summary->present_count)->toBe(1)
        ->and($summary->absent_count)->toBe(1)
        ->and($summary->late_count)->toBe(1)
        ->and($summary->total_days)->toBe(3);
});

it('records the days the child was expected', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    // April 2026 on a six-day week: 26 days, not the one that was marked.
    expect(aprilSummary($this->world)->expected_days)->toBe(26);
});

it('counts a late arrival as a full day present', function () {
    $this->post(route('attendance.store'), $this->world->payload('LT', '2026-04-06'));

    // The child was in class; the lateness is recorded to be chased, not to
    // dock the attendance.
    expect((float) aprilSummary($this->world)->present_equivalent)->toBe(1.0);
});

it('counts a half day as half', function () {
    $this->post(route('attendance.store'), $this->world->payload('HD', '2026-04-06'));

    $summary = aprilSummary($this->world);

    expect($summary->half_day_count)->toBe(1)
        ->and((float) $summary->present_equivalent)->toBe(0.5);
});

it('measures the percentage against the expected days', function () {
    // Thirteen present days out of twenty-six expected.
    foreach ([6, 7, 8, 9, 10, 11, 13, 14, 15, 16, 17, 18, 20] as $day) {
        $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-'.str_pad($day, 2, '0', STR_PAD_LEFT)));
    }

    expect(aprilSummary($this->world)->attendance_percentage)->toBe(50.0);
});

it('does not flatter a child who was barely marked', function () {
    // Three days marked, all present. The old arithmetic called this 100%.
    foreach ([6, 7, 8] as $day) {
        $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-0'.$day));
    }

    $summary = aprilSummary($this->world);

    expect($summary->attendance_percentage)->toBeLessThan(15.0)
        ->and($summary->unmarked_days)->toBe(23);
});

it('recomputes rather than adds up when a register is corrected', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    $summary = aprilSummary($this->world);

    // One day, now absent — not one present and one absent.
    expect($summary->total_days)->toBe(1)
        ->and($summary->present_count)->toBe(0)
        ->and($summary->absent_count)->toBe(1);
});

it('keeps one summary per student per month', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-07'));

    expect(AttendanceSummary::where('student_id', $this->students[0]->id)->count())->toBe(1);
});

it('refuses a second summary row for the same month', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $existing = aprilSummary($this->world);

    // The index was named unique and declared as a plain index.
    expect(fn () => AttendanceSummary::create([
        'student_id' => $existing->student_id,
        'session_id' => $existing->session_id,
        'month' => 4,
        'year' => 2026,
    ]))->toThrow(QueryException::class);
});

it('keeps separate summaries for separate months', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-05-06'));

    expect(AttendanceSummary::where('student_id', $this->students[0]->id)->count())->toBe(2);
});

it('summarises a child who has since left', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-05-31']);

    // Rebuilt from the register, and the months they were present survive.
    $this->summaries->refresh(
        $this->students[0]->id,
        $this->world->school->session->id,
        4,
        2026
    );

    expect(aprilSummary($this->world)->present_count)->toBe(1);
});

it('rebuilds every summary from the registers', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    AttendanceSummary::query()->delete();

    $this->artisan('attendance:rebuild-summaries')->assertSuccessful();

    expect(aprilSummary($this->world))->not->toBeNull()
        ->and(aprilSummary($this->world)->present_count)->toBe(1);
});

it('rebuilds a wrong summary back to the truth', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    aprilSummary($this->world)->update(['present_count' => 99, 'expected_days' => 3]);

    $this->artisan('attendance:rebuild-summaries')->assertSuccessful();

    expect(aprilSummary($this->world)->present_count)->toBe(1)
        ->and(aprilSummary($this->world)->expected_days)->toBe(26);
});
