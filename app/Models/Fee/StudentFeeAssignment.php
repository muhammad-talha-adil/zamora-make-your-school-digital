<?php

namespace App\Models\Fee;

use App\Enums\Fee\AssignmentType;
use App\Enums\Fee\ValueType;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Student Fee Assignment Model
 * 
 * Student-specific fee overrides, extra charges, waivers, or custom settings.
 */
class StudentFeeAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'student_enrollment_record_id',
        'session_id',
        'campus_id',
        'class_id',
        'section_id',
        'fee_head_id',
        'assignment_type',
        'value_type',
        'amount',
        'effective_from',
        'effective_to',
        'is_active',
        'reason',
        'approved_by',
    ];

    protected $casts = [
        'assignment_type' => AssignmentType::class,
        'value_type' => ValueType::class,
        'amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the enrollment record
     */
    public function enrollmentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollmentRecord::class, 'student_enrollment_record_id');
    }

    /**
     * Get the session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the campus
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the class
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the fee head
     */
    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }

    /**
     * Get the approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: Active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Effective on date
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    /**
     * Scope: By assignment type
     */
    public function scopeByType($query, AssignmentType $type)
    {
        return $query->where('assignment_type', $type);
    }
}
