<?php

/**
 * Case 11 — the roll as it stood, not as it stands.
 *
 * Every roster and every report was built from the open enrollment, so the
 * module could only ever answer "where is this child now". Opening September
 * listed today's class; a child who moved from 5-A to 5-B in November appeared
 * under 5-B for the whole year; and a child who had since left vanished from
 * the report along with the months they were actually present.
 *
 * The enrollment periods exist precisely so that history survives. This is the
 * module using them.
 */

use App\Models\Attendance;
use App\Models\StudentEnrollmentRecord;
use Inertia\Testing\AssertableInertia;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
    $this->students = $this->world->enrol(2);
});

/**
 * Closes a child's current period and opens the next one, as a move does.
 */
function moveTo(AttendanceWorld $world, int $studentId, int $sectionId, string $on): StudentEnrollmentRecord
{
    $current = StudentEnrollmentRecord::where('student_id', $studentId)
        ->whereNull('leave_date')
        ->firstOrFail();

    $current->update(['leave_date' => $on]);

    return StudentEnrollmentRecord::create([
        'student_id' => $studentId,
        'session_id' => $current->session_id,
        'class_id' => $current->class_id,
        'section_id' => $sectionId,
        'campus_id' => $current->campus_id,
        'admission_date' => $on,
        'student_status_id' => $current->student_status_id,
        'previous_enrollment_id' => $current->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);
}

/** The roster the marking screen is given for a date. */
function rosterFor(AttendanceWorld $world, string $date, ?int $sectionId = null): array
{
    $response = test()->getJson(route('attendance.api.students', array_filter([
        'class_id' => $world->school->class->id,
        'section_id' => $sectionId,
        'date' => $date,
    ])));

    $response->assertSuccessful();

    return collect($response->json('students'))->pluck('id')->all();
}

it('lists a child on the roster for a date they were enrolled', function () {
    expect(rosterFor($this->world, '2026-04-06'))->toContain($this->students[0]->id);
});

it('leaves a child off a date before they were admitted', function () {
    $this->students[0]->currentEnrollment->update(['admission_date' => '2026-05-01']);

    expect(rosterFor($this->world, '2026-04-06'))->not->toContain($this->students[0]->id);
});

it('leaves a child off a date after they left', function () {
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-04-30']);

    expect(rosterFor($this->world, '2026-05-06'))->not->toContain($this->students[0]->id);
});

it('still lists a child who has left, for a date they were there', function () {
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-04-30']);

    // The register they were actually on must still be openable.
    expect(rosterFor($this->world, '2026-04-06'))->toContain($this->students[0]->id);
});

it('lists a moved child under the section they were in at the time', function () {
    $other = $this->world->school->otherSection;
    moveTo($this->world, $this->students[0]->id, $other->id, '2026-05-01');

    $inApril = rosterFor($this->world, '2026-04-06', $this->world->school->section->id);
    $inMay = rosterFor($this->world, '2026-05-06', $this->world->school->section->id);

    expect($inApril)->toContain($this->students[0]->id)
        ->and($inMay)->not->toContain($this->students[0]->id);
});

it('lists a moved child under their new section afterwards', function () {
    $other = $this->world->school->otherSection;
    moveTo($this->world, $this->students[0]->id, $other->id, '2026-05-01');

    expect(rosterFor($this->world, '2026-05-06', $other->id))->toContain($this->students[0]->id);
});

it('files a back-dated register under the section held on that day', function () {
    $other = $this->world->school->otherSection;
    moveTo($this->world, $this->students[0]->id, $other->id, '2026-05-01');

    // Marked in May for a day in April, when the child was still in the first
    // section.
    $this->post(route('attendance.store'), [
        'attendance_date' => '2026-04-06',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => 0,
        'attendances' => [[
            'student_id' => $this->students[0]->id,
            'attendance_status_id' => $this->world->status('P')->id,
        ]],
    ])->assertRedirect(route('attendance.index'));

    expect(Attendance::firstOrFail()->section_id)->toBe($this->world->school->section->id);
});

it('keeps a child who left mid-month on the class report', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-04-15']);

    $summary = [];
    $this->get(route('attendance.class-report', [
        'class_id' => $this->world->school->class->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertInertia(function (AssertableInertia $page) use (&$summary) {
        $summary = collect($page->toArray()['props']['summary']);
    });

    // They disappeared entirely before, taking the fortnight they were present.
    expect($summary->pluck('registration_no'))->toContain($this->students[0]->registration_no);
});

it('counts only the days a leaver was expected', function () {
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-04-15']);

    $row = [];
    $this->get(route('attendance.class-report', [
        'class_id' => $this->world->school->class->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertInertia(function (AssertableInertia $page) use (&$row) {
        $row = collect($page->toArray()['props']['summary'])
            ->firstWhere('registration_no', test()->students[0]->registration_no);
    });

    // To the 15th, not the whole month: they are not absent after leaving.
    expect($row['expected_days'])->toBe(13);
});

it('keeps a leaver on their own report too', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-04-15']);

    $props = [];
    $this->get(route('attendance.student-report', [
        'student' => $this->students[0]->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertInertia(function (AssertableInertia $page) use (&$props) {
        $props = $page->toArray()['props'];
    });

    expect($props['stats']['present'])->toBe(1)
        ->and($props['expectedDays'])->toBe(13);
});

it('agrees between the two reports for a leaver', function () {
    $this->students[0]->currentEnrollment->update(['leave_date' => '2026-04-15']);

    $studentProps = [];
    $this->get(route('attendance.student-report', [
        'student' => $this->students[0]->id, 'month' => 4, 'year' => 2026,
    ]))->assertInertia(function (AssertableInertia $page) use (&$studentProps) {
        $studentProps = $page->toArray()['props'];
    });

    $classRow = [];
    $this->get(route('attendance.class-report', [
        'class_id' => $this->world->school->class->id, 'month' => 4, 'year' => 2026,
    ]))->assertInertia(function (AssertableInertia $page) use (&$classRow) {
        $classRow = collect($page->toArray()['props']['summary'])
            ->firstWhere('registration_no', test()->students[0]->registration_no);
    });

    expect($studentProps['expectedDays'])->toBe($classRow['expected_days']);
});
