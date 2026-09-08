<?php

namespace App\Models\Fee;

use App\Enums\Fee\ValueType;
use App\Models\Campus;
use App\Models\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Ten percent off the second child, twenty off the third."
 *
 * Keyed by the child's rank among the siblings currently enrolled, counting the
 * eldest as first — so the rule stops applying by itself when an elder sibling
 * finishes school and the younger one moves up.
 */
class FeeSiblingDiscountRule extends Model
{
    protected $fillable = [
        'campus_id',
        'session_id',
        'child_rank',
        'fee_head_id',
        'value_type',
        'value',
        'is_active',
    ];

    protected $casts = [
        'child_rank' => 'integer',
        'value_type' => ValueType::class,
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }

    /**
     * What this rule takes off a charge.
     *
     * Capped at the charge, so a voucher line cannot go negative — the same
     * rule the entered concessions follow.
     */
    public function amountOff(float $charge): float
    {
        $off = $this->value_type === ValueType::PERCENT
            ? ($charge * (float) $this->value) / 100
            : (float) $this->value;

        return round(min($off, $charge), 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
