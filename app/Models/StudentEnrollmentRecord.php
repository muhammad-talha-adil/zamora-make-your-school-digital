<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentEnrollmentRecord extends Model
{
    protected $fillable = [
        'student_id',
        'session_id',
        'class_id',
        'section_id',
        'campus_id',
        'admission_date',
        'leave_date',
        'student_status_id',
        'previous_enrollment_id',
        'description',
        'monthly_fee',
        'annual_fee',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'leave_date' => 'date',
        'monthly_fee' => 'decimal:2',
        'annual_fee' => 'decimal:2',
    ];

    /**
     * Get the student that this enrollment record belongs to.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the academic session for this enrollment.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * Get the class for this enrollment.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for this enrollment.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the campus for this enrollment (nullable).
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the status associated with this enrollment record.
     */
    public function studentStatus(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class);
    }

    /**
     * Get the previous enrollment record (for re-admissions).
     */
    public function previousEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollmentRecord::class, 'previous_enrollment_id');
    }

    /**
     * Get the next enrollment record (for chaining).
     */
    public function nextEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollmentRecord::class, 'previous_enrollment_id');
    }

    /**
     * Check if this enrollment is currently active.
     */
    public function isActive(): bool
    {
        return is_null($this->leave_date);
    }

    /**
     * Scope for active enrollments.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('leave_date');
    }

    /**
     * Scope for a specific student.
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for a specific session.
     */
    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for a specific class.
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope for a specific section.
     */
    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }
}
