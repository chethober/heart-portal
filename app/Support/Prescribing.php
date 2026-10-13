<?php

namespace App\Support;

/**
 * Coded vocabularies for prescription items and the drug-name normalization the search uses.
 * Codes are stored; labels are the English clinical terms printed on the prescription.
 */
final class Prescribing
{
    /**
     * @var array<string, string>
     */
    public const ROUTES = [
        'PO' => 'Oral',
        'SL' => 'Sublingual',
        'IV' => 'Intravenous',
        'IM' => 'Intramuscular',
        'SC' => 'Subcutaneous',
        'INH' => 'Inhaled',
        'TD' => 'Transdermal',
        'TOP' => 'Topical',
    ];

    /**
     * @var array<string, string>
     */
    public const FREQUENCIES = [
        'QD' => 'Once daily',
        'BID' => 'Twice daily',
        'TID' => 'Three times daily',
        'QID' => 'Four times daily',
        'Q6H' => 'Every 6 hours',
        'Q8H' => 'Every 8 hours',
        'Q12H' => 'Every 12 hours',
        'QHS' => 'At bedtime',
        'QAM' => 'Every morning',
        'QOD' => 'Every other day',
        'QW' => 'Once weekly',
        'PRN' => 'As needed',
        'STAT' => 'Once, immediately',
    ];

    /**
     * Dose units.
     *
     * @var list<string>
     */
    public const UNITS = ['mg', 'mcg', 'g', 'mL', 'unit', 'tablet', 'capsule', 'puff', 'drop', 'patch', 'sachet', 'vial', 'ampoule'];

    /**
     * Dosage forms of catalog drugs.
     *
     * @var list<string>
     */
    public const FORMS = ['tablet', 'capsule', 'oral solution', 'injection', 'infusion', 'sublingual tablet', 'spray', 'patch', 'inhaler', 'ointment', 'sachet'];

    public static function routeLabel(?string $code): ?string
    {
        return $code === null ? null : (self::ROUTES[$code] ?? $code);
    }

    public static function frequencyLabel(?string $code): ?string
    {
        return $code === null ? null : (self::FREQUENCIES[$code] ?? $code);
    }

    /**
     * Folds the letter variants heart folded before searching (Arabic yeh/kaf, alef with madda),
     * Persian/Arabic digits and case, so a name typed on any keyboard matches.
     */
    public static function normalize(string $text): string
    {
        $text = strtr($text, [
            'ي' => 'ی', 'ئ' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'آ' => 'ا', 'أ' => 'ا', 'إ' => 'ا', 'ة' => 'ه', "\u{200C}" => ' ',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }
}
