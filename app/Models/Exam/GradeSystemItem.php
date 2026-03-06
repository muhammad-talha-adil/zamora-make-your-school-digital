<?php

namespace App\Models\Exam;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSystemItem extends Model
{
    protected $fillable = [
        'grade_system_id',
        'min_percentage',
        'max_percentage',
        'grade_letter',
        'grade_point',
        'sort_order',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'grade_point' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the grade system that owns this item.
     */
    public function gradeSystem(): BelongsTo
    {
        return $this->belongsTo(GradeSystem::class);
    }
}
