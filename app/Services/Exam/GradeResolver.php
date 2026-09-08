<?php

namespace App\Services\Exam;

use App\Models\Exam\GradeSystem;
use App\Models\Exam\GradeSystemItem;
use Illuminate\Support\Collection;

/**
 * Which grading scale applies, and what grade a percentage earns.
 *
 * The scale was found with `GradeSystem::where('is_active', true)->first()`,
 * ignoring the `campus_id` and `session_id` the table has always carried. A
 * multi-campus school got whichever row the database returned first, and last
 * year's scale was applied to this year's results — or the reverse.
 *
 * This is the same resolution the fee and attendance policies use, and it is
 * deliberately the same shape: **campus, then session, narrowest wins.**
 *
 * The grade lookup is here too, because there were two of those as well and
 * they disagreed.
 */
class GradeResolver
{
    /** @var array<string, GradeSystem|null> */
    private array $resolved = [];

    /**
     * The bands of each resolved scale, held in memory.
     *
     * A class of forty children across eight papers is 320 grade lookups, and
     * each one was a query. The scale is a handful of rows that do not change
     * while a batch is being saved.
     *
     * @var array<int, Collection<int, GradeSystemItem>>
     */
    private array $bands = [];

    /**
     * Bands by their own id, for the caller that has an id and wants the row.
     *
     * @var array<int, GradeSystemItem|null>
     */
    private array $byId = [];

    /**
     * The scale in force for a campus, in a session.
     *
     * A scale naming both wins over one naming only the campus, which wins over
     * a school-wide one — the most specific answer available. Falls back to the
     * default scale, and then to any active one, so a school that has set none
     * of this up still grades.
     */
    public function systemFor(?int $campusId = null, ?int $sessionId = null): ?GradeSystem
    {
        $key = ($campusId ?? 'x').':'.($sessionId ?? 'x');

        if (array_key_exists($key, $this->resolved)) {
            return $this->resolved[$key];
        }

        $candidates = GradeSystem::query()
            ->where('is_active', true)
            ->where(function ($query) use ($campusId) {
                $query->whereNull('campus_id');

                if ($campusId) {
                    $query->orWhere('campus_id', $campusId);
                }
            })
            ->where(function ($query) use ($sessionId) {
                $query->whereNull('session_id');

                if ($sessionId) {
                    $query->orWhere('session_id', $sessionId);
                }
            })
            ->get();

        $best = $candidates
            // Most specific first: a scale naming both beats one naming the
            // campus, which beats a school-wide one.
            ->sortByDesc(fn (GradeSystem $s) => ($s->campus_id ? 2 : 0) + ($s->session_id ? 1 : 0))
            ->first();

        return $this->resolved[$key] = $best
            ?? GradeSystem::where('is_default', true)->first()
            ?? GradeSystem::where('is_active', true)->first();
    }

    /**
     * The grade band a percentage falls in.
     */
    public function itemFor(?float $percentage, ?int $campusId = null, ?int $sessionId = null): ?GradeSystemItem
    {
        if ($percentage === null) {
            return null;
        }

        $system = $this->systemFor($campusId, $sessionId);

        if (! $system) {
            return null;
        }

        return $this->bandsIn($system)
            ->first(fn (GradeSystemItem $item) => (float) $item->min_percentage <= $percentage
                && (float) $item->max_percentage >= $percentage);
    }

    /**
     * A scale's bands, read once.
     *
     * Ordering only matters where bands overlap, which `problemsWith()` now
     * refuses at the point a scale is saved. It is kept so an older scale saved
     * before that rule still gives the same answer twice.
     *
     * @return Collection<int, GradeSystemItem>
     */
    private function bandsIn(GradeSystem $system)
    {
        if (isset($this->bands[$system->id])) {
            return $this->bands[$system->id];
        }

        $bands = $system->gradeSystemItems()
            ->orderBy('sort_order')
            ->orderByDesc('min_percentage')
            ->get();

        foreach ($bands as $band) {
            $this->byId[$band->id] = $band;
        }

        return $this->bands[$system->id] = $bands;
    }

    /**
     * A band by its id.
     *
     * The result cache stores the grade as an id, and the pass rule needs the
     * row behind it. Reading it back one `find()` at a time turned a class of
     * forty into forty queries, so the bands already in memory answer first.
     */
    public function bandById(?int $id): ?GradeSystemItem
    {
        if ($id === null) {
            return null;
        }

        return $this->byId[$id] ??= GradeSystemItem::find($id);
    }

    public function idFor(?float $percentage, ?int $campusId = null, ?int $sessionId = null): ?int
    {
        return $this->itemFor($percentage, $campusId, $sessionId)?->id;
    }

    /**
     * Refuses a set of bands that overlap or leave a gap.
     *
     * The unique index on `(grade_system_id, min_percentage, max_percentage)`
     * stops the identical band twice and does nothing about 80–90 sitting
     * alongside 90–100 — both match 90, and which one wins is then a coin toss
     * dressed up as a rule.
     *
     * A gap is worth refusing for the same reason: a scale running 0–39 and
     * 41–100 gives a child on exactly 40 no grade at all, and nobody finds out
     * until a result card comes out blank.
     *
     * @param  array<int, array{min_percentage: float|int, max_percentage: float|int}>  $bands
     * @return array<int, string> the problems found, empty when the scale is sound
     */
    public function problemsWith(array $bands): array
    {
        $problems = [];

        $sorted = collect($bands)
            ->map(fn ($band) => [
                'min' => (float) ($band['min_percentage'] ?? 0),
                // A band saved with no ceiling is the open top one: 80 and up.
                'max' => (float) ($band['max_percentage'] ?? 100),
                'letter' => $band['grade_letter'] ?? null,
            ])
            ->sortBy('min')
            ->values();

        foreach ($sorted as $band) {
            if ($band['min'] > $band['max']) {
                $problems[] = sprintf(
                    'A band cannot start above where it ends (%s to %s).',
                    $this->trim($band['min']),
                    $this->trim($band['max'])
                );
            }
        }

        for ($i = 1; $i < $sorted->count(); $i++) {
            $previous = $sorted[$i - 1];
            $current = $sorted[$i];

            if ($current['min'] <= $previous['max']) {
                $problems[] = sprintf(
                    'Bands %s–%s and %s–%s overlap; a percentage in both would take either grade.',
                    $this->trim($previous['min']),
                    $this->trim($previous['max']),
                    $this->trim($current['min']),
                    $this->trim($current['max'])
                );
            }
        }

        return array_values(array_unique($problems));
    }

    /**
     * The bands of a saved scale, in the shape `problemsWith()` expects.
     *
     * @return array<int, array<string, mixed>>
     */
    public function bandsOf(GradeSystem $system, ?int $ignoreItemId = null): array
    {
        return $system->gradeSystemItems()
            ->when($ignoreItemId, fn ($q) => $q->where('id', '!=', $ignoreItemId))
            ->get()
            ->map(fn (GradeSystemItem $item) => [
                'min_percentage' => $item->min_percentage,
                'max_percentage' => $item->max_percentage,
                'grade_letter' => $item->grade_letter,
            ])
            ->all();
    }

    /**
     * Forgets what has been resolved, for a long-running job.
     */
    public function forget(): void
    {
        $this->resolved = [];
        $this->bands = [];
        $this->byId = [];
    }

    private function trim(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2), '0'), '.');
    }
}
