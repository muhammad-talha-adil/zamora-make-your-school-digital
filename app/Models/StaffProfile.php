<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_no',
        'campus_id',
        'department_id',
        'designation_id',
        'employment_type',
        'hire_date',
        'confirmation_date',
        'basic_salary',
        'allowance_amount',
        'deduction_amount',
        'payment_method',
        'bank_name',
        'account_no',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'confirmation_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowance_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class, 'department_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(StaffDesignation::class, 'designation_id');
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float) $this->basic_salary + (float) $this->allowance_amount;
    }

    public function getNetSalaryAttribute(): float
    {
        return max(0, $this->gross_salary - (float) $this->deduction_amount);
    }
}
