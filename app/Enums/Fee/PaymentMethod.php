<?php

namespace App\Enums\Fee;

/**
 * Payment Method Enum
 *
 * Defines available payment methods in Pakistani schools.
 */
enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case ONLINE = 'online';
    case JAZZCASH = 'jazzcash';
    case EASYPAISA = 'easypaisa';
    case CHEQUE = 'cheque';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK => 'Bank Transfer',
            self::ONLINE => 'Online Payment',
            self::JAZZCASH => 'JazzCash',
            self::EASYPAISA => 'EasyPaisa',
            self::CHEQUE => 'Cheque',
        };
    }

    /**
     * Check if method requires reference number
     */
    public function requiresReference(): bool
    {
        return match ($this) {
            self::CASH => false,
            default => true,
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
