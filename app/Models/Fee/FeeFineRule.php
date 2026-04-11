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
        'campus_id',
        'session_id',
        'class_id',
        'section_id',
        'fee_head_id',
        'fine_type',
        'fine_value',
        'grace_days',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'fine_type' => FineType::class,
        'fine_value' => 'decimal:2',
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
     * Calculate fine amount
     */
    public function calculateFine(float $voucherAmount, int $daysLate): float
    {
        if ($daysLate <= $this->grace_days) {
            return 0;
        }

        $effectiveDays = $daysLate - $this->grace_days;

        return match($this->fine_type) {
            FineType::FIXED_PER_DAY => $this->fine_value * $effectiveDays,
            FineType::FIXED_ONCE => $this->fine_value,
            FineType::PERCENT => ($voucherAmount * $this->fine_value) / 100,
        };
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
