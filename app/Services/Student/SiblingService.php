<?php

namespace App\Services\Student;

use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Which children belong to the same family.
 *
 * Fee concessions here are given by family — "second child 20%, third child
 * 30%" — and the discount module had nowhere to ask how many of this family
 * are on the roll. The link has always been in the data: guardians are
 * deduplicated by phone number when a child is admitted, so two children with
 * the same father already point at the same `guardians` row. Nothing exposed it.
 *
 * Two rules worth stating, because a school will ask about both:
 *
 *  - **Siblings are found through the guardians**, not through a surname. Two
 *    unrelated children called Ahmed Ali are not siblings; two children with
 *    the same father are, whatever they are called.
 *  - **Only children still on the roll are counted** for a concession. An elder
 *    brother who left last year does not make this child the second child, and
 *    a school that wants it counted otherwise is deciding something this cannot
 *    decide for it.
 */
class SiblingService
{
    /**
     * The other children of this child's guardians.
     *
     * @param  bool  $enrolledOnly  only children currently on the roll
     * @return Collection<int, Student>
     */
    public function siblingsOf(Student $student, bool $enrolledOnly = true): Collection
    {
        $guardianIds = $student->studentGuardians()->pluck('guardian_id')->filter();

        if ($guardianIds->isEmpty()) {
            return collect();
        }

        return Student::query()
            ->where('id', '!=', $student->id)
            ->whereHas('studentGuardians', fn ($link) => $link->whereIn('guardian_id', $guardianIds))
            ->when($enrolledOnly, fn ($q) => $q->whereHas(
                'enrollmentRecords',
                fn ($enrollment) => $enrollment->whereNull('leave_date')
            ))
            ->with(['user:id,name', 'currentEnrollment.class', 'currentEnrollment.section'])
            ->get();
    }

    /**
     * How many of this family are on the roll, this child included.
     *
     * The figure a family concession is worked out from.
     */
    public function familySizeOn(Student $student): int
    {
        return $this->siblingsOf($student)->count() + 1;
    }

    /**
     * Where this child comes in the family, by admission date.
     *
     * "Second child" for a concession means the second to be admitted, not the
     * second to be born — a school gives the discount to whoever joined later,
     * which is the one it is still deciding a fee for.
     */
    public function birthOrderFor(Student $student): int
    {
        $family = $this->siblingsOf($student)
            ->push($student)
            ->sortBy([
                fn (Student $a, Student $b) => ($a->admission_date?->timestamp ?? 0) <=> ($b->admission_date?->timestamp ?? 0),
                fn (Student $a, Student $b) => $a->id <=> $b->id,
            ])
            ->values();

        $position = $family->search(fn (Student $s) => $s->id === $student->id);

        return $position === false ? 1 : $position + 1;
    }

    /**
     * The family as a screen would show it.
     *
     * @return array<string, mixed>
     */
    public function familyOf(Student $student): array
    {
        $siblings = $this->siblingsOf($student);

        return [
            'family_size' => $siblings->count() + 1,
            'birth_order' => $this->birthOrderFor($student),
            'siblings' => $siblings->map(fn (Student $sibling) => [
                'student_id' => $sibling->id,
                'name' => $sibling->user?->name,
                'admission_no' => $sibling->admission_no,
                'class' => $sibling->currentEnrollment?->class?->name,
                'section' => $sibling->currentEnrollment?->section?->name,
                'admission_date' => $sibling->admission_date?->toDateString(),
            ])->values()->all(),
        ];
    }
}
