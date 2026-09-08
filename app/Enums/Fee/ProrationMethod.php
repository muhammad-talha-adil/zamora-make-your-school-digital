<?php

namespace App\Enums\Fee;

/**
 * How a child admitted part-way through a month is charged for that month.
 *
 * Schools here differ, so this is a setting rather than a rule: some charge the
 * full month whatever the date, some halve it after the middle of the month,
 * and some count the days.
 */
enum ProrationMethod: string
{
    case NONE = 'none';
    case HALF_MONTH = 'half_month';
    case DAILY = 'daily';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Full month, whatever the admission date',
            self::HALF_MONTH => 'Half month after the cut-off day',
            self::DAILY => 'Charged by the day',
        };
    }

    /**
     * The share of a month's charge a child admitted on this date owes.
     *
     * @param  int  $cutoffDay  the day from which a half month is charged
     */
    public function shareOfMonth(\DateTimeInterface $admittedOn, int $cutoffDay): float
    {
        $day = (int) $admittedOn->format('j');
        $daysInMonth = (int) $admittedOn->format('t');

        return match ($this) {
            self::NONE => 1.0,
            self::HALF_MONTH => $day < $cutoffDay ? 1.0 : 0.5,

            // Counting the admission day itself, so a child admitted on the
            // first of the month owes the whole of it.
            self::DAILY => ($daysInMonth - $day + 1) / $daysInMonth,
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
