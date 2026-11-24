<?php

namespace App\Support;

/**
 * One place that knows how phone numbers are stored: Pakistani mobiles as
 * 0333-1112233. Anything that is not a mobile is left as typed (trimmed).
 */
class Phone
{
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '0092')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '92') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '0'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '03')) {
            return substr($digits, 0, 4).'-'.substr($digits, 4);
        }

        return $value;
    }

    /**
     * Normalises the named fields of a request payload in place.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    public static function normalizeFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $data[$field] = self::normalize($data[$field]);
            }
        }

        return $data;
    }
}
