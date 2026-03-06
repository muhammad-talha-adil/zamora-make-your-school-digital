<?php

namespace App\Models\Exam;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamRevaluationRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'exam_result_line_id',
        'requested_by',
        'reason',
        'status',
    ];

    /**
     * Get the result line for this request.
     */
    public function examResultLine(): BelongsTo
    {
        return $this->belongsTo(ExamResultLine::class);
    }

    /**
     * Get the user who requested this revaluation.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    /**
     * Get all actions for this request.
     */
    public function examRevaluationActions(): HasMany
    {
        return $this->hasMany(ExamRevaluationAction::class);
    }
}
