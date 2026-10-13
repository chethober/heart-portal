<?php

namespace App\Support\HeartImport;

/**
 * Turns raw values read from heart's MySQL tables or the hospital information
 * system into what Echo Portal stores. Every method returns null for "no value".
 */
final class LegacyValue
{
    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * Trimmed text with Latin digits; null when empty.
     */
    public static function text(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $text = trim(strtr((string) $value, self::DIGITS));

        return $text === '' ? null : $text;
    }

    /**
     * Free text as written (digits untouched), trimmed; null when empty.
     */
    public static function string(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * Jalali date as YYYY/MM/DD. Heart stores DateOf_* columns as YYYYMMDD
     * (its addSlashToShamsiDate() adds the slashes for display); the HIS sends
     * "YYYY/MM/DD HH:MM". Anything else is not a date.
     */
    public static function date(mixed $value): ?string
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        if (preg_match('#^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})(?!\d)#', $text, $m)
            || preg_match('#^(\d{4})(\d{2})(\d{2})(?!\d)#', $text, $m)) {
            [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];

            if ($year > 0 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return sprintf('%04d/%02d/%02d', $year, $month, $day);
            }
        }

        return null;
    }

    /**
     * Time of day as HH:MM (MySQL TIME columns arrive as HH:MM:SS).
     */
    public static function time(mixed $value): ?string
    {
        $text = self::text($value);

        if ($text !== null && preg_match('/^(\d{1,2}):(\d{2})/', $text, $m) && (int) $m[1] < 24 && (int) $m[2] < 60) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return null;
    }

    public static function number(mixed $value): ?float
    {
        $text = self::text($value);

        return $text !== null && is_numeric($text) ? (float) $text : null;
    }

    /**
     * Heart's tri-state checkboxes store 1 (ticked), 0 (unticked) or 9 (not answered).
     */
    public static function boolean(mixed $value): ?bool
    {
        return match (strtolower((string) self::text($value))) {
            '1', 'on', 'true', 'yes', 'y' => true,
            '0', 'false', 'no', 'n' => false,
            default => null,
        };
    }

    /**
     * A choice code as a string ("1", not "1.00" from a DECIMAL column), or
     * null when it is one of the field's placeholder codes.
     *
     * @param  list<string>  $blank
     */
    public static function code(mixed $value, array $blank = []): ?string
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        if (is_numeric($text) && (float) $text === floor((float) $text)) {
            $text = (string) (int) $text;
        }

        return in_array($text, $blank, true) ? null : $text;
    }

    /**
     * Phone number from heart's DECIMAL columns, which drop the leading zero
     * and add ".00".
     */
    public static function phone(mixed $value): ?string
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        $text = preg_replace('/\.0+$/', '', $text);
        $digits = preg_replace('/[^\d+]/', '', $text);

        if ($digits === '' || trim($digits, '0') === '') {
            return null;
        }

        // Iranian mobile numbers stored as numbers lose their leading zero.
        return preg_match('/^9\d{9}$/', $digits) ? '0'.$digits : $digits;
    }

    /**
     * Heart's gender codes (1 male, 2 female); the HIS may send words instead.
     */
    public static function gender(mixed $value): ?string
    {
        return match (mb_strtolower((string) self::code($value))) {
            '1', 'm', 'male', 'مرد', 'مذکر' => 'Male',
            '2', 'f', 'female', 'زن', 'مونث', 'مؤنث' => 'Female',
            default => null,
        };
    }
}
