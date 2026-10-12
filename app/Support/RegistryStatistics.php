<?php

namespace App\Support;

use App\Enums\StudyStatus;
use App\Models\EchoStudy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Registry statistics for the signed-in user's hospital, the counterpart of the charts on heart's
 * home page (records per registry, discharge status). Every query goes through the study models, so
 * the hospital scope and soft deletes apply.
 */
final class RegistryStatistics
{
    /**
     * Choice fields charted as distributions, per procedure.
     *
     * @var array<string, list<string>>
     */
    public const DISTRIBUTIONS = [
        'angiography' => ['angiography_status', 'result_legacy', 'LMD'],
        'angioplasty' => ['t1_Result_Of_PCI_1', 'arterial_Access_site'],
        'discharge' => ['disch_status'],
    ];

    public const PERIODS = [6, 12, 24];

    public function __construct(private readonly int $months = 12) {}

    /**
     * Study and patient counts per form, echo first.
     *
     * @return list<array{key: string, label: string, total: int, signed: int, drafts: int, patients: int}>
     */
    public function totals(): array
    {
        $rows = [];

        foreach (self::models() as $key => $model) {
            $row = $model::query()->toBase()
                ->selectRaw('count(*) as total, count(distinct patient_id) as patients')
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as drafts', [StudyStatus::Draft->value])
                ->first();

            $rows[] = [
                'key' => $key,
                'label' => self::label($key),
                'total' => (int) $row->total,
                'signed' => (int) $row->total - (int) $row->drafts,
                'drafts' => (int) $row->drafts,
                'patients' => (int) $row->patients,
            ];
        }

        return $rows;
    }

    /**
     * Studies per Jalali month over the period, by form. A procedure counts in the month of its
     * procedure date (heart's dates are typed in Jalali), or the month it was recorded when that is
     * missing; echo studies count in the month they were recorded.
     *
     * @return array{months: list<string>, series: array<string, array<string, int>>, max: int}
     */
    public function monthly(): array
    {
        $months = self::lastMonths($this->months);
        $series = [];

        foreach (self::models() as $key => $model) {
            $dateField = $key === 'echo' ? null : config("procedures.{$key}.date_field");
            $counts = array_fill_keys($months, 0);

            $rows = $model::query()->toBase()->select(array_filter(['id', 'created_at', $dateField]))->lazyById(1000, 'id');
            foreach ($rows as $row) {
                $month = self::month($dateField ? $row->{$dateField} : null)
                    ?? JalaliDate::format(Carbon::parse($row->created_at), 'yyyy/MM');
                if (isset($counts[$month])) {
                    $counts[$month]++;
                }
            }

            $series[$key] = $counts;
        }

        $totals = array_map(fn (string $month) => array_sum(array_column($series, $month)), $months);

        return ['months' => $months, 'series' => $series, 'max' => max([0, ...$totals])];
    }

    /**
     * Counts of each choice of the charted fields, in the form's option order, with studies that
     * did not record the field last.
     *
     * @return list<array{procedure: string, field: string, label: string, total: int, rows: list<array{label: string, count: int}>}>
     */
    public function distributions(): array
    {
        $charts = [];

        foreach (self::DISTRIBUTIONS as $key => $fieldNames) {
            $fields = Procedures::fields($key);

            foreach ($fieldNames as $name) {
                $counts = Procedures::model($key)::query()->toBase()
                    ->selectRaw("{$this->column($name)} as code, count(*) as total")
                    ->groupBy('code')
                    ->pluck('total', 'code')
                    ->mapWithKeys(fn ($total, $code) => [self::code($code) => (int) $total]);

                $rows = [];
                foreach ($fields[$name]['options'] as $code => $label) {
                    $rows[] = ['label' => $label, 'count' => $counts->pull((string) $code, 0)];
                }
                // Codes no longer offered (from imported data) keep their raw value.
                foreach ($counts->except('') as $code => $total) {
                    $rows[] = ['label' => (string) $code, 'count' => $total];
                }
                $rows[] = ['label' => __('Not recorded'), 'count' => $counts->get('', 0)];

                $charts[] = [
                    'procedure' => self::label($key),
                    'field' => $name,
                    'label' => $fields[$name]['label'],
                    'total' => array_sum(array_column($rows, 'count')),
                    'rows' => array_values(array_filter($rows, fn (array $row) => $row['count'] > 0)),
                ];
            }
        }

        return $charts;
    }

    public static function label(string $key): string
    {
        return $key === 'echo' ? __('Echocardiography') : __(Procedures::label($key));
    }

    /**
     * Echo plus every procedure ported from heart.
     *
     * @return array<string, class-string<Model>>
     */
    private static function models(): array
    {
        return ['echo' => EchoStudy::class, ...Procedures::MODELS];
    }

    /**
     * The quoted column of a charted field; names come from DISTRIBUTIONS, never from the request.
     */
    private function column(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }

    /**
     * Choice codes as strings ("4", not "4.0"); empty for no value.
     */
    private static function code(mixed $code): string
    {
        return match (true) {
            $code === null || $code === '' => '',
            is_bool($code) => $code ? '1' : '0',
            is_numeric($code) && (float) $code == (int) $code => (string) (int) $code,
            default => (string) $code,
        };
    }

    /**
     * "yyyy/MM" of a typed Jalali date such as 1405/7/9, or null when it isn't one.
     */
    private static function month(mixed $date): ?string
    {
        if (! is_string($date) || ! preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', trim($date), $m) || $m[2] < 1 || $m[2] > 12) {
            return null;
        }

        return sprintf('%04d/%02d', $m[1], $m[2]);
    }

    /**
     * The last $count Jalali months, oldest first, ending with the current one.
     *
     * @return list<string>
     */
    private static function lastMonths(int $count): array
    {
        [$year, $month] = array_map('intval', explode('/', JalaliDate::format(now(), 'yyyy/MM')));
        $months = [];

        for ($i = 0; $i < $count; $i++) {
            array_unshift($months, sprintf('%04d/%02d', $year, $month));
            if (--$month === 0) {
                [$year, $month] = [$year - 1, 12];
            }
        }

        return $months;
    }
}
