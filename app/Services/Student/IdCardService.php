<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The student ID card.
 *
 * The photograph is uploaded at admission and stored, and nothing has ever
 * printed it. A school runs these off a section at a time and cuts them, so
 * this assembles many rather than one.
 *
 * The guardian's phone is on the card deliberately: it is the number somebody
 * rings when a child is found at a bus stop, and it is the only field on the
 * card that exists for the child's safety rather than the school's records.
 */
class IdCardService
{
    /**
     * Cards for a set of children.
     *
     * @param  Collection<int, Student>|array<int, Student>  $students
     * @return array<int, array<string, mixed>>
     */
    public function forStudents($students): array
    {
        $students = Student::hydrate([])->merge(collect($students))->loadMissing([
            'user:id,name',
            'currentEnrollment.class',
            'currentEnrollment.section',
            'currentEnrollment.campus',
            'currentEnrollment.session',
            'studentGuardians.guardian',
        ]);

        return $students->map(fn (Student $student) => [
            'student' => $student,
            'class' => $student->currentEnrollment?->class?->name,
            'section' => $student->currentEnrollment?->section?->name,
            'campus' => $student->currentEnrollment?->campus?->name,
            'session' => $student->currentEnrollment?->session?->name,
            'guardian_phone' => $this->phoneFor($student),
        ])->values()->all();
    }

    /**
     * The children of a class or section, for the run that prints forty.
     *
     * @return Collection<int, Student>
     */
    public function studentsIn(?User $viewer, int $classId, ?int $sectionId = null): Collection
    {
        return Student::query()
            // The same reach as every other list in this module.
            ->visibleTo($viewer)
            ->whereHas('enrollmentRecords', function ($enrollment) use ($classId, $sectionId) {
                $enrollment->whereNull('leave_date')
                    ->where('class_id', $classId)
                    ->when($sectionId, fn ($q, $id) => $q->where('section_id', $id));
            })
            ->get();
    }

    /**
     * The number to ring, preferring the guardian marked primary.
     */
    private function phoneFor(Student $student): ?string
    {
        return $student->studentGuardians
            ->sortByDesc(fn ($link) => (bool) $link->is_primary)
            ->first()?->guardian?->phone;
    }
}
