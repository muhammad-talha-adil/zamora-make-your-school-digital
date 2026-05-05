<?php

namespace App\Models\Fee;

use App\Enums\Fee\VoucherItemSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fee Voucher Item Model
 *
 * Line items breakdown for each voucher.
 */
class FeeVoucherItem extends Model
{
    protected $fillable = [
        'fee_voucher_id',
        'fee_head_id',
        'description',
        'amount',
        'discount_amount',
        'fine_amount',
        'adjusted_amount',
        'net_amount',
        'source_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'fine_amount' => 'decimal:2',
        'adjusted_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'source_type' => VoucherItemSource::class,
        'reference_id' => 'integer',
    ];

    /**
     * Get the parent voucher
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }

    /**
     * Get the fee head
     */
    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }

    /**
     * Scope: By source type
     */
    public function scopeBySource($query, VoucherItemSource $source)
    {
        return $query->where('source_type', $source);
    }
}
