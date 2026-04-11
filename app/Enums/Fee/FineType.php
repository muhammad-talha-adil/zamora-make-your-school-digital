<?php

namespace App\Enums\Fee;

/**
 * Fine Type Enum
 * 
 * Defines how late payment fines are calculated.
 */
enum FineType: string
{
    case FIXED_PER_DAY = 'fixed_per_day';
    case FIXED_ONCE = 'fixed_once';
    case PERCENT = 'percent';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::FIXED_PER_DAY => 'Fixed Amount Per Day',
            self::FIXED_ONCE => 'Fixed Amount (One Time)',
            self::PERCENT => 'Percentage of Amount',
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
