<?php

namespace App\Services\Student;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentLeaveRecord;
use App\Models\StudentStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A child's enrolment periods: closing one, and opening the next.
 *
 * Everything a school does to a child after admitting them is that one act.
 * Leaving closes a period. Re-admission closes the old one and opens a new one.
 * A section transfer does the same, on the same day. So does promotion, into
 * next year's class.
 *
 * The module had three of these written separately and no promotion at all:
 *
 *  - `handleLeave()` closed the period and always stamped **today**, so a child
 *    who left in June and was entered in September left in September — and
 *    `leave_date` is what the fee run, the register and the exam roll all read.
 *    It never wrote `student_leave_records`, the table built to hold the reason.
 *  - `handleReactivation()` and `readmit()` were two copies of re-admission that
 *    disagreed about defaults, about what was required, and about the status to
 *    write — one of them falling back to **Left** when re-admitting, and then to
 *    a hard-coded row id.
 *  - Neither closed the open period first, so a re-admission of a child nobody
 *    had marked as left ran straight into the unique index added by
 *    `enforce_single_open_enrollment` and died with a 500.
 *
 * One routine now, and the constraint is never reached because the period is
 * always closed before the next is opened.
 */
class StudentEnrollmentService
{
    public const PROMOTED = 'promoted';

    public const DETAINED = 'detained';

    /** Failed a subject and moved up anyway — ordinary here. */
    public const PROMOTED_ON_CONDITION = 'promoted_on_condition';

    /**
     * Marks a child as having left.
     *
     * The date is asked for rather than assumed. A school entering the register
     * weeks later must be able to say when the child actually went, because
     * every historical question in this system reads `leave_date`.
     *
     * The reason goes to `student_leave_records` — the register a school keeps
     * of who left and why, which had a table, a model and a relation and had
     * never once been written to.
     *
     * @throws ValidationException
     */
    public function leave(
        Student $student,
        ?string $leaveDate = null,
        ?int $statusId = null,
        ?string $reason = null,
        ?User $actor = null
    ): Student {
        $enrollment = $this->openPeriodOf($student);

        if (! $enrollment) {
            throw ValidationException::withMessages([
                'student_id' => 'This child is not on the roll, so there is nothing to close.',
            ]);
        }

        $leaveDate = $this->leaveDateFor($enrollment, $leaveDate);
        $statusId ??= $this->statusIdNamed('Left');

        return DB::transaction(function () use ($student, $enrollment, $leaveDate, $statusId, $reason) {
            $enrollment->update([
                'leave_date' => $leaveDate,
                'student_status_id' => $statusId,
                // The free note about this particular period. The *reason they
                // left* is the leave record; these are two different things and
                // were being written to the same column.
                'description' => $reason ?? $enrollment->description,
            ]);

            StudentLeaveRecord::create([
                'student_id' => $student->id,
                'leave_date' => $leaveDate,
                'student_status_id' => $statusId,
                'description' => $reason,
            ]);

            $student->update(['student_status_id' => $statusId]);

            $this->setLoginActive($student, false);

            return $student->fresh();
        });
    }

    /**
     * Brings a child back onto the roll.
     *
     * Closes whatever is still open first. A child being re-admitted who was
     * never marked as left is the ordinary case — somebody forgot — and it used
     * to be a 500.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function readmit(Student $student, array $data, ?User $actor = null): Student
    {
        $last = $this->lastPeriodOf($student);

        $sessionId = $data['session_id'] ?? $last?->session_id;
        $classId = $data['class_id'] ?? $last?->class_id;
        $campusId = $data['campus_id'] ?? $last?->campus_id;

        // Section is genuinely optional — a class may have none.
        $sectionId = array_key_exists('section_id', $data)
            ? $data['section_id']
            : $last?->section_id;

        if (! $sessionId || ! $classId) {
            throw ValidationException::withMessages([
                'class_id' => 'Say which session and class this child is coming back into.',
            ]);
        }

        $admissionDate = $data['admission_date'] ?? now()->toDateString();
        $statusId = $data['student_status_id'] ?? $this->statusIdNamed('Active');

        return DB::transaction(function () use (
            $student, $sessionId, $classId, $sectionId, $campusId, $admissionDate, $statusId, $data
        ) {
            $previous = $this->closeOpenPeriod($student, $admissionDate);

            $this->openPeriod($student, [
                'session_id' => $sessionId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'campus_id' => $campusId,
                'admission_date' => $admissionDate,
                'student_status_id' => $statusId,
                'previous_enrollment_id' => $previous?->id ?? $this->lastPeriodOf($student)?->id,
            ], $data);

            $student->update(['student_status_id' => $statusId]);

            $this->setLoginActive($student, true);

            return $student->fresh();
        });
    }

    /**
     * Moves children into another class or section, as of a date.
     *
     * Fifteen children out of 9-A and into 9-B in October is one act, not
     * fifteen edits — and each one is a period closed and a period opened, so
     * the register for September still says 9-A and October's says 9-B.
     *
     * @param  Collection<int, Student>|array<int, Student>  $students
     * @return int how many were moved
     *
     * @throws ValidationException
     */
    public function transfer(
        $students,
        int $classId,
        ?int $sectionId = null,
        ?string $effectiveFrom = null,
        ?int $campusId = null
    ): int {
        $students = collect($students);
        $effectiveFrom ??= now()->toDateString();

        if ($students->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($students, $classId, $sectionId, $effectiveFrom, $campusId) {
            $moved = 0;

            foreach ($students as $student) {
                $open = $this->openPeriodOf($student);

                if (! $open) {
                    // Not on the roll: there is nothing to move.
                    continue;
                }

                // Already there. Doing this twice must not lay down a second
                // period — the seeder lesson.
                if ((int) $open->class_id === $classId && (int) $open->section_id === (int) $sectionId) {
                    continue;
                }

                $this->closeOpenPeriod($student, $effectiveFrom);

                $this->openPeriod($student, [
                    'session_id' => $open->session_id,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'campus_id' => $campusId ?? $open->campus_id,
                    'admission_date' => $effectiveFrom,
                    'student_status_id' => $open->student_status_id,
                    'previous_enrollment_id' => $open->id,
                ], $this->feeShapeOf($open));

                $moved++;
            }

            return $moved;
        });
    }

    /**
     * Moves a child up at the end of the year.
     *
     * Three outcomes, all ordinary here: **promoted**, **detained** (repeats the
     * class), and **promoted on condition** (failed a subject and moved up
     * anyway). A detained child still gets a new period — they are in next
     * year's session, in the same class — because the session has changed and
     * the fee, the register and the exam roll all hang off the period.
     *
     * Whether the child passed is the exam module's answer, not this one's:
     * `AnnualResultService::forStudent()`. This records the decision.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function promote(
        Student $student,
        int $toSessionId,
        ?int $toClassId = null,
        ?int $toSectionId = null,
        string $outcome = self::PROMOTED,
        ?string $startingOn = null,
        ?User $actor = null
    ): StudentEnrollmentRecord {
        $open = $this->openPeriodOf($student);

        if (! $open) {
            throw ValidationException::withMessages([
                'student_id' => 'This child is not on the roll, so there is nothing to promote.',
            ]);
        }

        if (! in_array($outcome, [self::PROMOTED, self::DETAINED, self::PROMOTED_ON_CONDITION], true)) {
            throw ValidationException::withMessages([
                'outcome' => 'A promotion is either promoted, detained, or promoted on condition.',
            ]);
        }

        /*
         * Already been through this run.
         *
         * Checked before anything else, because a child who has already been
         * promoted is *in* the new class — and asking "what comes after Class
         * 6" for a child already in Class 6 refuses a run that has nothing left
         * to do. Running the sheet twice is what a school does when the screen
         * hangs, and it must be harmless.
         */
        $already = StudentEnrollmentRecord::where('student_id', $student->id)
            ->where('session_id', $toSessionId)
            ->whereNull('leave_date')
            ->first();

        if ($already) {
            return $already;
        }

        // Detained means the same class again. Promoted means the next one,
        // which the caller names — and which `nextClassAfter()` suggests.
        $targetClass = $outcome === self::DETAINED
            ? (int) $open->class_id
            : ($toClassId ?? $this->nextClassAfter($open->class_id)?->id);

        if (! $targetClass) {
            throw ValidationException::withMessages([
                'to_class_id' => 'There is no class after this one. Say which class to promote into, '
                    .'or set the class levels so the system can work it out.',
            ]);
        }

        $startingOn ??= now()->toDateString();

        return DB::transaction(function () use (
            $student, $open, $toSessionId, $targetClass, $toSectionId, $outcome, $startingOn
        ) {

            $this->closeOpenPeriod($student, $startingOn);

            $new = $this->openPeriod($student, [
                'session_id' => $toSessionId,
                'class_id' => $targetClass,
                'section_id' => $toSectionId,
                'campus_id' => $open->campus_id,
                'admission_date' => $startingOn,
                'student_status_id' => $open->student_status_id,
                'previous_enrollment_id' => $open->id,
                'description' => $this->promotionNote($outcome),
            ], $this->feeShapeOf($open));

            return $new;
        });
    }

    /**
     * Undoes a promotion.
     *
     * A section promoted by mistake is otherwise fixed by hand in the database,
     * which is how enrolment histories get destroyed. The new period is deleted
     * and the one it came from is reopened.
     *
     * @throws ValidationException
     */
    public function revertPromotion(Student $student, int $fromSessionId): Student
    {
        $promoted = StudentEnrollmentRecord::where('student_id', $student->id)
            ->where('session_id', $fromSessionId)
            ->whereNull('leave_date')
            ->first();

        if (! $promoted) {
            throw ValidationException::withMessages([
                'session_id' => 'This child has no open period in that session to undo.',
            ]);
        }

        $previous = $promoted->previous_enrollment_id
            ? StudentEnrollmentRecord::find($promoted->previous_enrollment_id)
            : null;

        return DB::transaction(function () use ($student, $promoted, $previous) {
            // The new period goes first, or reopening the old one would be a
            // second open period and the unique index would refuse it.
            $promoted->delete();

            $previous?->update(['leave_date' => null]);

            return $student->fresh();
        });
    }

    /**
     * The class a school would normally promote into.
     *
     * A suggestion, never the last word: the caller may always name the target.
     * Null where the levels have not been filled in, or where this is the last
     * class in the school — and "there is no next class" is the right answer for
     * a child finishing Class 10.
     */
    public function nextClassAfter(?int $classId): ?SchoolClass
    {
        if (! $classId) {
            return null;
        }

        $current = SchoolClass::find($classId);

        if (! $current || $current->level === null) {
            return null;
        }

        return SchoolClass::where('is_active', true)
            ->whereNotNull('level')
            ->where('level', '>', $current->level)
            ->orderBy('level')
            ->first();
    }

    /**
     * The period a child is currently in.
     *
     * Ordered, unlike `Student::currentEnrollment()` used to be — a `hasOne`
     * with no order is a coin toss, whatever the unique index says.
     */
    public function openPeriodOf(Student $student): ?StudentEnrollmentRecord
    {
        return StudentEnrollmentRecord::where('student_id', $student->id)
            ->whereNull('leave_date')
            ->orderByDesc('admission_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * The most recent period, open or closed.
     */
    public function lastPeriodOf(Student $student): ?StudentEnrollmentRecord
    {
        return StudentEnrollmentRecord::where('student_id', $student->id)
            ->orderByRaw('CASE WHEN leave_date IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('admission_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Closes whatever is open, on the day the next period begins.
     *
     * The single reason the unique index is never reached.
     */
    private function closeOpenPeriod(Student $student, string $on): ?StudentEnrollmentRecord
    {
        $open = $this->openPeriodOf($student);

        if (! $open) {
            return null;
        }

        // A period cannot close before it opened.
        $closeOn = $open->admission_date && $open->admission_date->gt($on)
            ? $open->admission_date->toDateString()
            : $on;

        $open->update(['leave_date' => $closeOn]);

        return $open;
    }

    /**
     * Opens a period, carrying the fee shape of the one it follows.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $extra
     */
    private function openPeriod(Student $student, array $attributes, array $extra = []): StudentEnrollmentRecord
    {
        return StudentEnrollmentRecord::create(array_merge([
            'student_id' => $student->id,
            'leave_date' => null,
            'monthly_fee' => 0,
            'annual_fee' => 0,
        ], $this->feeKeysFrom($extra), $attributes));
    }

    /**
     * What a period charges, so the next one starts from the same place.
     *
     * The fee for a *new class* is the fee module's business; carrying the old
     * shape forward is the honest default, and it is visible on the enrolment
     * screen for somebody to change.
     *
     * @return array<string, mixed>
     */
    private function feeShapeOf(StudentEnrollmentRecord $period): array
    {
        return [
            'monthly_fee' => $period->monthly_fee,
            'annual_fee' => $period->annual_fee,
            'fee_structure_id' => $period->fee_structure_id,
            'fee_mode' => $period->fee_mode,
            'custom_fee_entries' => $period->custom_fee_entries,
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function feeKeysFrom(array $source): array
    {
        return array_filter(
            [
                'monthly_fee' => $source['monthly_fee'] ?? null,
                'annual_fee' => $source['annual_fee'] ?? null,
                'fee_structure_id' => $source['fee_structure_id'] ?? null,
                'fee_mode' => $source['fee_mode'] ?? null,
                'custom_fee_entries' => $source['custom_fee_entries'] ?? null,
            ],
            fn ($value) => $value !== null
        );
    }

    /**
     * Checks the leave date against the period it closes.
     *
     * @throws ValidationException
     */
    private function leaveDateFor(StudentEnrollmentRecord $enrollment, ?string $leaveDate): string
    {
        $leaveDate ??= now()->toDateString();

        if ($enrollment->admission_date && $leaveDate < $enrollment->admission_date->toDateString()) {
            throw ValidationException::withMessages([
                'leave_date' => 'A child cannot leave before the day they joined ('
                    .$enrollment->admission_date->format('d M Y').').',
            ]);
        }

        if ($leaveDate > now()->toDateString()) {
            // `leave_date` is what "still on the roll" is read from, so a future
            // date would take a child off the register today for a day that has
            // not come. A leaving date is recorded on the day it happens.
            throw ValidationException::withMessages([
                'leave_date' => 'A leaving date cannot be in the future.',
            ]);
        }

        return $leaveDate;
    }

    private function promotionNote(string $outcome): string
    {
        return match ($outcome) {
            self::DETAINED => 'Detained — repeating the year.',
            self::PROMOTED_ON_CONDITION => 'Promoted on condition.',
            default => 'Promoted.',
        };
    }

    /**
     * The id of a named status.
     *
     * The old code fell back from Active to **Left** and then to a hard-coded
     * `2`, so a school that renamed its statuses re-admitted children as having
     * left. There is no guessing here: a missing status is a seeding problem and
     * says so.
     *
     * @throws ValidationException
     */
    private function statusIdNamed(string $name): int
    {
        $status = StudentStatus::where('name', $name)->first();

        if (! $status) {
            throw ValidationException::withMessages([
                'student_status_id' => "This school has no \"{$name}\" status set up.",
            ]);
        }

        return (int) $status->id;
    }

    /**
     * Turns the child's login on or off with them.
     */
    private function setLoginActive(Student $student, bool $active): void
    {
        if ($student->user_id) {
            User::where('id', $student->user_id)->update(['is_active' => $active]);
        }
    }
}
