<?php

namespace App\Models\Staff;

use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one person is paid under one head, and from when.
 *
 * **A raise is a new row, not an edit.** A payroll run for June has to be able
 * to say what the allowance was in June, and it cannot if July's increase
 * overwrote it.
 *
 * These hang off the **person**, never off a job — that is Phase 0's decision
 * written into the shape: one salary for the person, and a second-job allowance
 * for a school that wants to pay for the second job separately.
 */
class StaffSalaryComponent extends Model
{
    protected $fillable = [
        'staff_profile_id', 'salary_head_id', 'amount',
        'effective_from', 'effective_to', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return BelongsTo<SalaryHead, $this>
     */
    public function salaryHead(): BelongsTo
    {
        return $this->belongsTo(SalaryHead::class);
    }

    /**
     * What was in force on a given day.
     *
     * The question every payroll run asks, and the reason the dates exist.
     */
    public function scopeInForceOn($query, $date)
    {
        return $query->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            });
    }
}
