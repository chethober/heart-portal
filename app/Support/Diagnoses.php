<?php

namespace App\Support;

/**
 * The final-diagnosis list (config/diagnoses.php), ported from heart's ICD guide.
 */
final class Diagnoses
{
    /**
     * @return array<string, array<string, array{label: string, icd10: string, criteria: string}>> Group => key => entry.
     */
    public static function groups(): array
    {
        return config('diagnoses');
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, array{label: string, icd10: string, criteria: string, group: string}>
     */
    public static function all(): array
    {
        $all = [];
        foreach (self::groups() as $group => $entries) {
            foreach ($entries as $key => $entry) {
                $all[$key] = [...$entry, 'group' => $group];
            }
        }

        return $all;
    }

    /**
     * "Acute NSTEMI (I21.4)", or the key itself for one no longer in the list.
     */
    public static function label(string $key): string
    {
        $entry = self::all()[$key] ?? null;

        return $entry ? "{$entry['label']} ({$entry['icd10']})" : $key;
    }

    /**
     * Labels of the chosen keys, in the order they were chosen.
     *
     * @param  array<int, string>|null  $keys
     * @return list<string>
     */
    public static function labels(?array $keys): array
    {
        return array_values(array_map(self::label(...), $keys ?? []));
    }

    /**
     * Keys for ICD-10 codes as heart stored them on an admission (e.g. "I21.4, I48.0"): each known
     * code maps to the first diagnosis with that code; unknown codes are left out.
     *
     * @return list<string>
     */
    public static function fromIcdCodes(?string $codes): array
    {
        $byCode = [];
        foreach (self::all() as $key => $entry) {
            $byCode[strtoupper($entry['icd10'])] ??= $key;
        }

        $keys = [];
        foreach (preg_split('/[\s,;|]+/', strtoupper((string) $codes), -1, PREG_SPLIT_NO_EMPTY) as $code) {
            if (isset($byCode[$code])) {
                $keys[] = $byCode[$code];
            }
        }

        return array_values(array_unique($keys));
    }
}
