<?php

namespace App\Enums\Fee;

/**
 * Wallet Transaction Type Enum
 *
 * Defines types of transactions in student fee wallet (advance payments).
 */
enum WalletTransactionType: string
{
    case ADVANCE_DEPOSIT = 'advance_deposit';
    case VOUCHER_ADJUSTMENT = 'voucher_adjustment';
    case REFUND = 'refund';
    case MANUAL_CREDIT = 'manual_credit';
    case MANUAL_DEBIT = 'manual_debit';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ADVANCE_DEPOSIT => 'Advance Deposit',
            self::VOUCHER_ADJUSTMENT => 'Voucher Adjustment',
            self::REFUND => 'Refund',
            self::MANUAL_CREDIT => 'Manual Credit',
            self::MANUAL_DEBIT => 'Manual Debit',
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
