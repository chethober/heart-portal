<?php

namespace App\Support;

use DateTimeInterface;
use IntlDateFormatter;

/**
 * Formats timestamps as Jalali (Solar Hijri) dates, in the hospital display
 * timezone, with Latin digits to match how dates are typed on the form.
 */
class JalaliDate
{
    public static function format(?DateTimeInterface $date, string $pattern = 'yyyy/MM/dd HH:mm'): string
    {
        if ($date === null) {
            return '';
        }

        $formatter = new IntlDateFormatter(
            'en_US@calendar=persian',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            config('echo.display_timezone'),
            IntlDateFormatter::TRADITIONAL,
            $pattern,
        );

        return (string) $formatter->format($date);
    }
}
