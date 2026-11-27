<?php

namespace App\Reports;

/**
 * Right heart catheterization report, ported from heart's reports/templates/rhc.js: the access and
 * catheter, a table of O2 saturation and pressures by site, the derived haemodynamics, result and
 * recommendation, and the entrance and exit times.
 */
final class RhcReport extends ProcedureReport
{
    public const PROCEDURE = 'rhc';

    protected const SUMMARY = ['Result'];

    /**
     * Sites in heart's order, with the measurements its form records for each.
     */
    private const SITES = [
        'LA' => ['la_o2', 'la_mean', null, null],
        'PCWP' => ['pcwp_o2', 'pcwp_mean', null, null],
        'RPA' => ['rpa_o2', 'rpa_mean', 'rpa_systolic', 'rpa_diastolic'],
        'LPA' => ['lpa_o2', 'lpa_mean', 'lpa_systolic', 'lpa_diastolic'],
        'MPA' => ['mpa_o2', 'mpa_mean', 'mpa_systolic', 'mpa_diastolic'],
        'RV' => ['rv_o2', 'rv_mean', 'rv_systolic', 'rv_diastolic'],
        'RA' => ['ra_o2', 'ra_mean', null, null],
        'SVC' => ['svc_o2', 'svc_mean', null, null],
        'IVC' => ['ivc_o2', 'ivc_mean', null, null],
        'AORTA' => ['aorta_o2', 'aorta_mean', 'aorta_systolic', 'aorta_diastolic'],
    ];

    public function headerItems(): array
    {
        return array_filter(['Registry code' => $this->text('qID')]);
    }

    public function titles(): array
    {
        return ['گزارش کاتتریسم قلبی', 'Heart Catheterization Report'];
    }

    public function signatureBoxes(): array
    {
        return [
            ['title' => 'Assistant physician', 'name' => null],
            ['title' => 'Attending physician', 'name' => null],
        ];
    }

    protected function build(): array
    {
        $via = $this->join([
            ($access = $this->text('Access')) ? "via {$access} vein" : null,
            ($catheter = $this->text('catheter')) ? "with {$catheter} catheter" : null,
        ], ' and ');

        $table = [['Site', 'O2 saturation %', 'Mean pressure (mmHg)', 'Systolic pressure (mmHg)', 'Diastolic pressure (mmHg)']];
        foreach (self::SITES as $site => $fields) {
            $row = array_map(fn (?string $name) => $name ? ($this->value($name) ?? '') : '', $fields);
            if (implode('', $row) !== '') {
                $table[] = [$site, ...$row];
            }
        }

        return [
            self::section('Right heart catheterization', [
                self::line('Indication', $this->choiceOrOther('indication', 'indication_other')),
                self::line(null, self::sentence('Right heart catheterization was done'.($via !== '' ? " {$via}" : '').' and the following results were obtained')),
            ], $table),
            self::section('Haemodynamics', [
                self::line('BSA', $this->value('bsa')),
                self::line('HB', $this->value('hb')),
                self::line('Cardiac output', $this->unit('cardiac_output', 'L/min')),
                self::line('Cardiac index', $this->unit('cardiac_index', 'L/min/m²')),
                self::line('Pulmonary vascular resistance', $this->pvr()),
                self::line('Systemic vascular resistance', $this->unit('svr', 'Wood units')),
            ]),
            self::section(null, [
                self::line('Result', ($result = $this->text('right_heart_result')) ? self::sentence($result) : null),
                self::line('Recommendation', ($advice = $this->text('right_heart_recommendation')) ? self::sentence($advice) : null),
                self::line('Entrance time', $this->dateTime('DateOf_entrance_date', 'entrance_time')),
                self::line('Exit time', $this->dateTime('DateOf_exit_date', 'exit_time')),
            ]),
        ];
    }

    /**
     * The recorded PVR, or one calculated as (mean PA pressure − PCWP) / cardiac output when the
     * form has those but no PVR, marked as calculated.
     */
    private function pvr(): ?string
    {
        if (($recorded = $this->unit('pvr', 'Wood units')) !== null) {
            return $recorded;
        }

        $mpa = $this->numeric('mpa_mean');
        $pcwp = $this->numeric('pcwp_mean');
        $output = $this->numeric('cardiac_output');

        if ($mpa === null || $pcwp === null || $output === null || $output <= 0) {
            return null;
        }

        return self::number(round(($mpa - $pcwp) / $output, 2)).' Wood units (calculated)';
    }

    /**
     * Heart treated 0 as "not measured" in this form, so zero is left out like an empty field.
     */
    private function value(string $name): ?string
    {
        $value = $this->raw($name);
        if ($value === null || (is_numeric($value) && (float) $value === 0.0)) {
            $this->consume($name);

            return null;
        }

        return $this->text($name);
    }

    private function unit(string $name, string $unit): ?string
    {
        $value = $this->value($name);

        return $value === null ? null : "{$value} {$unit}";
    }

    private function numeric(string $name): ?float
    {
        $value = $this->raw($name);

        return is_numeric($value) && (float) $value !== 0.0 ? (float) $value : null;
    }
}
