<?php

namespace App\Models\Fee;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fee Payment Allocation Model
 * 
 * Allocates one payment across one or multiple vouchers.
 */
class FeePaymentAllocation extends Model
{
    protected $fillable = [
        'fee_payment_id',
        'fee_voucher_id',
        'allocated_amount',
        'allocation_date',
        'notes',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'allocation_date' => 'date',
    ];

    /**
     * Get the payment
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'fee_payment_id');
    }

    /**
     * Get the voucher
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }
}
