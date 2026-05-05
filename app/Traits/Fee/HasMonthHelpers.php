<?php

namespace App\Traits\Fee;

use App\Models\Month;
use Illuminate\Support\Collection;

/**
 * Month Helper Trait
 *
 * Provides helper methods for working with the months table in fee module.
 */
trait HasMonthHelpers
{
    /**
     * Get month ID by month number
     *
     * @param  int  $monthNumber  Month number (1-12)
     * @return int|null Month ID
     */
    public static function getMonthIdByNumber(int $monthNumber): ?int
    {
        return cache()->remember(
            "month_id_{$monthNumber}",
            now()->addDay(),
            fn () => Month::where('month_number', $monthNumber)->value('id')
        );
    }

    /**
     * Get month number by month ID
     *
     * @param  int  $monthId  Month ID
     * @return int|null Month number (1-12)
     */
    public static function getMonthNumberById(int $monthId): ?int
    {
        return cache()->remember(
            "month_number_{$monthId}",
            now()->addDay(),
            fn () => Month::where('id', $monthId)->value('month_number')
        );
    }

    /**
     * Get month name by month ID
     *
     * @param  int  $monthId  Month ID
     * @return string|null Month name
     */
    public static function getMonthNameById(int $monthId): ?string
    {
        return cache()->remember(
            "month_name_{$monthId}",
            now()->addDay(),
            fn () => Month::where('id', $monthId)->value('name')
        );
    }

    /**
     * Get month name by month number
     *
     * @param  int  $monthNumber  Month number (1-12)
     * @return string|null Month name
     */
    public static function getMonthNameByNumber(int $monthNumber): ?string
    {
        return cache()->remember(
            "month_name_number_{$monthNumber}",
            now()->addDay(),
            fn () => Month::where('month_number', $monthNumber)->value('name')
        );
    }

    /**
     * Get multiple month IDs by month numbers
     *
     * @param  array  $monthNumbers  Array of month numbers
     * @return array Array with month_number as key and id as value
     */
    public static function getMonthIds(array $monthNumbers): array
    {
        return cache()->remember(
            'month_ids_'.md5(json_encode($monthNumbers)),
            now()->addDay(),
            fn () => Month::whereIn('month_number', $monthNumbers)
                ->pluck('id', 'month_number')
                ->toArray()
        );
    }

    /**
     * Get all months as collection
     */
    public static function getAllMonths(): Collection
    {
        return cache()->remember(
            'all_months',
            now()->addDay(),
            fn () => Month::orderBy('month_number')->get()
        );
    }

    /**
     * Get months for dropdown/select
     *
     * @return array Array with id as key and name as value
     */
    public static function getMonthsForSelect(): array
    {
        return cache()->remember(
            'months_for_select',
            now()->addDay(),
            fn () => Month::orderBy('month_number')
                ->pluck('name', 'id')
                ->toArray()
        );
    }

    /**
     * Get academic year months (August to June)
     */
    public static function getAcademicYearMonths(): Collection
    {
        return cache()->remember(
            'academic_year_months',
            now()->addDay(),
            function () {
                // August (8) to December (12), then January (1) to June (6)
                return Month::whereIn('month_number', [8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6])
                    ->orderByRaw('CASE 
                        WHEN month_number >= 8 THEN month_number 
                        ELSE month_number + 12 
                    END')
                    ->get();
            }
        );
    }

    /**
     * Get current month ID
     */
    public static function getCurrentMonthId(): ?int
    {
        return self::getMonthIdByNumber((int) now()->format('n'));
    }

    /**
     * Check if month number is valid
     */
    public static function isValidMonthNumber(int $monthNumber): bool
    {
        return $monthNumber >= 1 && $monthNumber <= 12;
    }

    /**
     * Clear month cache
     */
    public static function clearMonthCache(): void
    {
        cache()->forget('all_months');
        cache()->forget('months_for_select');
        cache()->forget('academic_year_months');

        // Clear individual month caches
        for ($i = 1; $i <= 12; $i++) {
            cache()->forget("month_id_{$i}");
            cache()->forget("month_name_number_{$i}");
        }
    }
}
