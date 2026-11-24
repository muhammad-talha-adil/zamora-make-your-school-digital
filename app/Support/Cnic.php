<?php

namespace App\Support;

/**
 * One place that knows how a CNIC is stored: 35202-1234567-1. Thirteen bare
 * digits are dashed; anything else is left as typed so validation can name it.
 */
class Cnic
{
    public const PATTERN = '/^[0-9]{5}-[0-9]{7}-[0-9]$/';

    public const EXAMPLE = '35202-1234567-1';

    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) === 13) {
            return substr($digits, 0, 5).'-'.substr($digits, 5, 7).'-'.substr($digits, 12);
        }

        return $value;
    }
}
