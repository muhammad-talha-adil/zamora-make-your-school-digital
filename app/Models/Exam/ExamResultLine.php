<?php

namespace App\Models\Exam;

use App\Enums\Exam\SubjectRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamResultLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'result_header_id',
        'exam_paper_id',
        'subject_role',
        'obtained_marks',
        'grace_marks',
        'grace_reason',
        'grace_by',
        'grace_at',
        'is_pass',
        'is_absent',
        'is_exempt',
        'remarks',
        'total_marks_snapshot',
        'passing_marks_snapshot',
        'percentage_cache',
        'grade_item_id_cache',
    ];

    protected $casts = [
        'subject_role' => SubjectRole::class,
        'obtained_marks' => 'decimal:2',
        'grace_marks' => 'decimal:2',
        'grace_at' => 'datetime',
        'is_pass' => 'boolean',
        'is_absent' => 'boolean',
        'is_exempt' => 'boolean',
        'total_marks_snapshot' => 'decimal:2',
        'passing_marks_snapshot' => 'decimal:2',
        'percentage_cache' => 'decimal:2',
    ];

    /**
     * Get the result header for this line.
     */
    public function resultHeader(): BelongsTo
    {
        return $this->belongsTo(ExamResultHeader::class);
    }

    /**
     * Get the exam paper for this line.
     */
    public function examPaper(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class);
    }

    /**
     * Get the grade item for this result line.
     */
    public function gradeItem(): BelongsTo
    {
        return $this->belongsTo(GradeSystemItem::class, 'grade_item_id_cache');
    }

    /**
     * Get the revaluation request for this result line (one active request per line).
     */
    public function revaluationRequest(): HasOne
    {
        return $this->hasOne(ExamRevaluationRequest::class);
    }

    /**
     * The mark that goes on the result card.
     *
     * What the child wrote, plus whatever grace the school gave. Grace is kept
     * in its own column and never merged into `obtained_marks`, so the school
     * can always answer "you scored 31, we gave 2" when a parent compares the
     * card with the answer sheet.
     */
    public function effectiveMarks(): ?float
    {
        if ($this->obtained_marks === null) {
            return $this->grace_marks !== null ? (float) $this->grace_marks : null;
        }

        return (float) $this->obtained_marks + (float) ($this->grace_marks ?? 0);
    }

    /**
     * Whether this paper is part of the total, the percentage and the position.
     *
     * An exempt paper is one the child was never required to sit. An additional
     * subject is one they sat for its own sake. Neither counts, and they are
     * two different statements.
     */
    public function countsTowardsTotal(): bool
    {
        return ! $this->is_exempt
            && ($this->subject_role ?? SubjectRole::Core)->counts();
    }
}
