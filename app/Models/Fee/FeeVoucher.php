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
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Fee Voucher Model
 *
 * Voucher/challan header - the core transaction document.
 */
class FeeVoucher extends Model
{
    use LogsActivity, SoftDeletes;

    /**
     * Status and money movement on the voucher, not every touch.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'paid_amount', 'balance_amount', 'net_amount', 'discount_amount', 'fine_amount', 'published_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "fee voucher {$eventName}");
    }

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
     * The number a bank cashier keys in, or a scanner reads off the challan.
     *
     * Fee here is collected over the counter at a bank, and the teller has no
     * access to the school's system: everything they need has to be on the slip
     * itself. The digits are the voucher's own id, the due date and the amount
     * in paisa, closed by a check digit, so a mistyped consumer number is
     * rejected at the counter rather than turning up as an unmatched deposit
     * the office has to chase.
     */
    public function challanReference(): string
    {
        $body = str_pad((string) $this->id, 8, '0', STR_PAD_LEFT)
            .($this->due_date ? $this->due_date->format('ymd') : '000000')
            .str_pad((string) (int) round((float) $this->balance_amount * 100), 10, '0', STR_PAD_LEFT);

        return $body.self::checkDigitFor($body);
    }

    /**
     * The challan reference in groups, which is how a teller reads it aloud.
     */
    public function challanReferenceFormatted(): string
    {
        return trim(chunk_split($this->challanReference(), 4, ' '));
    }

    /**
     * Luhn check digit — the same one printed on utility bills here, so bank
     * staff and scanners already know how to validate it.
     */
    public static function checkDigitFor(string $digits): int
    {
        $sum = 0;
        $double = true;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $value = (int) $digits[$i];

            if ($double) {
                $value *= 2;

                if ($value > 9) {
                    $value -= 9;
                }
            }

            $sum += $value;
            $double = ! $double;
        }

        return (10 - ($sum % 10)) % 10;
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

    /**
     * What this viewer's campus reach allows them to see.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $campusId = $user->campusId();

        if ($campusId === null) {
            return $query;
        }

        return $query->where('campus_id', $campusId);
    }
}
