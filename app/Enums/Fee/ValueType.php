<?php

namespace App\Enums\Fee;

/**
 * Value Type Enum
 * 
 * Defines whether a value is fixed amount or percentage.
 */
enum ValueType: string
{
    case FIXED = 'fixed';
    case PERCENT = 'percent';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::FIXED => 'Fixed Amount',
            self::PERCENT => 'Percentage',
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
