<?php

namespace App\Services\Student;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentLeaveRecord;
use Illuminate\Validation\ValidationException;

/**
 * The School Leaving Certificate — the TC.
 *
 * A child cannot be admitted to another school without one, so it is not a
 * convenience: a family standing at the counter asking for it is a family that
 * cannot move until they have it.
 *
 * Everything on it already exists in this system. What was missing was the one
 * record it hangs off — `student_leave_records`, which had a table, a model and
 * a relation and had never been written to. `StudentEnrollmentService::leave()`
 * writes it now, and this reads it.
 *
 * The one thing deliberately **not** asserted here is that fees are clear. This
 * says what is owed and leaves the decision to the office, because a school
 * that withholds a child's certificate over a disputed bill is making a
 * judgement no piece of software should make for it.
 */
class LeavingCertificateService
{
    /**
     * The certificate for a child who has left.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function forStudent(Student $student): array
    {
        $student->loadMissing(['user', 'gender', 'studentStatus', 'studentGuardians.guardian.user']);

        $leaving = StudentLeaveRecord::where('student_id', $student->id)
            ->with('studentStatus')
            ->orderByDesc('leave_date')
            ->orderByDesc('id')
            ->first();

        if (! $leaving) {
            throw ValidationException::withMessages([
                'student_id' => 'This child has not been marked as having left, '
                    .'so there is nothing to certify. Record the leaving first.',
            ]);
        }

        $last = StudentEnrollmentRecord::where('student_id', $student->id)
            ->with(['class', 'section', 'campus', 'session'])
            ->orderByDesc('admission_date')
            ->orderByDesc('id')
            ->first();

        $first = StudentEnrollmentRecord::where('student_id', $student->id)
            ->orderBy('admission_date')
            ->orderBy('id')
            ->first();

        $guardian = $student->studentGuardians
            ->sortByDesc(fn ($link) => (bool) $link->is_primary)
            ->first()?->guardian;

        return [
            'school' => School::where('is_active', true)->first(),
            'student' => $student,
            'guardian' => $guardian,

            'joined_on' => $first?->admission_date ?? $student->admission_date,
            'left_on' => $leaving->leave_date,
            'reason' => $leaving->description,
            'status' => $leaving->studentStatus?->name,

            'last_class' => $last?->class?->name,
            'last_section' => $last?->section?->name,
            'campus' => $last?->campus?->name,
            'session' => $last?->session?->name,

            // How long they were with the school, which is the line a receiving
            // school reads first.
            'years' => $this->yearsBetween($first, $leaving),

            'issued_on' => now(),
        ];
    }

    /**
     * How long the child was at the school, in words a certificate can print.
     */
    private function yearsBetween(?StudentEnrollmentRecord $first, StudentLeaveRecord $leaving): ?string
    {
        $from = $first?->admission_date;
        $to = $leaving->leave_date;

        if (! $from || ! $to || $to->lt($from)) {
            return null;
        }

        $months = $from->diffInMonths($to);
        $years = intdiv((int) $months, 12);
        $rest = (int) $months % 12;

        $parts = [];

        if ($years > 0) {
            $parts[] = $years.' '.($years === 1 ? 'year' : 'years');
        }

        if ($rest > 0) {
            $parts[] = $rest.' '.($rest === 1 ? 'month' : 'months');
        }

        return $parts === [] ? 'less than a month' : implode(' ', $parts);
    }
}
