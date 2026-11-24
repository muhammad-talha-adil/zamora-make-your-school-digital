<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRunItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payroll_run_id',
        'staff_profile_id',
        'gross_salary',
        'allowance_amount',
        'deduction_amount',
        'net_salary',
        'amount_paid',
        'advance_deduction_amount',
        'status',
        'payment_method',
        'reference_no',
        'paid_at',
        'notes',
    ];

    protected $appends = ['total_deduction_amount'];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'allowance_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'advance_deduction_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function getTotalDeductionAmountAttribute(): float
    {
        return $this->totalDeductionAmount();
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function advanceDeductions(): HasMany
    {
        return $this->hasMany(StaffAdvanceDeduction::class);
    }

    /**
     * Every deduction taken from this item: the regular ones plus the advance
     * instalment. `net_salary` is already net of both, so
     * gross + allowances - totalDeductionAmount() === net_salary.
     */
    public function totalDeductionAmount(): float
    {
        return round((float) $this->deduction_amount + (float) $this->advance_deduction_amount, 2);
    }

    /**
     * What is still owed on this item: the net salary (already after the
     * advance instalment) minus what has actually been paid so far.
     */
    public function amountDue(): float
    {
        return max(round((float) $this->net_salary - (float) $this->amount_paid, 2), 0.0);
    }
}
