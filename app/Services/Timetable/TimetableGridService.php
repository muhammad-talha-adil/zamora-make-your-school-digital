<?php

namespace App\Services\Timetable;

use App\Models\Timetable\TimetableEntry;
use App\Models\Timetable\TimetablePeriod;
use Illuminate\Support\Collection;

/**
 * The shapes every timetable screen (manage, teacher, portal) is sent.
 *
 * One place so the grid component receives the same payload everywhere.
 */
class TimetableGridService
{
    /**
     * The active periods a campus runs in a session, in day order.
     *
     * @return Collection<int, TimetablePeriod>
     */
    public function periodsFor(?int $campusId, ?int $sessionId): Collection
    {
        if ($campusId === null || $sessionId === null) {
            return new Collection;
        }

        return TimetablePeriod::query()
            ->active()
            ->where('campus_id', $campusId)
            ->where('session_id', $sessionId)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  Collection<int, TimetablePeriod>  $periods
     * @return array<int, array{id: int, name: string, start_time: string, end_time: string, is_break: bool}>
     */
    public function periodPayload(Collection $periods): array
    {
        return $periods->map(fn (TimetablePeriod $period) => [
            'id' => $period->id,
            'name' => $period->name,
            'start_time' => substr((string) $period->start_time, 0, 5),
            'end_time' => substr((string) $period->end_time, 0, 5),
            'is_break' => $period->is_break,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, TimetableEntry>  $entries
     * @return array<int, array<string, mixed>>
     */
    public function entryPayload(Collection $entries): array
    {
        $entries->loadMissing(['subject:id,name', 'staffProfile.user:id,name', 'schoolClass:id,name', 'section:id,name']);

        return $entries->map(fn (TimetableEntry $entry) => [
            'id' => $entry->id,
            'period_id' => $entry->period_id,
            'weekday' => $entry->weekday,
            'class_id' => $entry->class_id,
            'section_id' => $entry->section_id,
            'subject_id' => $entry->subject_id,
            'staff_profile_id' => $entry->staff_profile_id,
            'subject' => $entry->subject?->name,
            'teacher' => $entry->staffProfile?->user?->name,
            'class' => $entry->schoolClass?->name,
            'section' => $entry->section?->name,
            'room' => $entry->room,
        ])->values()->all();
    }
}
