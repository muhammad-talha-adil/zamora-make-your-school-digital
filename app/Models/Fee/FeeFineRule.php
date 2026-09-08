<?php

namespace App\Models\Fee;

use App\Enums\Fee\FineType;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fee Fine Rule Model
 *
 * Late payment fine rules.
 */
class FeeFineRule extends Model
{
    protected $fillable = [
        'name',
        'campus_id',
        'session_id',
        'class_id',
        'section_id',
        'fee_head_id',
        'fine_type',
        'fine_value',
        'initial_amount',
        'daily_amount',
        'max_fine_amount',
        'grace_days',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'fine_type' => FineType::class,
        'fine_value' => 'decimal:2',
        'initial_amount' => 'decimal:2',
        'daily_amount' => 'decimal:2',
        'max_fine_amount' => 'decimal:2',
        'grace_days' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the campus
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the class (nullable)
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section (nullable)
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the fee head (nullable)
     */
    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }

    /**
     * What this rule charges on a voucher that is late.
     *
     * The cap applies to every kind of rule, not only to slabs: an uncapped
     * per-day fine on a voucher nobody chased grows past the fee itself, which
     * is the one outcome no school actually wants.
     */
    public function calculateFine(float $voucherAmount, int $daysLate): float
    {
        if ($daysLate <= $this->grace_days) {
            return 0.0;
        }

        $effectiveDays = $daysLate - $this->grace_days;

        $fine = match ($this->fine_type) {
            FineType::FIXED_PER_DAY => (float) $this->fine_value * $effectiveDays,
            FineType::FIXED_ONCE => (float) $this->fine_value,
            FineType::PERCENT => ($voucherAmount * (float) $this->fine_value) / 100,

            // The fixed part lands on the first day past grace; the daily part
            // counts from the day after that, so a rule of "Rs 200 then Rs 50 a
            // day" charges Rs 200 on day one, not Rs 250.
            FineType::SLAB => (float) $this->initial_amount
                + ((float) $this->daily_amount * ($effectiveDays - 1)),
        };

        if ($this->max_fine_amount !== null) {
            $fine = min($fine, (float) $this->max_fine_amount);
        }

        return round(max($fine, 0.0), 2);
    }

    /**
     * Scope: Active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Effective on date
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }
}
