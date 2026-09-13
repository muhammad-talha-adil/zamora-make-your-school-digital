<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of leave, and what a school gives per year.
 *
 * `is_paid` is the one that reaches payroll: unpaid leave is a deduction, paid
 * leave is not.
 */
class StaffLeaveType extends Model
{
    protected $fillable = [
        'name', 'code', 'days_per_year', 'is_paid', 'requires_approval', 'is_active',
    ];

    protected $casts = [
        'days_per_year' => 'integer',
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<StaffLeave, $this>
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(StaffLeave::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
