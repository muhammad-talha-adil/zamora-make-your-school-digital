<?php

namespace App\Models\Fee;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fee Voucher Print Log Model
 * 
 * Tracks voucher/challan print history for audit purposes.
 */
class FeeVoucherPrintLog extends Model
{
    protected $fillable = [
        'fee_voucher_id',
        'printed_by',
        'printed_at',
        'print_count',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'print_count' => 'integer',
    ];

    /**
     * Get the voucher
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }

    /**
     * Get the printer
     */
    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
