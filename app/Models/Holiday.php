<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Holiday extends Model
{
    /**
     * Recurrence types.
     */
    public const RECURRENCE_NONE = 'none';
    public const RECURRENCE_YEARLY = 'yearly';
    public const RECURRENCE_MONTHLY = 'monthly';
    public const RECURRENCE_WEEKLY = 'weekly';

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'campus_id',
        'is_national',
        'description',
        'recurrence_type',
        'recurrence_end_date',
        'is_attendance_allowed',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'recurrence_end_date' => 'date',
        'is_national' => 'boolean',
        'is_attendance_allowed' => 'boolean',
    ];

    /**
     * Get the campus this holiday belongs to (nullable for national holidays).
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Check if this is a national holiday.
     */
    public function isNational(): bool
    {
        return $this->is_national;
    }

    /**
     * Check if attendance is allowed on this holiday.
     */
    public function isAttendanceAllowed(): bool
    {
        return $this->is_attendance_allowed ?? false;
    }

    /**
     * Check if this is a recurring holiday.
     */
    public function isRecurring(): bool
    {
        return $this->recurrence_type !== self::RECURRENCE_NONE && $this->recurrence_type !== null;
    }

    /**
     * Get the number of days for this holiday.
     */
    public function getDaysCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Check if a specific date falls on this holiday (including recurring).
     */
    public function includesDate(string $date): bool
    {
        $checkDate = Carbon::parse($date);

        // Check simple date range
        if ($checkDate->betweenInclusive($this->start_date, $this->end_date)) {
            return true;
        }

        // Check recurring holidays
        if ($this->isRecurring()) {
            return $this->checkRecurringDate($checkDate);
        }

        return false;
    }

    /**
     * Check if a date matches a recurring holiday pattern.
     */
    private function checkRecurringDate(Carbon $date): bool
    {
        $recurrenceEnd = $this->recurrence_end_date 
            ? Carbon::parse($this->recurrence_end_date) 
            : $date->addYears(10); // Default to 10 years if not set

        if ($date->greaterThan($recurrenceEnd)) {
            return false;
        }

        // Check based on recurrence type
        switch ($this->recurrence_type) {
            case self::RECURRENCE_YEARLY:
                return $date->dayOfYear === $this->start_date->dayOfYear;

            case self::RECURRENCE_MONTHLY:
                return $date->day === $this->start_date->day;

            case self::RECURRENCE_WEEKLY:
                return $date->dayOfWeek === $this->start_date->dayOfWeek;

            default:
                return false;
        }
    }

    /**
     * Get all dates this holiday applies to within a range.
     */
    public function getOccurrencesInRange(Carbon $start, Carbon $end): array
    {
        $occurrences = [];

        // Add initial occurrence if in range
        if ($this->start_date->betweenInclusive($start, $end)) {
            $occurrences[] = $this->start_date->format('Y-m-d');
        }

        // Handle recurring holidays
        if ($this->isRecurring()) {
            $recurrenceEnd = $this->recurrence_end_date 
                ? Carbon::parse($this->recurrence_end_date)->min($end)
                : $end;

            $current = $this->start_date->copy();

            while ($current->lessThanOrEqualTo($recurrenceEnd)) {
                // Move to next occurrence
                switch ($this->recurrence_type) {
                    case self::RECURRENCE_YEARLY:
                        $current->addYear();
                        break;
                    case self::RECURRENCE_MONTHLY:
                        $current->addMonth();
                        break;
                    case self::RECURRENCE_WEEKLY:
                        $current->addWeek();
                        break;
                }

                if ($current->betweenInclusive($start, $end) && $current->lessThanOrEqualTo($recurrenceEnd)) {
                    $occurrences[] = $current->format('Y-m-d');
                }
            }
        }

        return $occurrences;
    }

    /**
     * Boot method to clear cache when holidays are modified.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($holiday) {
            $holiday->clearCache();
        });

        static::updated(function ($holiday) {
            $holiday->clearCache();
        });

        static::deleted(function ($holiday) {
            $holiday->clearCache();
        });
    }

    /**
     * Clear the holiday cache.
     */
    public function clearCache(): void
    {
        // Clear all holiday-related caches
        $cacheKeys = [
            'holiday:*',
            'attendance_status_ids',
            'attendance_statuses',
        ];

        foreach ($cacheKeys as $pattern) {
            // Use Cache::flush() for pattern in production
            // For now, we'll clear all caches
            \Illuminate\Support\Facades\Cache::flush();
        }
    }
}
