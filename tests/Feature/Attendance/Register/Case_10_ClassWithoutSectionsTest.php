<?php

/**
 * Case 10 — marking a class that has no sections.
 *
 * You settled during the fee review that a class may have none at all. The
 * enrollment and the fee tables were made nullable then; attendance was the
 * last one left, and those children could not be marked present.
 *
 * The unique index needed the same care: `[date, class, section]` stops
 * protecting anything once the section is null, because a database treats each
 * NULL as distinct — so a class could quietly collect a second register for the
 * same day.
 */

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use App\Models\AttendanceSummary;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Database\QueryException;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
    $this->sectionlessClass = $this->world->school->classWithoutSections;
});

/** A child in the class that has no sections. */
function enrolIntoSectionlessClass(AttendanceWorld $world, string $suffix): Student
{
    $student = Student::create([
        'user_id' => $world->school->actor->id,
        'registration_no' => 'REG-NOSEC-'.$suffix,
        'student_code' => 'STU-NOSEC-'.$suffix,
        'admission_no' => 'ADM-NOSEC-'.$suffix,
        'dob' => now()->subYears(9)->toDateString(),
        'gender_id' => $world->school->maleGender->id,
        'student_status_id' => $world->school->activeStatus->id,
        'admission_date' => '2026-04-01',
    ]);

    StudentEnrollmentRecord::create([
        'student_id' => $student->id,
        'session_id' => $world->school->session->id,
        'class_id' => $world->school->classWithoutSections->id,
        'section_id' => null,
        'campus_id' => $world->school->campus->id,
        'admission_date' => '2026-04-01',
        'student_status_id' => $world->school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);

    return $student->fresh('currentEnrollment');
}

/** The payload the screen posts for a class with no sections. */
function sectionlessPayload(AttendanceWorld $world, array $students, string $code = 'P', string $date = '2026-04-06'): array
{
    return [
        'attendance_date' => $date,
        'campus_id' => $world->school->campus->id,
        'session_id' => $world->school->session->id,
        'class_id' => $world->school->classWithoutSections->id,
        'section_id' => 0,
        'attendances' => array_map(fn (Student $student) => [
            'student_id' => $student->id,
            'attendance_status_id' => $world->status($code)->id,
        ], $students),
    ];
}

it('marks a class that has no sections', function () {
    $students = [enrolIntoSectionlessClass($this->world, 'A'), enrolIntoSectionlessClass($this->world, 'B')];

    $this->post(route('attendance.store'), sectionlessPayload($this->world, $students))
        ->assertRedirect(route('attendance.index'));

    expect(AttendanceStudent::count())->toBe(2);
});

it('files the register under no section at all', function () {
    $students = [enrolIntoSectionlessClass($this->world, 'A')];

    $this->post(route('attendance.store'), sectionlessPayload($this->world, $students));

    expect(Attendance::firstOrFail()->section_id)->toBeNull();
});

it('accepts a submission that names no section', function () {
    $students = [enrolIntoSectionlessClass($this->world, 'A')];

    $payload = sectionlessPayload($this->world, $students);
    unset($payload['section_id']);

    // `required` used to reject this outright.
    $this->post(route('attendance.store'), $payload)->assertSessionHasNoErrors();
});

it('keeps one register a day for a class with no sections', function () {
    $students = [enrolIntoSectionlessClass($this->world, 'A')];

    $this->post(route('attendance.store'), sectionlessPayload($this->world, $students, 'P'));
    $this->post(route('attendance.store'), sectionlessPayload($this->world, $students, 'A'));

    // The unique index cannot compare NULLs, so this needed its own guard.
    expect(Attendance::count())->toBe(1)
        ->and(AttendanceStudent::count())->toBe(1);
});

it('refuses a second register for the same class and day', function () {
    $students = [enrolIntoSectionlessClass($this->world, 'A')];
    $this->post(route('attendance.store'), sectionlessPayload($this->world, $students));

    $existing = Attendance::firstOrFail();

    expect(fn () => Attendance::create([
        'attendance_date' => '2026-04-06',
        'campus_id' => $existing->campus_id,
        'session_id' => $existing->session_id,
        'class_id' => $existing->class_id,
        'section_id' => null,
        'taken_by' => $this->world->school->actor->id,
    ]))->toThrow(QueryException::class);
});

it('still keeps one register per section for a class that has them', function () {
    $withSection = $this->world->enrol(1);

    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    expect(Attendance::where('class_id', $this->world->school->class->id)->count())->toBe(1)
        ->and($withSection)->toHaveCount(1);
});

it('lets two classes be marked on the same day', function () {
    $sectionless = [enrolIntoSectionlessClass($this->world, 'A')];
    $this->world->enrol(1);

    $this->post(route('attendance.store'), sectionlessPayload($this->world, $sectionless));
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    expect(Attendance::count())->toBe(2);
});

it('summarises a child who has no section', function () {
    $students = [enrolIntoSectionlessClass($this->world, 'A')];

    $this->post(route('attendance.store'), sectionlessPayload($this->world, $students));

    $summary = AttendanceSummary::where('student_id', $students[0]->id)->firstOrFail();

    expect($summary->present_count)->toBe(1)
        ->and($summary->expected_days)->toBe(26);
});

it('locks a register that has no section', function () {
    $students = [enrolIntoSectionlessClass($this->world, 'A')];
    $this->post(route('attendance.store'), sectionlessPayload($this->world, $students));

    $teacher = $this->world->school->withFullRoles()
        ->userWithRole('teacher', 'teacher.nosection@test.local');

    Attendance::query()->update(['is_locked' => true]);

    // The lock check looks the register up by section; a null one was dropped
    // from that lookup, so it was never checked at all.
    $this->actingAs($teacher)
        ->post(route('attendance.store'), sectionlessPayload($this->world, $students, 'A'))
        ->assertForbidden();
});
