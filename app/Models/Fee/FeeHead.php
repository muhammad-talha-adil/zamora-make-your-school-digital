<?php

namespace App\Models\Fee;

use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\FeeHeadCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fee Head Model
 * 
 * Master table for all fee heads (Tuition, Admission, Transport, Fine, etc.)
 */
class FeeHead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'category',
        'is_recurring',
        'default_frequency',
        'is_optional',
        'sort_order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'category' => FeeHeadCategory::class,
        'is_recurring' => 'boolean',
        'default_frequency' => FeeFrequency::class,
        'is_optional' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get fee structure items using this fee head
     */
    public function feeStructureItems(): HasMany
    {
        return $this->hasMany(FeeStructureItem::class);
    }

    /**
     * Get student fee assignments for this fee head
     */
    public function studentFeeAssignments(): HasMany
    {
        return $this->hasMany(StudentFeeAssignment::class);
    }

    /**
     * Get student discounts for this fee head
     */
    public function studentDiscounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    /**
     * Get voucher items for this fee head
     */
    public function voucherItems(): HasMany
    {
        return $this->hasMany(FeeVoucherItem::class);
    }

    /**
     * Get fine rules for this fee head
     */
    public function fineRules(): HasMany
    {
        return $this->hasMany(FeeFineRule::class);
    }

    /**
     * Scope: Active fee heads only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, FeeHeadCategory $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Optional fee heads
     */
    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    /**
     * Scope: Recurring fee heads
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
