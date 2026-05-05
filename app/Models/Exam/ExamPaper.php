<?php

namespace App\Models\Exam;

use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamPaper extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'exam_id',
        'subject_id',
        'campus_id',
        'class_id',
        'section_id',
        'scope_type',
        'paper_date',
        'start_time',
        'end_time',
        'total_marks',
        'passing_marks',
        'paper_weight',
        'status',
    ];

    protected $casts = [
        'paper_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'total_marks' => 'decimal:2',
        'passing_marks' => 'decimal:2',
        'paper_weight' => 'decimal:3',
    ];

    /**
     * Get the exam that owns this paper.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the campus for this paper (optional).
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the class for this paper (optional for CLASS/SCHOOL scope).
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for this paper (only for SECTION scope).
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class)->withDefault();
    }

    /**
     * Get the subject for this paper.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get all result lines for this paper.
     */
    public function examResultLines(): HasMany
    {
        return $this->hasMany(ExamResultLine::class);
    }

    /**
     * Check if paper is for whole school.
     */
    public function isSchoolScope(): bool
    {
        return $this->scope_type === 'SCHOOL';
    }

    /**
     * Check if paper is for a specific class.
     */
    public function isClassScope(): bool
    {
        return $this->scope_type === 'CLASS';
    }

    /**
     * Check if paper is for a specific section.
     */
    public function isSectionScope(): bool
    {
        return $this->scope_type === 'SECTION';
    }
}
