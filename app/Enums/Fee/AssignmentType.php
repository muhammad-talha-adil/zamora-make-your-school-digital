<?php

namespace App\Enums\Fee;

/**
 * Assignment Type Enum
 * 
 * Defines types of student-specific fee assignments/overrides.
 */
enum AssignmentType: string
{
    case OVERRIDE = 'override';
    case DISCOUNT = 'discount';
    case EXTRA_CHARGE = 'extra_charge';
    case WAIVER = 'waiver';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::OVERRIDE => 'Fee Override',
            self::DISCOUNT => 'Discount',
            self::EXTRA_CHARGE => 'Extra Charge',
            self::WAIVER => 'Waiver',
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
