<?php

namespace App\Enums\Fee;

/**
 * Fee Head Category Enum
 *
 * Defines what a fee head is charged for (tuition, transport, fine, etc).
 * How often it is billed is a separate concern, held by FeeFrequency.
 */
enum FeeHeadCategory: string
{
    case TUITION = 'tuition';
    case ADMISSION = 'admission';
    case ANNUAL_CHARGES = 'annual_charges';
    case EXAMINATION = 'examination';
    case TRANSPORT = 'transport';
    case LIBRARY = 'library';
    case LABORATORY = 'laboratory';
    case SPORTS = 'sports';
    case SECURITY = 'security';
    case FINE = 'fine';
    case DISCOUNT = 'discount';
    case MISC = 'misc';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::TUITION => 'Tuition Fee',
            self::ADMISSION => 'Admission / Registration Fee',
            self::ANNUAL_CHARGES => 'Annual Charges',
            self::EXAMINATION => 'Examination Fee',
            self::TRANSPORT => 'Transport Fee',
            self::LIBRARY => 'Library Fee',
            self::LABORATORY => 'Laboratory / Computer Fee',
            self::SPORTS => 'Sports / Co-curricular Fee',
            self::SECURITY => 'Security / Caution Money',
            self::FINE => 'Fine / Late Fee',
            self::DISCOUNT => 'Discount / Concession',
            self::MISC => 'Miscellaneous',
        };
    }

    /**
     * The billing frequency this category is normally charged at. The office
     * can still override it per fee head — this is only a starting suggestion.
     */
    public function defaultFrequency(): FeeFrequency
    {
        return match ($this) {
            self::TUITION => FeeFrequency::MONTHLY,
            self::ADMISSION => FeeFrequency::ONCE,
            self::ANNUAL_CHARGES => FeeFrequency::YEARLY,
            self::EXAMINATION => FeeFrequency::ONCE,
            self::TRANSPORT => FeeFrequency::MONTHLY,
            self::LIBRARY => FeeFrequency::YEARLY,
            self::LABORATORY => FeeFrequency::YEARLY,
            self::SPORTS => FeeFrequency::YEARLY,
            self::SECURITY => FeeFrequency::ONCE,
            self::FINE => FeeFrequency::ONCE,
            self::DISCOUNT => FeeFrequency::MONTHLY,
            self::MISC => FeeFrequency::MONTHLY,
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
