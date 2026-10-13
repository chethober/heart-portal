<?php

namespace App\Support;

use App\Models\Patient;

/**
 * Iranian national ID (کد ملی) check digit, as heart checked it on its forms (isValidPNC):
 * ten digits, the last being the check digit of the first nine with weights 10..2, mod 11.
 */
final class NationalId
{
    public static function isValid(string $nationalId): bool
    {
        $id = Patient::normalizeNationalId($nationalId);

        if (! preg_match('/^\d{10}$/', $id) || preg_match('/^(\d)\1{9}$/', $id)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $id[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $check = (int) $id[9];

        return $remainder < 2 ? $check === $remainder : $check === 11 - $remainder;
    }
}
