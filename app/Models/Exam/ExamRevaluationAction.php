<?php

namespace App\Models\Exam;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamRevaluationAction extends Model
{
    protected $fillable = [
        'request_id',
        'action_by',
        'old_marks',
        'new_marks',
        'note',
    ];

    protected $casts = [
        'old_marks' => 'decimal:2',
        'new_marks' => 'decimal:2',
    ];

    /**
     * Get the revaluation request for this action.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ExamRevaluationRequest::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
