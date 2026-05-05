<?php

namespace App\Models\Exam;

use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
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
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the class for this registration.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for this registration.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class)->withDefault();
    }

    /**
     * Get the student for this registration.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the enrollment for this registration.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollmentRecord::class, 'enrollment_id');
    }
}
