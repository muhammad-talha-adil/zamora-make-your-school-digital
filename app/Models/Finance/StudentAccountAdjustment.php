<?php

namespace App\Models\Finance;

use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAccountAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'student_account_charge_id',
        'voucher_id',
        'voucher_item_id',
        'adjustment_type',
        'source_module',
        'source_id',
        'amount',
        'reason',
        'meta',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(StudentAccountCharge::class, 'student_account_charge_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'voucher_id');
    }

    public function voucherItem(): BelongsTo
    {
        return $this->belongsTo(FeeVoucherItem::class, 'voucher_item_id');
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
