<?php

namespace App\Enums\Fee;

/**
 * Voucher Item Source Enum
 * 
 * Tracks the origin/source of a voucher line item.
 */
enum VoucherItemSource: string
{
    case STRUCTURE = 'structure';
    case OVERRIDE = 'override';
    case MANUAL = 'manual';
    case ARREARS = 'arrears';
    case ADVANCE_ADJUSTMENT = 'advance_adjustment';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::STRUCTURE => 'Fee Structure',
            self::OVERRIDE => 'Student Override',
            self::MANUAL => 'Manual Entry',
            self::ARREARS => 'Arrears/Previous Balance',
            self::ADVANCE_ADJUSTMENT => 'Advance Adjustment',
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
