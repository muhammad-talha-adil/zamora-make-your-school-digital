<?php

namespace App\Models\Fee;

use App\Enums\Fee\ApprovalStatus;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Student Discount Model
 *
 * Formal student-level discounts linked to discount types.
 */
class StudentDiscount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'student_enrollment_record_id',
        'discount_type_id',
        'fee_head_id',
        'value_type',
        'value',
        'effective_from',
        'effective_to',
        'approval_status',
        'approved_by',
        'reason',
    ];

    protected $casts = [
        'approval_status' => ApprovalStatus::class,
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
     * Get the discount type
     */
    public function discountType(): BelongsTo
    {
        return $this->belongsTo(DiscountType::class);
    }

    /**
     * Get the fee head (optional)
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
     * Scope: Approved discounts
     */
    public function scopeApproved($query)
    {
        return $query->where('approval_status', ApprovalStatus::APPROVED);
    }

    /**
     * Scope: Pending discounts
     */
    public function scopePending($query)
    {
        return $query->where('approval_status', ApprovalStatus::PENDING);
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
}
