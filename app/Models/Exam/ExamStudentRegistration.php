<?php

namespace App\Models\Exam;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamStudentRegistration extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'campus_id',
        'class_id',
        'section_id',
        'enrollment_id',
        'roll_no_snapshot',
        'status',
    ];

    /**
     * Get the exam for this registration.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the campus for this registration.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Campus::class);
    }

    /**
     * Get the class for this registration.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(\App\Models\SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for this registration.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Section::class)->withDefault();
    }

    /**
     * Get the student for this registration.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    /**
     * Get the enrollment for this registration.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\StudentEnrollmentRecord::class, 'enrollment_id');
    }
}
