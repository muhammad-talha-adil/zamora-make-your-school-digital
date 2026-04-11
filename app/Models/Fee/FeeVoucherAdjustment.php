<?php

namespace App\Models\Fee;

use App\Enums\Fee\AdjustmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fee Voucher Adjustment Model
 * 
 * Voucher-level adjustments like arrears, advance, waiver, fine reversal.
 */
class FeeVoucherAdjustment extends Model
{
    protected $fillable = [
        'fee_voucher_id',
        'adjustment_type',
        'amount',
        'description',
        'related_voucher_id',
        'created_by',
    ];

    protected $casts = [
        'adjustment_type' => AdjustmentType::class,
        'amount' => 'decimal:2',
    ];

    /**
     * Get the parent voucher
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }

    /**
     * Get the related voucher (for arrears/advance)
     */
    public function relatedVoucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'related_voucher_id');
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: By adjustment type
     */
    public function scopeByType($query, AdjustmentType $type)
    {
        return $query->where('adjustment_type', $type);
    }
}
