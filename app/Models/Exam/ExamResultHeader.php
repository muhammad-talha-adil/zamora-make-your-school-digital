<?php

namespace App\Models\Exam;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamResultHeader extends Model
{
    use SoftDeletes;

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
        'is_locked',
    ];

    protected $casts = [
        'total_obtained_cache' => 'decimal:2',
        'overall_percentage_cache' => 'decimal:2',
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
        return $this->belongsTo(\App\Models\Campus::class);
    }

    /**
     * Get the class for this result header.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for this result header.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Section::class)->withDefault();
    }

    /**
     * Get the student for this result header.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
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
}
