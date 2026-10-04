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

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'allowance_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'advance_deduction_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

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
     * What is still owed on this item: the net salary, minus whatever advance
     * installment already reduced what is payable, minus what has actually
     * been paid so far.
     */
    public function amountDue(): float
    {
        $payable = (float) $this->net_salary - (float) $this->advance_deduction_amount;

        return max(round($payable - (float) $this->amount_paid, 2), 0.0);
    }
}
