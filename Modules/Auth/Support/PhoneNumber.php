<?php

namespace Modules\Auth\Support;

/**
 * Normalizes the Egyptian mobile number formats THOB accepts into a single
 * canonical E.164 form (`+20XXXXXXXXXX`) used for every lookup and for storage.
 */
class PhoneNumber
{
    /**
     * Accepted operator prefixes for Egyptian mobile numbers, i.e. the two
     * digits following the country code (010, 011, 012, 015 without the 0).
     */
    private const MOBILE_PREFIXES = ['10', '11', '12', '15'];

    /**
     * Normalize a raw phone string, or return null when it is not a valid
     * Egyptian mobile number in one of the accepted formats.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        // 01XXXXXXXXX  ->  20 1XXXXXXXXX
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '20'.substr($digits, 1);
        }

        // 1XXXXXXXXX  ->  20 1XXXXXXXXX  (missing both country code and leading 0)
        if (strlen($digits) === 10 && $digits[0] === '1') {
            $digits = '20'.$digits;
        }

        if (strlen($digits) !== 12 || ! str_starts_with($digits, '20')) {
            return null;
        }

        $subscriber = substr($digits, 2); // 10XXXXXXXX

        if (! in_array(substr($subscriber, 0, 2), self::MOBILE_PREFIXES, true)) {
            return null;
        }

        return '+'.$digits;
    }

    /**
     * Whether the given raw string is an accepted Egyptian mobile number.
     */
    public static function isValid(?string $raw): bool
    {
        return self::normalize($raw) !== null;
    }
}
