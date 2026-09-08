<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * When the school day starts, for a period of the year.
 *
 * "Late" is not a fixed time here: school starts an hour or more earlier
 * through Ramzan and later in winter in the north, so a child arriving at 8:15
 * is late in one month and early in the next.
 */
class AttendanceTiming extends Model
{
    protected $fillable = [
        'campus_id',
        'session_id',
        'name',
        'starts_on',
        'ends_on',
        'day_starts_at',
        'late_after',
        'day_ends_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * The clock in force on a date.
     *
     * The **narrowest** period wins. A campus states its regular timing across
     * the whole session and drops a Ramzan one inside it; the shorter span is
     * plainly the more specific answer, so nobody has to remember to switch the
     * regular one off and on again around it.
     *
     * Null when the campus has set nothing, which leaves lateness a matter of
     * judgement rather than of the clock — as it was before this existed.
     */
    public static function inForce(?int $campusId, $date, ?int $sessionId = null): ?self
    {
        if (! $campusId) {
            return null;
        }

        $on = $date instanceof \DateTimeInterface
            ? Carbon::instance($date)->toDateString()
            : Carbon::parse($date)->toDateString();

        return self::query()
            ->where('campus_id', $campusId)
            ->where('is_active', true)
            ->when($sessionId, fn ($q) => $q->where(function ($inner) use ($sessionId) {
                $inner->where('session_id', $sessionId)->orWhereNull('session_id');
            }))
            ->whereDate('starts_on', '<=', $on)
            ->whereDate('ends_on', '>=', $on)
            ->get()
            // Narrowest wins. Sorted here rather than in SQL because the date
            // arithmetic that would do it is spelled differently by every
            // database, and there are only ever a handful of rows per campus.
            ->sortBy(fn (self $timing) => $timing->starts_on->diffInDays($timing->ends_on))
            ->first();
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * Whether an arrival at this time counts as late.
     *
     * Compared as clock times, because that is what they are — the column holds
     * a time of day, not a moment in history.
     */
    public function isLate(?string $checkIn): bool
    {
        if (! $checkIn) {
            return false;
        }

        return $this->toSeconds($checkIn) > $this->toSeconds((string) $this->late_after);
    }

    /**
     * How many minutes past the deadline an arrival was.
     */
    public function minutesLate(?string $checkIn): int
    {
        if (! $this->isLate($checkIn)) {
            return 0;
        }

        return (int) round(
            ($this->toSeconds($checkIn) - $this->toSeconds((string) $this->late_after)) / 60
        );
    }

    private function toSeconds(string $time): int
    {
        $parts = array_map('intval', array_pad(explode(':', $time), 3, 0));

        return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
