<?php

namespace App\Enums\Fee;

/**
 * Adjustment Type Enum
 *
 * Defines types of voucher-level adjustments.
 */
enum AdjustmentType: string
{
    case ARREARS = 'arrears';
    case ADVANCE = 'advance';
    case WAIVER = 'waiver';
    case FINE_REVERSAL = 'fine_reversal';
    case MANUAL_CHARGE = 'manual_charge';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ARREARS => 'Arrears/Previous Balance',
            self::ADVANCE => 'Advance Payment',
            self::WAIVER => 'Waiver',
            self::FINE_REVERSAL => 'Fine Reversal',
            self::MANUAL_CHARGE => 'Manual Charge',
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
