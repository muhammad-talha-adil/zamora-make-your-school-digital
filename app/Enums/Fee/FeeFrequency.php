<?php

namespace App\Enums\Fee;

/**
 * Fee Frequency Enum
 * 
 * Defines how often a fee is charged.
 */
enum FeeFrequency: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case ONCE = 'once';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::MONTHLY => 'Monthly',
            self::YEARLY => 'Yearly',
            self::ONCE => 'One Time',
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
