<?php

namespace App\Models\Fee;

use App\Enums\Fee\ValueType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Discount Type Model
 *
 * Master table for standard discount/concession types.
 */
class DiscountType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'value_type',
        'default_value',
        'description',
        'is_active',
        'requires_approval',
    ];

    protected $casts = [
        'value_type' => ValueType::class,
        'default_value' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    /**
     * Get student discounts using this type
     */
    public function studentDiscounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    /**
     * Scope: Active discount types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
