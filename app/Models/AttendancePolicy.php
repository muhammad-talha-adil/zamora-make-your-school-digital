<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Which days a campus works, and whether it tells guardians about absences.
 *
 * Schools here differ on all of it — Friday is a half day at many, Saturday is
 * a full day at some and closed at others — so it is a setting rather than a
 * rule.
 */
class AttendancePolicy extends Model
{
    /** Monday to Saturday, which is the common week here. */
    public const DEFAULT_WORKING_DAYS = [1, 2, 3, 4, 5, 6];

    protected $fillable = [
        'campus_id',
        'session_id',
        'working_days',
        'half_days',
        'absence_alert_enabled',
        'lock_after_days',
        'late_fine_enabled',
        'late_fine_grace_count',
        'late_fine_amount',
        'late_fine_monthly_cap',
        'notes',
        'is_active',
    ];

    protected $attributes = [
        'absence_alert_enabled' => false,
        'lock_after_days' => 0,
        'late_fine_enabled' => false,
        'late_fine_grace_count' => 3,
        'late_fine_amount' => 0,
        'is_active' => true,
    ];

    protected $casts = [
        'working_days' => 'array',
        'half_days' => 'array',
        'absence_alert_enabled' => 'boolean',
        'lock_after_days' => 'integer',
        'late_fine_enabled' => 'boolean',
        'late_fine_grace_count' => 'integer',
        'late_fine_amount' => 'decimal:2',
        'late_fine_monthly_cap' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Whether a register taken on this date is old enough to close itself.
     *
     * Zero — the default — never closes anything, which is how the module
     * behaved before this setting existed.
     */
    public function shouldAutoLock(Carbon $registerDate, ?Carbon $asOf = null): bool
    {
        $days = (int) ($this->lock_after_days ?? 0);

        if ($days <= 0) {
            return false;
        }

        return $registerDate->copy()->startOfDay()->addDays($days)
            ->lessThanOrEqualTo(($asOf ?? now())->copy()->startOfDay());
    }

    /**
     * The policy in force for a campus, in a session.
     *
     * A row naming the session wins over the campus default. Returns an unsaved
     * model when the campus has set nothing, whose defaults are the ordinary
     * six-day week — so a school that has configured nothing still gets a
     * sensible denominator rather than none at all.
     */
    public static function resolve(?int $campusId, ?int $sessionId = null): self
    {
        if (! $campusId) {
            return new self;
        }

        $policy = self::query()
            ->where('campus_id', $campusId)
            ->where('is_active', true)
            ->where(function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)
                    ->orWhereNull('session_id');
            })
            ->orderByRaw('session_id is null')
            ->first();

        return $policy ?? new self;
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
     * @return array<int, int>
     */
    public function workingWeekdays(): array
    {
        $days = $this->working_days;

        return empty($days) ? self::DEFAULT_WORKING_DAYS : array_map('intval', $days);
    }

    /**
     * @return array<int, int>
     */
    public function halfWeekdays(): array
    {
        return array_map('intval', $this->half_days ?? []);
    }

    /**
     * Whether the school opens its doors on this date's weekday.
     *
     * Holidays are a separate question, answered by `AttendanceService`.
     */
    public function isWorkingWeekday(Carbon $date): bool
    {
        return in_array((int) $date->isoWeekday(), $this->workingWeekdays(), true);
    }

    /**
     * Whether the school's day on this date is a short one.
     *
     * Deliberately **not** part of the attendance denominator. A child who
     * attends the whole of a short Friday has attended everything that was
     * asked of them, so the day is worth one expected day like any other —
     * halving it here would let a full attender finish above 100%.
     *
     * The school's short day and the child's half day are different things: the
     * second is a status on the register, weighted at 0.5.
     */
    public function isShortDay(Carbon $date): bool
    {
        return $this->isWorkingWeekday($date)
            && in_array((int) $date->isoWeekday(), $this->halfWeekdays(), true);
    }
}
