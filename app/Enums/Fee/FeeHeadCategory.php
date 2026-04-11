<?php

namespace App\Enums\Fee;

/**
 * Fee Head Category Enum
 * 
 * Defines the main categories of fee heads in the system.
 * Used to classify different types of fees for reporting and processing.
 */
enum FeeHeadCategory: string
{
    case MONTHLY = 'monthly';
    case ANNUAL = 'annual';
    case ONE_TIME = 'one_time';
    case TRANSPORT = 'transport';
    case FINE = 'fine';
    case DISCOUNT = 'discount';
    case MISC = 'misc';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::MONTHLY => 'Monthly Fee',
            self::ANNUAL => 'Annual Fee',
            self::ONE_TIME => 'One-Time Charge',
            self::TRANSPORT => 'Transport Fee',
            self::FINE => 'Fine/Penalty',
            self::DISCOUNT => 'Discount/Concession',
            self::MISC => 'Miscellaneous',
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
