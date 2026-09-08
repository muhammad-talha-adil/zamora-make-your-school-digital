<?php

namespace App\Models\Fee;

use App\Enums\Fee\FeeFrequency;
use App\Models\Month;
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
        'instalment_count',
        'instalment_month_ids',
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
        'instalment_count' => 'integer',
        'instalment_month_ids' => 'array',
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
        return $this->belongsTo(Month::class, 'billing_month_id');
    }

    /**
     * Get the start month
     */
    public function startsFromMonth(): BelongsTo
    {
        return $this->belongsTo(Month::class, 'starts_from_month_id');
    }

    /**
     * Get the end month
     */
    public function endsAtMonth(): BelongsTo
    {
        return $this->belongsTo(Month::class, 'ends_at_month_id');
    }

    /**
     * Whether this charge is collected in more than one instalment.
     */
    public function isInstalled(): bool
    {
        return ($this->instalment_count ?? 1) > 1 && ! empty($this->instalment_month_ids);
    }

    /**
     * The month numbers the instalments fall in, in order.
     *
     * @return array<int, int>
     */
    public function instalmentMonthNumbers(): array
    {
        if (! $this->isInstalled()) {
            return [];
        }

        return Month::whereIn('id', $this->instalment_month_ids)
            ->orderBy('month_number')
            ->pluck('month_number')
            ->all();
    }

    /**
     * What one instalment charges in the given month.
     *
     * The amount is split evenly and the final instalment absorbs whatever the
     * division left over, so the instalments always add back up to the charge —
     * a Rs 10,000 annual fee in three parts is 3,333.33 + 3,333.33 + 3,333.34,
     * never Rs 9,999.99.
     */
    public function instalmentAmountForMonth(int $monthNumber): ?float
    {
        $months = $this->instalmentMonthNumbers();
        $position = array_search($monthNumber, $months, true);

        if ($position === false) {
            return null;
        }

        $total = round((float) $this->amount, 2);
        $count = count($months);
        $each = round($total / $count, 2);

        return $position === $count - 1
            ? round($total - ($each * ($count - 1)), 2)
            : $each;
    }

    /**
     * Check if item is applicable for a given month
     */
    public function isApplicableForMonth(int $monthNumber): bool
    {
        // If no month range specified, assume always applicable
        if (! $this->starts_from_month_id && ! $this->ends_at_month_id) {
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
