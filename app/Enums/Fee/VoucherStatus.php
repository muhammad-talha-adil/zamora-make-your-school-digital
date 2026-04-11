<?php

namespace App\Enums\Fee;

/**
 * Voucher Status Enum
 * 
 * Tracks the lifecycle status of a fee voucher/challan.
 */
enum VoucherStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case ADJUSTED = 'adjusted';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::UNPAID => 'Unpaid',
            self::PARTIAL => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::ADJUSTED => 'Adjusted',
        };
    }

    /**
     * Get color class for UI
     */
    public function color(): string
    {
        return match($this) {
            self::UNPAID => 'yellow',
            self::PARTIAL => 'blue',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
            self::ADJUSTED => 'purple',
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
