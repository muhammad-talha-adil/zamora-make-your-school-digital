<?php

namespace App\Enums\Fee;

/**
 * Wallet Direction Enum
 * 
 * Defines direction of wallet transaction (credit or debit).
 */
enum WalletDirection: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::CREDIT => 'Credit',
            self::DEBIT => 'Debit',
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
