<?php

namespace App\Reports;

use App\Support\Procedures;

/**
 * Coronary angiography report, after heart's reports/templates/angiography.js: an introduction
 * (indication, technique), one sentence per vessel and graft, LV angiography, aortography,
 * hemodynamics, the result (1VD/2VD/3VD...), complications and recommendations.
 *
 * Vessels are read from the form's structure rather than field names (heart's names are not
 * consistent between vessels): every section with a "{prefix}_Vessel_patency" field is a vessel,
 * its segments are the Normal/Stenotic choices shown while the vessel is stenotic, and each
 * segment's severity and lesion type are the fields shown while that segment is stenotic.
 */
final class AngiographyReport extends ProcedureReport
{
    public const PROCEDURE = 'angiography';

    private const PATENCY_STENOSIS = '3';

    /**
     * How heart's result codes read in the report.
     */
    private const RESULTS = [
        '1' => 'Normal coronary arteries',
        '2' => 'Patent coronary arteries',
        '3' => 'Mild coronary artery disease',
        '4' => 'One-vessel CAD (1VD)',
        '5' => 'Two-vessel CAD (2VD)',
        '6' => 'Three-vessel CAD (3VD)',
        '7' => 'Multi-vessel CAD',
    ];

    /**
     * The result and the recommendation on one line, as the discharge summary writes them:
     * "آنژیوگرافی عروق کرونر انجام شد Three-vessel CAD (3VD) • Recommendation: CABG".
     */
    protected function buildSummary(): string
    {
        $result = $this->result();
        $recommendation = $this->recommendations();
        if ($result === null && $recommendation === null) {
            return '';
        }

        return $this->join([
            'آنژیوگرافی عروق کرونر انجام شد',
            $result,
            $recommendation !== null ? '• Recommendation: '.$recommendation : null,
        ], ' ');
    }

    public function title(): string
    {
        return __('Coronary Angiography Report');
    }

    protected function build(): array
    {
        return [
            self::section(null, [self::line(null, $this->introduction()), self::line(null, $this->technique())]),
            self::section(__('Findings'), $this->vessels()),
            self::section(null, [self::line(__('Additional findings'), $this->text('etc_Additional_Findings'))]),
            self::section(null, [
                self::line(__('LV angiography'), $this->lvAngiography()),
                self::line(__('Other results'), $this->text('lvent_anotherResult')),
                self::line(__('Aortography'), $this->aortography()),
                self::line(__('Hemodynamic data'), $this->hemodynamics()),
            ]),
            self::section(null, [
                self::line(__('Result'), $this->result()),
                self::line(__('Procedure related complication'), $this->complication()),
                self::line(__('Death'), $this->is('Death', '2') ? ($this->text('DeathCause') ?? __('Yes')) : null),
                self::line(__('Recommendation'), $this->recommendations()),
                self::line(__('Other explanations'), $this->text('other_explanations')),
                self::line(__('Entrance'), $this->dateTime('DateOf_entrance_date', 'entrance_time')),
                self::line(__('Exit'), $this->dateTime('DateOf_exit_date', 'exit_time')),
            ]),
        ];
    }

    private function introduction(): string
    {
        $types = $this->yesCaptions(['native_coronary_angiography', 'prepci_angiography', 'peripheral_angiography', 'graft_angiography']);
        $types[] = $this->text('other_Angiography_type');
        $types = $this->join($types);

        $text = 'The patient is '.($this->ageAndGender() ?? 'a patient');
        if ($status = $this->text('angiography_status')) {
            $text .= ", planned for {$status}";
        }
        if ($types !== '') {
            $text .= " ({$types})";
        }
        $indications = $this->yesCaptions(['indication_stable_angina', 'indication_st_elevated_mi', 'indication_non_st_elevated_mi', 'indication_unstable_angina', 'indication_cardiomyopathy', 'indication_heart_failure', 'indication_valvular_disease', 'indication_arrhythmia', 'indication_positive_stress_test', 'indication_cardiogenic_shock', 'indication_cardiac_arrest']);
        if ($indications !== []) {
            $text .= ', for '.$this->join($indications);
        }
        if ($date = $this->text('DateOf_Date_of_angiography')) {
            $text .= ", on {$date}";
        }
        if ($time = $this->text('Time_of_angiography')) {
            $text .= " at {$time}";
        }

        return self::sentence($text);
    }

    private function technique(): ?string
    {
        $parts = [];

        if ($access = $this->choiceOrOther('arterial_Access_site', 'other_access_2')) {
            $parts[] = "It was done via the {$access} artery";
        }
        if ($catheters = $this->text('catheter_name1')) {
            $parts[] = ($parts ? 'with ' : 'It was done with ')."{$catheters} catheters";
        }
        $sentences = $parts ? [self::sentence(implode(' ', $parts))] : [];

        $agent = $this->choiceOrOther('contrast_agentt1', 'other_contrast_agent');
        $volume = $this->text('contrast_volume2');
        if ($agent || $volume) {
            $sentences[] = self::sentence(($volume ? "{$volume} cc of " : '').($agent ?? 'contrast').' was used');
        }
        if ($dose = $this->text('fluro_dose2')) {
            $sentences[] = "Fluoroscopy dose: {$dose} mGy.";
        }

        return $sentences ? implode(' ', $sentences) : null;
    }

    /**
     * @return list<array{label: string|null, text: string}|null>
     */
    private function vessels(): array
    {
        $lines = [];

        foreach (Procedures::sections(self::PROCEDURE) as $section) {
            $patency = array_key_first(array_filter($section['fields'], fn (array $field, string $name) => str_ends_with($name, '_Vessel_patency'), ARRAY_FILTER_USE_BOTH));
            if ($patency !== null) {
                $lines[] = self::line($section['title'], $this->vessel(substr($patency, 0, -strlen('_Vessel_patency')), $section['fields']));
            }
        }

        return $lines;
    }

    /**
     * One vessel's findings, in heart's order: origin, patency (or the stenotic segments), the
     * vessel's properties, previous stent and the free-text description.
     *
     * @param  array<string, array<string, mixed>>  $fields
     */
    private function vessel(string $prefix, array $fields): ?string
    {
        $patency = "{$prefix}_Vessel_patency";
        $text = '';
        $known = [$patency, "{$prefix}_other_Vessel_patency", "{$prefix}_Cut_off", "{$prefix}_Take_off_from", "{$prefix}_prev_stent", "{$prefix}_pci_Description"];

        // Segments shown while the vessel is stenotic, and their details shown while the segment is.
        $segments = array_keys(array_filter($fields, fn (array $field) => ($field['show_if'] ?? null) === [$patency, self::PATENCY_STENOSIS]));
        $details = [];
        foreach ($fields as $name => $field) {
            if (isset($field['show_if']) && in_array($field['show_if'][0], $segments, true)) {
                $details[$field['show_if'][0]][] = $name;
            }
        }
        $known = [...$known, ...$segments, ...array_merge([], ...array_values($details))];

        if ($this->raw($patency) !== null) {
            if ($origin = $this->optional("{$prefix}_Take_off_from")) {
                $text .= "Originates from the {$origin} sinus; ";
            }

            $text .= match (true) {
                $this->is($patency, '1') => 'normal',
                $this->is($patency, '2') => 'patent',
                $this->is($patency, '4') => 'cut off'.(($at = $this->optional("{$prefix}_Cut_off")) ? " at {$at}" : ''),
                $this->is($patency, self::PATENCY_STENOSIS) => $this->segments($segments, $details) ?: 'stenosis',
                default => $this->optional("{$prefix}_other_Vessel_patency") ?? (string) $this->text($patency),
            };
            $text = self::sentence(ucfirst($text));
        }

        if ($properties = $this->properties($prefix, $fields, $known)) {
            $text .= " The vessel has {$properties}.";
        }

        if ($this->raw($patency) !== null && $this->has("{$prefix}_prev_stent")) {
            if ($this->is("{$prefix}_prev_stent", '0')) {
                $text .= ' There is no previous stent.';
            } elseif ($stent = $this->text("{$prefix}_prev_stent")) {
                $text .= " Previous stent is {$stent}.";
            }
        }

        if ($description = $this->optional("{$prefix}_pci_Description")) {
            $text .= ' '.self::sentence($description);
        }

        return trim($text) ?: null;
    }

    /**
     * "severe stenosis(70-89%) at proximal part segment, normal lumen at Ostia".
     *
     * @param  list<string>  $segments
     * @param  array<string, list<string>>  $details
     */
    private function segments(array $segments, array $details): string
    {
        $parts = [];

        foreach ($segments as $segment) {
            $where = $this->caption($segment);
            if ($this->is($segment, '1')) {
                $parts[] = "normal lumen at {$where}";
            } elseif ($this->is($segment, '2')) {
                $lesion = [];
                foreach ($details[$segment] ?? [] as $detail) {
                    // The lesion type (text) reads before the severity (choice).
                    $lesion[$this->fields[$detail]['type'] === 'choice' ? 1 : 0] = $this->text($detail);
                }
                ksort($lesion);
                $parts[] = $this->join([...$lesion, 'at '.$where], ' ');
            }
        }

        return $this->join($parts);
    }

    /**
     * The vessel's descriptive choices: "Large size, Mild Calcification, TIMI flow 3".
     *
     * @param  array<string, array<string, mixed>>  $fields
     * @param  list<string>  $known
     */
    private function properties(string $prefix, array $fields, array $known): string
    {
        $parts = [];

        foreach ($fields as $name => $field) {
            if (in_array($name, $known, true) || isset($field['show_if'])) {
                continue;
            }

            $parts[] = match ($name) {
                "{$prefix}_TIMI_Flow" => ($v = $this->text($name)) !== null ? "TIMI flow {$v}" : null,
                "{$prefix}_Termination" => ($v = $this->text($name)) !== null ? "termination {$v}" : null,
                "{$prefix}_Muscle_bridge" => $this->yes($name) ? 'muscle bridge' : null,
                "{$prefix}_grade" => ($v = $this->text($name)) !== null ? "collateral grade {$v}" : null,
                default => ($v = $this->text($name)) !== null ? "{$v} {$field['label']}" : null,
            };
        }

        return $this->join($parts);
    }

    private function lvAngiography(): ?string
    {
        $parts = [
            self::stripCode($this->text('lvent_lvsize')),
            ($ef = $this->text('lvent_lvef')) !== null ? "LVEF = {$ef}%" : null,
        ];

        $rwma = $this->yesCaptions(['lvent_RWMA_Anterobasal', 'lvent_RWMA_Anterolateral', 'lvent_RWMA_Anteroapical', 'lvent_RWMA_Inferobasal', 'lvent_RWMA_inferior']);
        if ($rwma) {
            $parts[] = 'RWMA in '.$this->join($rwma, ' - ').' segments';
        }
        $parts[] = self::stripCode($this->text('lvent_lvent_mr'));

        return $this->join($parts) ?: null;
    }

    private function aortography(): ?string
    {
        $parts = [
            ($size = self::stripCode($this->text('lvent_aortasize'))) ? "{$size} aorta" : null,
            ($leaflets = self::stripCode($this->text('lvent_leaflet'))) ? "{$leaflets} aortic valve" : null,
            self::stripCode($this->text('lvent_ar')),
        ];

        return $this->join($parts) ?: null;
    }

    private function hemodynamics(): ?string
    {
        $pressure = function (string $label, string $systolic, string $diastolic): ?string {
            $s = $this->text($systolic);
            $d = $this->text($diastolic);

            return $s === null && $d === null ? null : "{$label} = ".($s ?? '-').'/'.($d ?? '-').' mmHg';
        };

        return $this->join([
            $pressure('LV pressure', 'lvent_slvp', 'lvent_dlvp'),
            $pressure('Aorta pressure', 'lvent_sap', 'lvent_dap'),
        ]) ?: null;
    }

    private function result(): ?string
    {
        $parts = [];

        if ($this->yes('LMD')) {
            $parts[] = 'LM disease';
        }
        foreach (self::RESULTS as $code => $text) {
            if ($this->is('result_legacy', (string) $code)) {
                $parts[] = $text;
            }
        }
        if ($this->yes('Slow_coronary_flow_pattern')) {
            $parts[] = 'Slow coronary flow pattern';
        }
        $parts[] = $this->text('resulttt');

        return $this->join($parts, ' + ') ?: null;
    }

    private function complication(): ?string
    {
        if ($this->is('prc', '0')) {
            return __('No complication.');
        }
        if ($this->yes('prc')) {
            return $this->text('Managementcomplication') ?? __('Yes');
        }

        return null;
    }

    private function recommendations(): ?string
    {
        $parts = [];

        if ($this->yes('PCI')) {
            $vessels = array_map(
                fn (string $caption) => preg_replace('/^on /', '', $caption),
                $this->yesCaptions(['recommend_pci_lad', 'recommend_pci_lcx', 'recommend_pci_rca', 'recommend_pci_diagonols', 'recommend_pci_oms', 'recommend_pci_pda', 'recommend_pci_plv', 'recommend_pci_om1', 'recommend_pci_om2', 'recommend_pci_d1', 'recommend_pci_d2', 'recommend_pci_svg', 'recommend_pci_ramus']),
            );
            if ($this->yes('recommend_pci_other')) {
                $vessels[] = $this->text('recommend_pci_other_text');
            }
            $vessels = $this->join($vessels);
            $parts[] = 'PCI'.($vessels !== '' ? " on {$vessels}" : '');
        }
        if ($this->yes('PTCA')) {
            $parts[] = 'PTCA'.(($vessel = $this->text('PTCA_vessel')) ? " on {$vessel}" : '');
        }
        if ($this->yes('medial_treatment')) {
            $parts[] = 'Medical treatment if failed PCI'.(($vessel = $this->text('medial_treatment_vessel')) ? " on {$vessel}" : '');
        }

        $parts = [...$parts, ...$this->yesCaptions([
            'recommend_fallowup', 'recommend_treatment', 'recommend_cabg', 'recommend_avr', 'recommend_mvr', 'recommend_tvRepair',
            'recommend_TAVI', 'recommend_mvrepair', 'recommend_pciwithplannedcabg', 'recommend_further_evaluation_ivus',
            'recommend_further_evaluation_ffr', 'recommend_further_evaluation_viability', 'cabgVsMultivesselPCI',
            'LifeStyleModificationAndFollowUp', 'peripheralVascularIntervention', 'PTMC', 'PTPA',
            'medical_treatment_if_failed_look_see_for_PCI',
        ])];
        $parts[] = $this->text('other_recommendation');

        return $this->join($parts) ?: null;
    }

    /**
     * "Mild LV enlargment - S2" → "Mild LV enlargment" (heart's labels carry a grading code).
     */
    private static function stripCode(?string $label): ?string
    {
        return $label === null ? null : trim(preg_replace('/\s*-\s*[A-Z]{1,2}\d$/', '', $label));
    }
}
