<?php

namespace App\Models\Fee;

use App\Enums\Fee\PaymentMethod;
use App\Enums\Fee\PaymentStatus;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fee Payment Model
 * 
 * Payment receipt header - records actual money received.
 */
class FeePayment extends Model
{
    use SoftDeletes;

    protected $table = 'fee_payments';

    protected $fillable = [
        'receipt_no',
        'student_id',
        'student_enrollment_record_id',
        'campus_id',
        'payment_date',
        'payment_method',
        'reference_no',
        'bank_name',
        'received_amount',
        'excess_amount',
        'allocated_amount',
        'remaining_unallocated_amount',
        'status',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_method' => PaymentMethod::class,
        'received_amount' => 'decimal:2',
        'excess_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'remaining_unallocated_amount' => 'decimal:2',
        'status' => PaymentStatus::class,
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the campus
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Campus::class);
    }

    /**
     * Get the enrollment record
     */
    public function enrollmentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollmentRecord::class, 'student_enrollment_record_id');
    }

    /**
     * Get the receiver
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get payment allocations
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(FeePaymentAllocation::class);
    }

    /**
     * Check if payment is fully allocated
     */
    public function isFullyAllocated(): bool
    {
        return $this->remaining_unallocated_amount <= 0;
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, PaymentStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: By payment method
     */
    public function scopeByMethod($query, PaymentMethod $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope: Posted payments
     */
    public function scopePosted($query)
    {
        return $query->where('status', PaymentStatus::POSTED);
    }
}
