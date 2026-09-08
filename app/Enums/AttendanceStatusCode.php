<?php

namespace App\Enums;

/**
 * The attendance statuses the system itself knows about.
 *
 * These four codes plus the half day were declared as string constants on
 * **two** models at once — `Attendance` and `AttendanceStudent` each carried
 * their own copy — and compared as bare strings in a dozen places. Every other
 * module here states its fixed values as a backed enum, and three separate live
 * bugs in the fee module came from comparing an uncast string against one.
 *
 * What this is **not**: a cast for `attendance_statuses.code`. That column stays
 * a plain string on purpose. A school can add its own status — "Short Leave" at
 * a weight of 0.5, say — and casting the column would throw a `ValueError` the
 * moment such a row was read. The enum names the codes the application reasons
 * about; the table holds every code the school actually uses.
 */
enum AttendanceStatusCode: string
{
    case PRESENT = 'P';
    case ABSENT = 'A';
    case LEAVE = 'L';
    case LATE = 'LT';
    case HALF_DAY = 'HD';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::LEAVE => 'Leave',
            self::LATE => 'Late',
            self::HALF_DAY => 'Half Day',
        };
    }

    /**
     * The share of a day this status counts as present.
     *
     * The authority is `attendance_statuses.weight`, so a school can change it.
     * This is only the figure the seeder starts from, and the answer for the
     * seeded statuses when no row is to hand.
     */
    public function defaultWeight(): float
    {
        return match ($this) {
            // The child was in class; the lateness is recorded to be chased,
            // not to dock the attendance.
            self::PRESENT, self::LATE => 1.0,
            self::HALF_DAY => 0.5,
            self::ABSENT, self::LEAVE => 0.0,
        };
    }

    /**
     * Whether an absence on this status is worth telling a guardian about.
     */
    public function warrantsAlert(): bool
    {
        return $this === self::ABSENT;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
