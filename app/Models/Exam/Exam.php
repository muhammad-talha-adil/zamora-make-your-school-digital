<?php

namespace App\Models\Exam;

use App\Models\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use SoftDeletes;

    // Status constants
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_MARKING = 'marking';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_ACTIVE,
        self::STATUS_MARKING,
        self::STATUS_PUBLISHED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'session_id',
        'exam_type_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'publish_at',
        'published_at',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'publish_at' => 'datetime',
        'published_at' => 'datetime',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    // Append formatted dates to JSON output
    protected $appends = [
        'start_date_formatted',
        'end_date_formatted',
    ];

    /**
     * Get the exam type that owns this exam.
     */
    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    /**
     * Get the session that owns this exam.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get all papers for this exam.
     */
    public function examPapers(): HasMany
    {
        return $this->hasMany(ExamPaper::class);
    }

    /**
     * Get all registrations for this exam.
     */
    public function studentRegistrations(): HasMany
    {
        return $this->hasMany(ExamStudentRegistration::class);
    }

    /**
     * Get all result headers for this exam.
     */
    public function resultHeaders(): HasMany
    {
        return $this->hasMany(ExamResultHeader::class);
    }

    /**
     * Check if exam is published.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Check if exam is locked.
     */
    public function isLocked(): bool
    {
        return $this->is_locked === true;
    }

    /**
     * Get formatted start date (Y-m-d format)
     */
    public function getStartDateFormattedAttribute(): ?string
    {
        return $this->start_date ? $this->start_date->format('Y-m-d') : null;
    }

    /**
     * Get formatted end date (Y-m-d format)
     */
    public function getEndDateFormattedAttribute(): ?string
    {
        return $this->end_date ? $this->end_date->format('Y-m-d') : null;
    }

    /**
     * Calculate status based on exam dates
     */
    public function calculateStatus(): string
    {
        $today = now()->toDateString();
        $startDate = $this->start_date?->toDateString();
        $endDate = $this->end_date?->toDateString();

        // If exam has ended, it's completed
        if ($endDate && $endDate < $today) {
            return self::STATUS_COMPLETED;
        }

        // If exam has started (today is between start and end date)
        if ($startDate && $startDate <= $today && $endDate && $endDate >= $today) {
            return self::STATUS_ACTIVE;
        }

        // If start date is today
        if ($startDate && $startDate === $today) {
            return self::STATUS_ACTIVE;
        }

        // Otherwise it's scheduled
        return self::STATUS_SCHEDULED;
    }

    /**
     * Check if exam is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Scope to filter by active status
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to filter by scheduled status
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope to filter by marking status
     */
    public function scopeMarking($query)
    {
        return $query->where('status', self::STATUS_MARKING);
    }

    /**
     * Scope to filter by published status
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope to filter by completed status
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to filter by exam type
     */
    public function scopeOfType($query, int $examTypeId)
    {
        return $query->where('exam_type_id', $examTypeId);
    }

    /**
     * Scope to filter by session
     */
    public function scopeInSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
