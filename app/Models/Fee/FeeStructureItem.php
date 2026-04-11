<?php

namespace App\Models\Fee;

use App\Enums\Fee\FeeFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fee Structure Item Model
 * 
 * Detail lines for fee structures with amounts and billing rules.
 */
class FeeStructureItem extends Model
{
    protected $fillable = [
        'fee_structure_id',
        'fee_head_id',
        'amount',
        'frequency',
        'applicable_on_admission',
        'billing_month_id',
        'billing_year',
        'starts_from_month_id',
        'ends_at_month_id',
        'is_optional',
        'is_transport_related',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'frequency' => FeeFrequency::class,
        'applicable_on_admission' => 'boolean',
        'billing_month_id' => 'integer',
        'billing_year' => 'integer',
        'starts_from_month_id' => 'integer',
        'ends_at_month_id' => 'integer',
        'is_optional' => 'boolean',
        'is_transport_related' => 'boolean',
    ];

    /**
     * Get the parent fee structure
     */
    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    /**
     * Get the fee head
     */
    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }

    /**
     * Get the billing month
     */
    public function billingMonth(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Month::class, 'billing_month_id');
    }

    /**
     * Get the start month
     */
    public function startsFromMonth(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Month::class, 'starts_from_month_id');
    }

    /**
     * Get the end month
     */
    public function endsAtMonth(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Month::class, 'ends_at_month_id');
    }

    /**
     * Check if item is applicable for a given month
     */
    public function isApplicableForMonth(int $monthNumber): bool
    {
        // If no month range specified, assume always applicable
        if (!$this->starts_from_month_id && !$this->ends_at_month_id) {
            return true;
        }

        // Get month numbers from relationships
        $startsFromMonthNumber = $this->startsFromMonth?->month_number;
        $endsAtMonthNumber = $this->endsAtMonth?->month_number;

        // Handle year-spanning ranges (e.g., Aug to Jun)
        if ($startsFromMonthNumber && $endsAtMonthNumber) {
            if ($startsFromMonthNumber <= $endsAtMonthNumber) {
                // Same year range (e.g., Jan to Jun)
                return $monthNumber >= $startsFromMonthNumber && $monthNumber <= $endsAtMonthNumber;
            } else {
                // Year-spanning range (e.g., Aug to Jun)
                return $monthNumber >= $startsFromMonthNumber || $monthNumber <= $endsAtMonthNumber;
            }
        }

        return true;
    }

    /**
     * Scope: By frequency
     */
    public function scopeByFrequency($query, FeeFrequency $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Scope: Optional items
     */
    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    /**
     * Scope: Applicable on admission
     */
    public function scopeApplicableOnAdmission($query)
    {
        return $query->where('applicable_on_admission', true);
    }
}
