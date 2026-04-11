<?php

namespace App\Enums\Fee;

/**
 * Payment Status Enum
 * 
 * Tracks the status of a payment transaction.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case POSTED = 'posted';
    case REVERSED = 'reversed';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::POSTED => 'Posted',
            self::REVERSED => 'Reversed',
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
