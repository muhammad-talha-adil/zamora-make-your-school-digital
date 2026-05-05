<?php

namespace App\Models\Fee;

use App\Enums\Fee\VoucherStatus;
use App\Models\Campus;
use App\Models\Month;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fee Voucher Model
 *
 * Voucher/challan header - the core transaction document.
 */
class FeeVoucher extends Model
{
    use SoftDeletes;

    protected $table = 'fee_vouchers';

    protected $fillable = [
        'voucher_no',
        'student_id',
        'student_enrollment_record_id',
        'session_id',
        'campus_id',
        'class_id',
        'section_id',
        'voucher_month_id',
        'voucher_year',
        'issue_date',
        'due_date',
        'status',
        'gross_amount',
        'discount_amount',
        'fine_amount',
        'paid_amount',
        'net_amount',
        'balance_amount',
        'advance_adjusted_amount',
        'previous_voucher_ids',
        'notes',
        'generated_by',
        'published_at',
    ];

    protected $casts = [
        'voucher_month_id' => 'integer',
        'voucher_year' => 'integer',
        'issue_date' => 'date',
        'due_date' => 'date',
        'status' => VoucherStatus::class,
        'gross_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'advance_adjusted_amount' => 'decimal:2',
        'previous_voucher_ids' => 'array',
        'published_at' => 'datetime',
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
     * Get the generator
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the voucher month
     */
    public function voucherMonth(): BelongsTo
    {
        return $this->belongsTo(Month::class, 'voucher_month_id');
    }

    /**
     * Get voucher items
     */
    public function items(): HasMany
    {
        return $this->hasMany(FeeVoucherItem::class);
    }

    /**
     * Get voucher adjustments
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(FeeVoucherAdjustment::class);
    }

    /**
     * Get payment allocations
     */
    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(FeePaymentAllocation::class);
    }

    /**
     * Get print logs
     */
    public function printLogs(): HasMany
    {
        return $this->hasMany(FeeVoucherPrintLog::class);
    }

    /**
     * Get payments through payment allocations
     */
    public function payments()
    {
        return $this->hasManyThrough(FeePayment::class, FeePaymentAllocation::class, 'fee_voucher_id', 'id', 'id', 'fee_payment_id');
    }

    /**
     * Check if voucher is fully paid
     */
    public function isPaid(): bool
    {
        return $this->status === VoucherStatus::PAID;
    }

    /**
     * Check if voucher is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === VoucherStatus::OVERDUE
            || ($this->due_date < now() && $this->balance_amount > 0);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, VoucherStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Unpaid vouchers
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [VoucherStatus::UNPAID, VoucherStatus::PARTIAL, VoucherStatus::OVERDUE]);
    }

    /**
     * Scope: For month and year
     */
    public function scopeForPeriod($query, int $monthId, int $year)
    {
        return $query->where('voucher_month_id', $monthId)
            ->where('voucher_year', $year);
    }

    /**
     * Scope: Published vouchers
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }
}
