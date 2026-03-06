<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'registration_no',
        'student_code',
        'admission_no',
        'dob',
        'gender_id',
        'b_form',
        'student_status_id',
        'description',
        'admission_date',
        'image',
    ];

    protected $casts = [
        'dob' => 'date',
        'admission_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function studentStatus(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardians')
            ->withPivot(['id', 'relation_id', 'is_primary'])
            ->withTimestamps();
    }

    public function studentGuardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    /**
     * Get student's full name (from related User model)
     */
    public function getNameAttribute(): string
    {
        return $this->user?->name ?? 'Student #' . $this->registration_no;
    }

    /**
     * Get student's registration number (alias for registration_no)
     */
    public function getRegistrationNumberAttribute(): string
    {
        return $this->registration_no;
    }

    /**
     * Get student's current class name from active enrollment
     */
    public function getClassAttribute(): ?string
    {
        $enrollment = $this->enrollmentRecords()
            ->active()
            ->with('class')
            ->first();
        
        return $enrollment?->class?->name;
    }

    /**
     * Get student's current section name from active enrollment
     */
    public function getSectionAttribute(): ?string
    {
        $enrollment = $this->enrollmentRecords()
            ->active()
            ->with('section')
            ->first();
        
        return $enrollment?->section?->name;
    }

    /**
     * Get the student's inventory assignments.
     */
    public function studentInventories(): HasMany
    {
        return $this->hasMany(StudentInventory::class);
    }

    /**
     * Get admission fees for the student.
     */
    public function admissionFees(): HasMany
    {
        return $this->hasMany(AdmissionFee::class);
    }

    /**
     * Get primary guardian (father or mother)
     */
    public function primaryGuardian(): BelongsToMany
    {
        return $this->guardians()->where('is_primary', true);
    }

    /**
     * Get the student's leave records.
     * This relationship tracks all departure events for the student.
     */
    public function leaveRecords(): HasMany
    {
        return $this->hasMany(StudentLeaveRecord::class);
    }

    /**
     * Get the student's enrollment records.
     * This relationship tracks all enrollment periods (admission and re-admission).
     */
    public function enrollmentRecords(): HasMany
    {
        return $this->hasMany(StudentEnrollmentRecord::class);
    }

    /**
     * Get the student's currently active enrollment.
     */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollmentRecord::class)
            ->whereNull('leave_date');
    }

    /**
     * Get the student's full enrollment history ordered by date (newest first).
     */
    public function enrollmentHistory(): HasMany
    {
        return $this->hasMany(StudentEnrollmentRecord::class)
            ->orderBy('admission_date', 'desc');
    }

    /**
     * Get the student's leaves.
     */
    public function studentLeaves(): HasMany
    {
        return $this->hasMany(StudentLeave::class);
    }

    /**
     * Get the student's attendance records.
     */
    public function attendanceStudents(): HasMany
    {
        return $this->hasMany(AttendanceStudent::class);
    }

    /**
     * Get the student's attendance summaries.
     */
    public function attendanceSummaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class);
    }
}
