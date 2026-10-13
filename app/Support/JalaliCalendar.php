<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use IntlDateFormatter;

/**
 * Calendar arithmetic on Jalali dates stored as entered ('1405/07/01'): validity,
 * today's date in the hospital timezone, days between two dates and age in years.
 */
final class JalaliCalendar
{
    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * Trimmed input with Latin digits, and dates zero-padded so they sort as text
     * ('۱۴۰۵/۷/۱' → '1405/07/01'). Empty input becomes null.
     */
    public static function normalize(string $value): ?string
    {
        $value = trim(strtr($value, self::DIGITS));

        if (preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $value, $parts)) {
            return sprintf('%04d/%02d/%02d', $parts[1], $parts[2], $parts[3]);
        }

        return $value === '' ? null : $value;
    }

    /**
     * The date as a Gregorian day, or null when it is not a real Jalali date.
     */
    public static function toDate(?string $jalali): ?CarbonImmutable
    {
        if ($jalali === null || ! preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $jalali, $parts)) {
            return null;
        }

        $formatter = new IntlDateFormatter('en_US@calendar=persian', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'UTC', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');
        $formatter->setLenient(false);
        $timestamp = $formatter->parse(sprintf('%04d/%02d/%02d', $parts[1], $parts[2], $parts[3]));

        return $timestamp === false ? null : CarbonImmutable::createFromTimestampUTC((int) $timestamp)->startOfDay();
    }

    public static function isValid(?string $jalali): bool
    {
        return self::toDate($jalali) !== null;
    }

    /**
     * Today's Jalali date in the hospital display timezone.
     */
    public static function today(): string
    {
        return JalaliDate::format(now(), 'yyyy/MM/dd');
    }

    /**
     * Whole days from one date to another (today when $to is null), or null if either is invalid.
     */
    public static function daysBetween(?string $from, ?string $to = null): ?int
    {
        $start = self::toDate($from);
        $end = self::toDate($to ?? self::today());

        return $start && $end ? (int) $start->diffInDays($end, false) : null;
    }

    /**
     * Completed years from a date of birth to today.
     */
    public static function age(?string $dob): ?int
    {
        $birth = self::toDate($dob);
        $today = self::toDate(self::today());

        return $birth && $today && $birth <= $today ? (int) $birth->diffInYears($today) : null;
    }
}
