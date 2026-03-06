<?php

namespace App\Models\Exam;

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
        'obtained_marks',
        'is_absent',
        'is_exempt',
        'remarks',
        'total_marks_snapshot',
        'passing_marks_snapshot',
        'percentage_cache',
        'grade_item_id_cache',
    ];

    protected $casts = [
        'obtained_marks' => 'decimal:2',
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
}
