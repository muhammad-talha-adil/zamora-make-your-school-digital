<?php

namespace App\Models;

use App\Enums\AttendanceStatusCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    /**
     * Worked out once per model; see `statusCounts()`.
     *
     * @var array<string, int>|null
     */
    private ?array $statusCounts = null;

    protected $fillable = [
        'attendance_date',
        'campus_id',
        'session_id',
        'class_id',
        'section_id',
        'taken_by',
        'is_locked',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'is_locked' => 'boolean',
    ];

    /**
     * Get the campus for this attendance.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the session for this attendance.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * Get the class for this attendance.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for this attendance.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the user who took this attendance.
     */
    public function takenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by');
    }

    /**
     * Get all student attendance records for this attendance session.
     */
    public function attendanceStudents(): HasMany
    {
        return $this->hasMany(AttendanceStudent::class);
    }

    /**
     * Get the total number of students in this attendance session.
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->relationLoaded('attendanceStudents')
            ? $this->attendanceStudents->count()
            : $this->attendanceStudents()->count();
    }

    /**
     * How many students hold each status on this register, keyed by code.
     *
     * One grouped query, held for the life of the model. Each of the counts
     * below used to run its own `whereHas` count, so a list of registers cost
     * three queries per row before anything else was asked of it.
     *
     * When the rows are already loaded — which the dashboard and the show
     * screen do — they are counted in memory and no query runs at all.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        if ($this->statusCounts !== null) {
            return $this->statusCounts;
        }

        if ($this->relationLoaded('attendanceStudents')) {
            return $this->statusCounts = $this->attendanceStudents
                ->groupBy(fn (AttendanceStudent $row) => $row->attendanceStatus?->code)
                ->map->count()
                ->filter(fn ($count, $code) => $code !== '')
                ->all();
        }

        return $this->statusCounts = AttendanceStudent::query()
            ->join('attendance_statuses', 'attendance_statuses.id', '=', 'attendance_students.attendance_status_id')
            ->where('attendance_students.attendance_id', $this->id)
            ->groupBy('attendance_statuses.code')
            ->pluck(DB::raw('count(*)'), 'attendance_statuses.code')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    public function countOf(AttendanceStatusCode $code): int
    {
        return $this->statusCounts()[$code->value] ?? 0;
    }

    public function getPresentCountAttribute(): int
    {
        return $this->countOf(AttendanceStatusCode::PRESENT);
    }

    public function getAbsentCountAttribute(): int
    {
        return $this->countOf(AttendanceStatusCode::ABSENT);
    }

    public function getLateCountAttribute(): int
    {
        return $this->countOf(AttendanceStatusCode::LATE);
    }

    public function getLeaveCountAttribute(): int
    {
        return $this->countOf(AttendanceStatusCode::LEAVE);
    }

    public function getHalfDayCountAttribute(): int
    {
        return $this->countOf(AttendanceStatusCode::HALF_DAY);
    }

    /**
     * Only the registers a user is allowed to see.
     *
     * The policy answers "may I open this one"; a list needs the same question
     * asked of every row at once, or a teacher's index quietly shows them every
     * class in the school and only refuses when they click.
     *
     * Kept beside the policy's `coversRegister()` on purpose: they must always
     * agree, and the tests assert that they do.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $campusId = $user->campusId();

        if ($campusId !== null) {
            $query->where('campus_id', $campusId);
        }

        if (! $user->isClassRestricted()) {
            return $query;
        }

        $assignments = $user->teachingAssignments()->active()->get(['class_id', 'section_id']);

        if ($assignments->isEmpty()) {
            // A teacher with no class yet sees nothing, rather than everything.
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($outer) use ($assignments) {
            foreach ($assignments as $assignment) {
                $outer->orWhere(function ($q) use ($assignment) {
                    $q->where('class_id', $assignment->class_id);

                    // A whole-class assignment covers every section in it.
                    if ($assignment->section_id !== null) {
                        $q->where('section_id', $assignment->section_id);
                    }
                });
            }
        });
    }

    /**
     * Scope for locked attendance records.
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope for unlocked attendance records.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Check if this attendance is locked.
     */
    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    /**
     * Lock this attendance record.
     */
    public function lock(): bool
    {
        return $this->update(['is_locked' => true]);
    }

    /**
     * Unlock this attendance record.
     */
    public function unlock(): bool
    {
        return $this->update(['is_locked' => false]);
    }
}
