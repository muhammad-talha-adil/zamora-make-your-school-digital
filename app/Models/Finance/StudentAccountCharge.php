<?php

namespace App\Models\Finance;

use App\Models\Campus;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
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

class StudentAccountCharge extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'student_enrollment_record_id',
        'campus_id',
        'session_id',
        'class_id',
        'section_id',
        'source_module',
        'source_type',
        'source_id',
        'source_item_id',
        'charge_category',
        'title',
        'description',
        'charge_date',
        'billing_period_month_id',
        'billing_period_year',
        'due_date',
        'amount',
        'discount_amount',
        'fine_amount',
        'net_amount',
        'status',
        'billing_status',
        'voucher_id',
        'voucher_item_id',
        'is_recurring',
        'meta',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'charge_date' => 'date',
        'due_date' => 'date',
        'billing_period_year' => 'integer',
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'meta' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollmentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollmentRecord::class, 'student_enrollment_record_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function billingMonth(): BelongsTo
    {
        return $this->belongsTo(Month::class, 'billing_period_month_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'voucher_id');
    }

    public function voucherItem(): BelongsTo
    {
        return $this->belongsTo(FeeVoucherItem::class, 'voucher_item_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StudentAccountAdjustment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
