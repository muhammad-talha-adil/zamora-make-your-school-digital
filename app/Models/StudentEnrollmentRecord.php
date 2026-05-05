<?php

namespace App\Models;

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\StudentDiscount;
use App\Models\Fee\StudentFeeAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        // Fee structure integration
        'fee_structure_id',
        'fee_mode',
        'custom_fee_entries',
        'manual_discount_percentage',
        'manual_discount_reason',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'leave_date' => 'date',
        'monthly_fee' => 'decimal:2',
        'annual_fee' => 'decimal:2',
        'custom_fee_entries' => 'array',
        'manual_discount_percentage' => 'decimal:2',
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

    /**
     * Get fee vouchers for this enrollment.
     */
    public function feeVouchers(): HasMany
    {
        return $this->hasMany(FeeVoucher::class);
    }

    /**
     * Get fee payments for this enrollment.
     */
    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    /**
     * Get fee assignments for this enrollment.
     */
    public function feeAssignments(): HasMany
    {
        return $this->hasMany(StudentFeeAssignment::class);
    }

    /**
     * Get discounts for this enrollment.
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    /**
     * Get the fee structure for this enrollment.
     */
    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }
}
