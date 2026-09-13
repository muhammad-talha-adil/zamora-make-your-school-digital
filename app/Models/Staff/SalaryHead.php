<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named part of a salary.
 *
 * `staff_profiles.allowance_amount` is one lump sum, so a school that is asked
 * "what is this two thousand for?" has no answer. Heads are the answer: house
 * rent, conveyance, a second-job allowance, provident fund, income tax.
 *
 * The heads are a list the **school** maintains, so this is a plain string
 * column and not an enum — the rule from docs/WORKING-RULES.md. Only `type`
 * decides behaviour, and it has exactly two values.
 */
class SalaryHead extends Model
{
    public const TYPE_ALLOWANCE = 'allowance';

    public const TYPE_DEDUCTION = 'deduction';

    protected $fillable = [
        'name', 'code', 'type', 'is_part_of_gross',
        'description', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_part_of_gross' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @return HasMany<StaffSalaryComponent, $this>
     */
    public function components(): HasMany
    {
        return $this->hasMany(StaffSalaryComponent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isDeduction(): bool
    {
        return $this->type === self::TYPE_DEDUCTION;
    }
}
