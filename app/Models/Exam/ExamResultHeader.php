<?php

namespace App\Models\Exam;

use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamResultHeader extends Model
{
    use SoftDeletes;

    /*
     * The workflow a result moves through, in order. It only ever moves
     * forward: a result that has been verified, published or locked is not
     * knocked back by somebody editing a mark. Reopening one is a deliberate
     * act — see `ExamMarkingService::statusFor()`.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_LOCKED = 'locked';

    /**
     * The statuses a mark may still be written against without a deliberate
     * reopening.
     *
     * @var array<int, string>
     */
    public const OPEN_STATUSES = [self::STATUS_DRAFT, self::STATUS_SUBMITTED];

    /*
     * Whether the child passed. Separate from `status`, which is how far the
     * result has got through the office — a draft result can already be a fail,
     * and a published one can still be pending if a paper was never marked.
     */
    public const RESULT_PASS = 'pass';

    public const RESULT_FAIL = 'fail';

    /** A counted paper is still unmarked: not finished, so not failed. */
    public const RESULT_PENDING = 'pending';

    protected $fillable = [
        'exam_id',
        'student_id',
        'campus_id',
        'class_id',
        'section_id',
        'status',
        'total_obtained_cache',
        'overall_percentage_cache',
        'overall_grade_item_id_cache',
        'result_status',
        'failed_subject_count',
        'position_in_section',
        'position_in_class',
        'ranked_out_of',
        'remarks',
        'is_locked',
    ];

    protected $casts = [
        'total_obtained_cache' => 'decimal:2',
        'overall_percentage_cache' => 'decimal:2',
        'failed_subject_count' => 'integer',
        'is_locked' => 'boolean',
    ];

    /**
     * Get the exam for this result header.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the campus for this result header.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the class for this result header.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for this result header.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class)->withDefault();
    }

    /**
     * Get the student for this result header.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get all result lines for this header.
     */
    public function examResultLines(): HasMany
    {
        return $this->hasMany(ExamResultLine::class, 'result_header_id');
    }

    /**
     * Get the overall grade item for this result header.
     */
    public function overallGradeItem(): BelongsTo
    {
        return $this->belongsTo(GradeSystemItem::class, 'overall_grade_item_id_cache');
    }

    /**
     * Get total obtained marks from all result lines.
     */
    public function getTotalObtainedMarksAttribute(): ?float
    {
        return $this->examResultLines()
            ->where('is_absent', false)
            ->where('is_exempt', false)
            ->sum('obtained_marks');
    }

    /**
     * Get total max marks from all result lines.
     */
    public function getTotalMaxMarksAttribute(): ?float
    {
        return $this->examResultLines()
            ->where('is_absent', false)
            ->where('is_exempt', false)
            ->sum('total_marks_snapshot');
    }

    /**
     * Calculate and update the header totals.
     */
    public function recalculateTotals(): void
    {
        $lines = $this->examResultLines;

        $totalObtained = $lines
            ->where('is_absent', false)
            ->where('is_exempt', false)
            ->sum('obtained_marks');

        $totalMax = $lines
            ->where('is_absent', false)
            ->where('is_exempt', false)
            ->sum('total_marks_snapshot');

        $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : null;

        $this->update([
            'total_obtained_cache' => $totalObtained,
            'overall_percentage_cache' => $percentage,
        ]);
    }

    /**
     * Narrows a list to what this user may see.
     *
     * The policy guards one record; this filters a list. They are two halves of
     * the same rule and the tests assert they agree — a policy without a scope
     * means every list screen leaks what the record screen refuses.
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
}
