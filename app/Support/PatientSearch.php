<?php

namespace App\Support;

use App\Models\Admission;
use App\Models\Hospital;
use App\Models\Patient;
use App\Support\HeartImport\HisAdmissionImporter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The patient search, ported from heart's Search() (include/workflow.php): a national ID (11 digits
 * with a leading 0 are read as one), an HIS admission number, or a name, written with Arabic or
 * Persian ی/ک and with or without spaces. When nothing matches, heart looked the patient up in the
 * hospital information system; so does this, for the hospital configured for it (heart.his).
 * Results are always the current hospital's (HospitalScope).
 */
final class PatientSearch
{
    public const LIMIT = 100;

    /**
     * Characters Postgres translate() maps to their Persian forms (ي→ی, ك→ک) or drops (spaces,
     * zero-width non-joiner), applied to both the stored names and the query.
     */
    private const FROM = "يكىة \u{200C}";

    private const TO = 'یکیه';

    /**
     * @return array{patients: Collection<int, Patient>, kind: 'national_id'|'admission'|'name'|'too_short', fromHis: bool}
     */
    public static function search(string $query): array
    {
        $query = trim(self::digits($query));

        if (mb_strlen(self::normalize($query)) < 2) {
            return ['patients' => new Collection, 'kind' => 'too_short', 'fromHis' => false];
        }

        $compact = preg_replace('/[\s\-]/u', '', $query);
        $nationalId = preg_match('/^0\d{10}$/', $compact) ? substr($compact, 1) : $compact;

        // Any input may be a national ID (foreign ones are not 10 digits); try that first.
        $patient = Patient::whereNationalId($nationalId)->first();
        if ($patient) {
            return ['patients' => new Collection([$patient]), 'kind' => 'national_id', 'fromHis' => false];
        }

        if (preg_match('/^\d+$/', $compact)) {
            $kind = preg_match('/^\d{10}$/', $nationalId) ? 'national_id' : 'admission';
            $patients = $kind === 'admission' ? self::byAdmission($compact) : new Collection;

            if ($patients->isEmpty() && ($his = self::his()) !== null) {
                $found = $kind === 'national_id' ? $his->lookup(nationalId: $nationalId) : $his->lookup(admissionCode: $compact);
                if ($found) {
                    return ['patients' => new Collection([$found]), 'kind' => $kind, 'fromHis' => true];
                }
            }

            return ['patients' => $patients, 'kind' => $kind, 'fromHis' => false];
        }

        return ['patients' => self::byName($query), 'kind' => 'name', 'fromHis' => false];
    }

    /**
     * Persian and Arabic digits as ASCII, so "۰۰۱۲..." searches like "0012...".
     */
    private static function digits(string $query): string
    {
        return strtr($query, array_combine(
            [...mb_str_split('۰۱۲۳۴۵۶۷۸۹'), ...mb_str_split('٠١٢٣٤٥٦٧٨٩')],
            [...str_split('0123456789'), ...str_split('0123456789')],
        ));
    }

    public static function normalize(string $text): string
    {
        return mb_strtolower(strtr($text, ['ي' => 'ی', 'ك' => 'ک', 'ى' => 'ی', 'ة' => 'ه', ' ' => '', "\u{200C}" => '']));
    }

    /**
     * @return Collection<int, Patient>
     */
    private static function byName(string $query): Collection
    {
        $like = '%'.addcslashes(self::normalize($query), '%_\\').'%';
        $name = fn (string $column) => "lower(translate({$column}, ?, ?))";

        return Patient::query()
            ->where(fn ($q) => $q
                ->whereRaw($name("coalesce(first_name, '') || coalesce(last_name, '')").' like ?', [self::FROM, self::TO, $like])
                ->orWhereRaw($name("coalesce(last_name, '') || coalesce(first_name, '')").' like ?', [self::FROM, self::TO, $like]))
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * Heart stored HIS admission numbers with a ".00" suffix; both forms are matched.
     *
     * @return Collection<int, Patient>
     */
    private static function byAdmission(string $code): Collection
    {
        $ids = Admission::whereIn('his_id', [$code, "{$code}.00"])->pluck('patient_id');

        return Patient::whereIn('id', $ids)->limit(self::LIMIT)->get();
    }

    private static function his(): ?HisAdmissionImporter
    {
        $slug = config('heart.his.lookup_hospital');
        $hospital = $slug ? Hospital::where('slug', $slug)->first() : null;

        if (! $hospital || $hospital->id !== app(CurrentHospital::class)->id()) {
            return null;
        }

        return new HisAdmissionImporter(DB::connection('his'), $hospital, (string) config('heart.his.view'));
    }
}
