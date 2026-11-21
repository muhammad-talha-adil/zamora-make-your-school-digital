<?php

namespace App\Services\Timetable;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Timetable\TimetableEntry;
use App\Models\Timetable\TimetablePeriod;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

/**
 * Places a lesson on the timetable, refusing anything that cannot happen.
 *
 * Two clashes are refused: a **teacher** in two lessons at once, and a
 * **section** in two lessons at once. "At once" means overlapping in time, not
 * merely the same period row, so two periods that overlap on the clock clash
 * too. The unique keys on the table back this up if two requests race.
 */
class TimetableClashService
{
    /**
     * Creates or updates an entry after every check has passed.
     *
     * @param  array{class_id: int, section_id: int|null, subject_id: int, staff_profile_id: int, period_id: int, weekday: int, room?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function place(array $data, ?TimetableEntry $entry = null): TimetableEntry
    {
        $period = TimetablePeriod::findOrFail($data['period_id']);
        $class = SchoolClass::findOrFail($data['class_id']);

        $this->assertStructure($data, $period, $class);
        $this->assertNoClash($data, $period, $entry?->id);

        $attributes = [
            'campus_id' => $class->campus_id,
            'session_id' => $period->session_id,
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'] ?? null,
            'subject_id' => $data['subject_id'],
            'staff_profile_id' => $data['staff_profile_id'],
            'period_id' => $data['period_id'],
            'weekday' => $data['weekday'],
            'room' => $data['room'] ?? null,
        ];

        try {
            if ($entry) {
                $entry->update($attributes);

                return $entry;
            }

            return TimetableEntry::create($attributes);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'period_id' => 'That slot was just taken by another lesson. Reload and try again.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertStructure(array $data, TimetablePeriod $period, SchoolClass $class): void
    {
        if ($period->is_break) {
            throw ValidationException::withMessages(['period_id' => 'Nothing can be scheduled in a break.']);
        }

        if (! $period->is_active) {
            throw ValidationException::withMessages(['period_id' => 'That period is no longer in use.']);
        }

        if ((int) $period->campus_id !== (int) $class->campus_id) {
            throw ValidationException::withMessages(['period_id' => 'That period belongs to a different campus.']);
        }

        $sectionId = $data['section_id'] ?? null;

        if ($sectionId !== null && ! Section::where('id', $sectionId)->where('class_id', $class->id)->exists()) {
            throw ValidationException::withMessages(['section_id' => 'That section does not belong to this class.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertNoClash(array $data, TimetablePeriod $period, ?int $ignoreEntryId): void
    {
        $overlapping = TimetableEntry::query()
            ->with(['schoolClass:id,name', 'section:id,name', 'period:id,name'])
            ->where('session_id', $period->session_id)
            ->where('weekday', $data['weekday'])
            ->when($ignoreEntryId, fn ($q, $id) => $q->where('timetable_entries.id', '!=', $id))
            ->whereHas('period', fn ($q) => $q
                ->where('start_time', '<', $period->end_time)
                ->where('end_time', '>', $period->start_time));

        $teacherClash = (clone $overlapping)
            ->where('staff_profile_id', $data['staff_profile_id'])
            ->first();

        if ($teacherClash) {
            throw ValidationException::withMessages([
                'staff_profile_id' => sprintf(
                    'This teacher is already teaching %s%s in that period.',
                    $teacherClash->schoolClass?->name,
                    $teacherClash->section ? ' - '.$teacherClash->section->name : ''
                ),
            ]);
        }

        $sectionId = $data['section_id'] ?? null;

        $sectionClash = (clone $overlapping)
            ->where('class_id', $data['class_id'])
            ->when(
                $sectionId !== null,
                fn ($q) => $q->where('section_id', $sectionId),
                fn ($q) => $q->whereNull('section_id')
            )
            ->exists();

        if ($sectionClash) {
            throw ValidationException::withMessages([
                'period_id' => 'This section already has a lesson in that period.',
            ]);
        }
    }
}
