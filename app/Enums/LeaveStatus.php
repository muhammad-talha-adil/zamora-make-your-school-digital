<?php

namespace App\Enums;

/**
 * Where a student's leave application stands.
 *
 * The model declared `'status' => 'enum'`, which is not a Laravel cast at all:
 * it threw an `InvalidCastException` the moment the attribute was read, so the
 * leave record could not be touched. The three values were already fixed by the
 * column; this states them once in PHP as well.
 */
enum LeaveStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Awaiting approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
