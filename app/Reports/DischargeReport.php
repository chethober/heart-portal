<?php

namespace App\Reports;

/**
 * Discharge summary ("برگ خلاصه پرونده"), after heart's reports/templates/heart.js: complaint and
 * primary diagnosis, vital signs and signs, final diagnosis, the HF / MI / CABG registry sections,
 * treatments during admission, course, risk factors, previous procedures, labs, ECG, echo,
 * non-invasive tests, complications, the patient's condition at discharge, recommendations and
 * discharge medications. Its sentences are Persian, as heart printed them; the headings follow the
 * interface language.
 *
 * Heart also pulled the latest angiography result of the admission into the treatment list; that
 * is left out so a signed summary only depends on its own (snapshotted) data.
 */
final class DischargeReport extends ProcedureReport
{
    public const PROCEDURE = 'discharge';

    private const DEATH = '4';

    /**
     * Lab fields with the units heart printed.
     */
    private const LABS = [
        'lab_UREA' => 'mg/dl', 'lab_BUN' => 'mg/dl', 'lab_CREATININE' => 'mg/dl', 'lab_Max_CREATININE' => 'mg/dl',
        'lab_discharge_CREATININE' => 'mg/dl', 'lab_Na' => 'mmol/L', 'lab_K' => 'mmol/L', 'lab_CA' => 'mg/dL',
        'lab_UricAcid' => '', 'lab_P' => '', 'lab_HB' => 'g/dl', 'lab_WBC' => '/μL', 'lab_PLT' => 'x1000/μL',
        'lab_ESR' => 'mm', 'lab_PT' => 's', 'lab_PTT' => 's', 'lab_INR' => '', 'lab_FBS' => 'mg/dL', 'lab_BS' => 'mg/dl',
        'lab_HBA1c' => '%', 'lab_BS2HPP' => '', 'lab_Chol' => 'mg/dl', 'lab_TG' => 'mg/dl', 'lab_LDL' => 'mg/dl',
        'lab_HDL' => 'mg/dl', 'lab_AST' => '', 'lab_ALT' => '', 'lab_CPK' => '', 'lab_totalBilirobin' => '',
        'lab_BNP' => '', 'lab_MBpick' => '', 'lab_Troponin' => '', 'lab_CRP' => '', 'lab_VITD3' => '', 'lab_TSH' => '',
        'lab_ferritine' => '',
    ];

    /**
     * Printed in Persian like heart's, although the form itself is laid out left to right.
     */
    public function language(): string
    {
        return 'fa';
    }

    public function title(): string
    {
        return __('Discharge Summary Sheet');
    }

    public function signatureBoxes(): array
    {
        return [
            ['title' => __('Stamp and signature of the intern'), 'name' => $this->text('disch_StudentId'), 'step' => 'intern'],
            ['title' => __('Stamp and signature of the resident'), 'name' => null, 'step' => 'resident'],
            ['title' => __('Stamp and signature of the attending physician'), 'name' => null],
        ];
    }

    public function headerItems(): array
    {
        return array_filter([__('Residency status') => $this->is('bomi', '1', '2') ? $this->text('bomi') : null]);
    }

    protected function build(): array
    {
        $died = $this->is('disch_status', self::DEATH);

        return [
            self::section(__('Chief complaint and initial diagnosis'), [
                self::line(null, $this->summary()),
                self::line(__('Vital signs on admission'), $this->vitalSigns()),
                self::line(__('Signs'), $this->join([...$this->yesCaptions([
                    'signs_jvp', 'signs_rales', 'signs_wheeze', 'signs_decreasedbreathsounds', 'signs_systolicsouffle',
                    'signs_diastolicsouffle', 'signs_ascites', 'signs_peripheraledema', 'signs_s3', 'signs_coldextremities',
                ]), $this->text('signs_others')]) ?: null),
                self::line(__('Final diagnosis'), $this->text('final_diagnosis_codes')),
                self::line($this->raw('final_diagnosis_codes') !== null ? __('Final diagnosis notes') : __('Final diagnosis'), $this->text('finalDiagnosis')),
                self::line(__('Other notes on the illness'), $this->heartFailure()),
                self::line(__('Reperfusion'), $this->reperfusion()),
                self::line(__('Angiography findings before surgery'), $this->catheterization()),
                self::line(__('Echocardiography findings'), $this->cabgEcho()),
            ]),
            self::section(__('Treatments and surgical procedures'), [
                ...$this->treatments(),
                self::line(__('CABG surgery'), $this->cabgOperation()),
                self::line(__('Valve surgery'), $this->valveSurgery()),
                self::line(__('Other procedures performed'), $this->text('other_treatmentPrpcedure')),
                self::line(__('Course of illness'), $this->text('comment')),
            ]),
            self::section(__('History and paraclinical findings'), [
                self::line(__('Risk factors and other cardiovascular diseases'), $this->riskFactors()),
                self::line(__('Previous procedures'), $this->join([...$this->yesCaptions(['HxPCI', 'icd', 'crt', 'pace', 'cabg']), $this->text('other_procedures')]) ?: null),
                self::line(__('Lab data'), $this->labs()),
                self::line(__('Electrocardiogram results'), $this->ecg()),
                self::line(__('Echocardiography'), $this->join([
                    ($ef = $this->text('ef')) !== null ? "LVEF = {$ef}%" : null,
                    $this->text('important_result_summary'),
                ]) ?: null),
                self::line(__('Other non-invasive diagnostic tests'), $this->nonInvasive()),
            ]),
            self::section(__('Discharge'), [
                self::line(__('Complications during admission'), $this->admissionComplications()),
                self::line(__('Surgical complications during admission'), $this->surgicalComplications()),
                self::line(__('Condition at discharge'), $this->discharge()),
                self::line(__('Recommendations after discharge'), $died ? null : $this->recommendations()),
                self::line(__('Discharge medications'), $died ? null : $this->text('drugDischarge')),
            ]),
        ];
    }

    /**
     * Heart's opening sentence, or the free-text history when one was written instead.
     */
    private function summary(): ?string
    {
        if ($history = $this->text('non_specific_explanation')) {
            return $history;
        }

        $complaints = $this->yesCaptions([
            'clinicalcomplaint_chestpaininactivity', 'clinicalcomplaint_orthopnea', 'clinicalcomplaint_pnd', 'clinicalcomplaint_Oedema',
            'clinicalcomplaint_chestpainInrest', 'clinicalcomplaint_chestDisromfort', 'clinicalcomplaint_syncope',
            'clinicalcomplaint_palpitations', 'clinicalcomplaint_chestpainAndasthma', 'clinicalcomplaint_severeasthma',
            'clinicalcomplaint_severecough', 'clinicalcomplaint_Ascite', 'clinicalcomplaint_abdominaldiscomfort',
            'clinicalcomplaint_anorexia', 'clinicalcomplaint_fatigue', 'clinicalcomplaint_asthmainactivity',
        ]);
        if ($class = $this->text('dispneaclass')) {
            $complaints[] = "کلاس دیس پنه {$class}";
        }
        $complaints[] = $this->text('symptoms_others');

        $diagnoses = $this->yesCaptions([
            'stableangina', 'STElevatedMI', 'nonSTElevatedMI', 'unstable_angina', 'positive_stress_test',
            'cardiogenic_shock_within24hrs', 'LV_systolic_dysfunction', 'cardiomyopathy', 'pulmonary_hypertension',
            'heart_failure', 'peripheral_vascular_disease', 'cardiac_arrest_within_24hrs', 'acute_lung_edema',
            'lung_emboli', 'heart_valvular_disease',
        ]);
        if ($this->yes('arrhythmia')) {
            $diagnoses[] = $this->caption('arrhythmia').(($type = $this->text('typeOfArrhythmia')) ? " ({$type})" : '');
        }
        $diagnoses[] = $this->text('other_indication');
        $for = $this->yesCaptions(['for_angiography', 'for_angioplasty', 'for_CABG', 'for_heart_valve_surgery']);

        $cabg = $this->join([
            $this->yes('emerg_cabg') ? $this->join([$this->caption('emerg_cabg'), $this->is('emerg_cabg_indication', '9') ? null : $this->text('emerg_cabg_indication')], ' - ') : null,
            $this->yes('elective_cabg') ? $this->join([$this->caption('elective_cabg'), $this->is('elective_cabg_type', '9') ? null : $this->text('elective_cabg_type')], ' - ') : null,
        ]);

        $complaints = $this->join($complaints);
        $diagnoses = $this->join($diagnoses);
        $for = $this->join($for);
        if ($complaints === '' && $diagnoses === '' && $for === '' && $cabg === '') {
            return null;
        }

        $text = 'بیمار'.(($who = $this->ageAndGender()) ? " {$who}" : '').' است که';
        if ($complaints !== '') {
            $text .= " به دلیل {$complaints}";
        }
        if ($diagnoses !== '') {
            $text .= " با تشخیص {$diagnoses}";
        }
        if ($for !== '') {
            $text .= "، {$for}";
        }
        if ($cabg !== '') {
            $text .= " و با اندیکاسیون ({$cabg})";
        }

        return $text.' بستری شده است.';
    }

    private function vitalSigns(): ?string
    {
        $sbp = $this->text('sbp');
        $dbp = $this->text('dbp');
        $weight = $this->raw('weight');
        $height = $this->raw('height');

        $parts = [
            $sbp !== null || $dbp !== null ? 'BP = '.($sbp ?? '-').'/'.($dbp ?? '-').' mmHg' : null,
            ($v = $this->text('hr')) !== null ? "HR = {$v}" : null,
            ($v = $this->text('rr')) !== null ? "RR = {$v}" : null,
            ($v = $this->text('temperature')) !== null ? "T = {$v} °C" : null,
            ($v = $this->text('weight')) !== null ? "Weight = {$v} kg" : null,
            ($v = $this->text('height')) !== null ? "Height = {$v} cm" : null,
            is_numeric($weight) && is_numeric($height) && $height > 0
                ? 'BMI = '.self::number(round($weight / (($height / 100) ** 2), 1))
                : null,
        ];

        return $this->join($parts, ' , ') ?: null;
    }

    private function heartFailure(): ?string
    {
        $parts = [];

        if (! $this->is('diagnosis', 'unknown') && ($diagnosis = $this->text('diagnosis'))) {
            $parts[] = "نوع نارسایی: {$diagnosis}";
        }
        if (! $this->is('etiology', 'unknown') && ($etiology = $this->choiceOrOther('etiology', 'etiology_others', 'others'))) {
            $parts[] = "اتیولوژی نارسایی: {$etiology}";
        }
        if ($date = $this->text('DateOf_diagnosisdate')) {
            $parts[] = "تاریخ تشخیص: {$date}";
        }
        $factors = $this->yesCaptions([
            'aggravatingfactors_pneumonitis', 'aggravatingfactors_acutepe', 'aggravatingfactors_drugs', 'aggravatingfactors_food',
            'aggravatingfactors_svt', 'aggravatingfactors_vt', 'aggravatingfactors_recentsurgery',
            'aggravatingfactors_renaldysfunction', 'aggravatingfactors_ACS', 'aggravatingfactors_unknown',
        ]);
        if ($factors) {
            $parts[] = 'عامل تشدید کننده: '.$this->join($factors);
        }
        if ($others = $this->text('aggfactors_others')) {
            $parts[] = "علل دیگر: {$others}";
        }

        return $this->join($parts) ?: null;
    }

    private function reperfusion(): ?string
    {
        $parts = [];

        if ($this->yes('tx_fibrinolytic')) {
            $drugs = $this->join($this->yesCaptions(['tx_fibrinolytic_SK', 'tx_fibrinolytic_rtpa']), ' ');
            $parts[] = 'درمان با فیبرینولیتیک'.($drugs !== '' ? " ({$drugs})" : '')
                .(($date = $this->text('DateOf_tx_fibrinolytic_date')) ? " در تاریخ {$date}" : '')
                .(($time = $this->text('tx_fibrinolytic_time')) ? " و ساعت {$time}" : '')
                .' انجام شد.';
        } elseif ($this->is('tx_fibrinolytic', '2') && ($reason = $this->choiceOrOther('tx_fibrinolytic_reason_main', 'tx_fibrinolytic_reason_other'))) {
            $parts[] = "علت عدم دریافت فیبرینولیتیک: {$reason}.";
        }

        if ($this->yes('tx_Reperfusion')) {
            $parts[] = 'پروسیجر مداخله ای کرونر'
                .(($type = $this->text('tx_Reperfusion_type')) ? " ({$type})" : '')
                .(($date = $this->text('DateOf_Reperfusion_date')) ? " در تاریخ {$date}" : '')
                .(($time = $this->text('Reperfusion_time')) ? "، در ساعت {$time}" : '')
                .' انجام شد.';
        } elseif ($this->is('tx_Reperfusion', '2') && ($reason = $this->choiceOrOther('tx_pci_reason_main', 'tx_pci_reason_other'))) {
            $parts[] = "علت عدم انجام PCI: {$reason}.";
        }

        if ($parts && $this->is('pain_time_status', '1') && ($pain = $this->dateTime('DateOf_pain_date', 'pain_time'))) {
            array_unshift($parts, "زمان شروع درد: {$pain}.");
        }

        return $parts ? implode(' ', $parts) : null;
    }

    private function catheterization(): ?string
    {
        if (! $this->yes('angiography_available')) {
            return null;
        }

        $vessels = $this->yesCaptions(['cath_stenosis_lad', 'cath_stenosis_lcx', 'cath_stenosis_rca', 'cath_stenosis_diagonal', 'cath_stenosis_om', 'cath_stenosis_pda', 'cath_stenosis_plv', 'cath_stenosis_lm']);

        return $this->join([
            $vessels ? 'تنگی شدید در '.$this->join($vessels, ', ') : null,
            ($ef = $this->text('lvef')) !== null ? "LVEF = {$ef}%" : null,
        ]) ?: null;
    }

    private function cabgEcho(): ?string
    {
        return $this->join([
            ($v = $this->text('echo_before_lvef')) !== null ? "Pre operation LVEF = {$v}%" : null,
            ($v = $this->text('echo_post_lvef')) !== null ? "post operation LVEF = {$v}%" : null,
            $this->is('echo_before_mr_severity', '9') ? null : $this->text('echo_before_mr_severity'),
            $this->yes('echo_before_lv_aneurism') ? 'LV Aneurysm' : null,
            ($v = $this->text('echo_before_spap')) !== null ? "SPAP = {$v}" : null,
            ($v = $this->text('echo_before_otherFinding')) !== null ? "other finding: {$v}" : null,
        ], ' و ') ?: null;
    }

    /**
     * Heart's numbered list of treatments and procedures during the admission.
     *
     * @return list<array{label: string|null, text: string}|null>
     */
    private function treatments(): array
    {
        $items = [
            'medication_treatment' => ['medication_treatmentFor', __('The following medications were given during admission')],
            'angiography_coroners' => ['angiography_coronersFor', null],
            'angioplasty_coroners' => ['angioplasty_coronersFor', null],
            'heart_katetrism' => ['heart_katetrismFor', null],
            'heart_device' => ['heart_deviceFor', null],
            'ablationArythmie' => ['ablationArythmieFor', null],
        ];
        $lines = [];
        $n = 0;

        foreach ($items as $done => [$details, $label]) {
            $text = $this->text($details);
            if (! $this->yes($done) && $text === null) {
                continue;
            }
            $lines[] = self::line(++$n.' - '.($label ?? $this->caption($done)), $text ?? __('Done.'));
        }

        return $lines;
    }

    private function cabgOperation(): ?string
    {
        $grafts = [];
        foreach (['op_cabg_lima', 'op_cabg_rima', 'op_cabg_radial', 'op_cabg_svg1', 'op_cabg_svg2', 'op_cabg_svg3', 'op_cabg_svg4'] as $graft) {
            if ($this->raw($graft) !== null && ! $this->is($graft, 'UNKNOWN', 'NONE')) {
                $grafts[] = $this->caption($graft).' on '.$this->text($graft);
            }
        }

        $parts = [
            $this->is('op_cabg', '1', '2', '3') ? 'CABG ('.$this->text('op_cabg').')' : null,
            $grafts ? 'grafts: '.$this->join($grafts, ', ') : null,
            ($date = $this->text('DateOf_surgery_date')) ? "تاریخ جراحی: {$date}" : null,
            $this->yes('op_endarterectomy') ? 'End Arterectomy'.(($vessel = $this->text('op_endarterectomy_vessel')) ? " ({$vessel})" : '') : null,
            ($v = $this->text('op_cbp_time')) !== null ? "CPB time = {$v} min" : null,
            ($v = $this->text('op_crossclamp_time')) !== null ? "cross clamp time = {$v} min" : null,
            $this->text('other_surgery'),
        ];

        return $this->join($parts) ?: null;
    }

    private function valveSurgery(): ?string
    {
        if (! $this->yes('op_valve_srg')) {
            return null;
        }

        $valves = [];
        foreach (['mitral', 'aortic', 'tricuspid', 'pulmonary'] as $valve) {
            if (! $this->yes("op_valve_srg_which_{$valve}")) {
                continue;
            }

            $detail = match (true) {
                $this->is("op_valve_srg_type_{$valve}", '1') => $this->join(['Repair', $this->text("op_valve_srg_repair_{$valve}")], ' - '),
                $this->is("op_valve_srg_type_{$valve}", '2') => $this->join([
                    'Replacement',
                    $this->text("op_valve_srg_replacement_{$valve}"),
                    ($size = $this->text("op_valve_srg_replacement_size_{$valve}")) !== null ? "size {$size}" : null,
                ], ' - '),
                default => null,
            };
            $valves[] = ucfirst($valve).($detail ? " ({$detail})" : '');
        }

        return $this->join($valves) ?: __('Done.');
    }

    private function riskFactors(): ?string
    {
        $parts = [];

        foreach (['hxPrevMI_main' => 'DateOf_hxPrevMI_date', 'hxPrevHF_main' => 'DateOf_hxPrevHF_date', 'hxPrevCVA_main' => 'DateOf_hxPrevCVA_date', 'previous_cvd' => null, 'hxCRF' => null] as $history => $date) {
            if ($this->yes($history)) {
                $parts[] = $this->caption($history).($date && ($on = $this->text($date)) ? " ({$on})" : '');
            }
        }
        if ($this->yes('rf_htn_hx')) {
            $parts[] = $this->caption('rf_htn_hx').(($tx = $this->text('rf_htn_tx')) ? " ({$tx})" : '');
        }
        foreach (['rf_diabetes_hx' => ['dm_tx_regimen', 'dm_tx_oral', 'dm_tx_insulin'], 'rf_hyperchol_hx' => ['hyperchol_tx_regimen', 'hyperchol_tx_statin', 'hyperchol_tx_non_statin']] as $history => $therapy) {
            if ($this->yes($history)) {
                $tx = $this->join($this->yesCaptions($therapy));
                $parts[] = $this->caption($history).($tx !== '' ? " ({$tx})" : '');
            }
        }
        if ($this->is('rf_smoking', '1', '2')) {
            $parts[] = $this->text('rf_smoking');
        }
        $parts = [...$parts, ...$this->yesCaptions(['rf_hookah', 'rf_opium', 'rf_alcohol', 'rf_opioides'])];
        if ($this->yes('cad_family_hx')) {
            $family = $this->join($this->yesCaptions(['cad_family_hx_father', 'cad_family_hx_mother', 'cad_family_hx_brother', 'cad_family_hx_sister']));
            $parts[] = $this->caption('cad_family_hx').($family !== '' ? " ({$family})" : '');
        }
        $parts[] = $this->text('other_past_history');

        return $this->join($parts) ?: null;
    }

    private function labs(): ?string
    {
        $parts = [];

        foreach (self::LABS as $lab => $unit) {
            if (($value = $this->text($lab)) !== null) {
                $parts[] = trim($this->fields[$lab]['label']." = {$value} {$unit}");
            }
        }
        if ($this->raw('lab_BG2') !== null && ! $this->is('lab_BG2', '0', '9')) {
            $parts[] = $this->caption('lab_BG2').' = '.$this->text('lab_BG2');
        }
        foreach (['lab_HCVAb', 'lab_AntiHBS', 'lab_HIVAB'] as $serology) {
            if ($this->is($serology, '1')) {
                $parts[] = $this->caption($serology).' = positive';
            } elseif ($this->is($serology, '0')) {
                $parts[] = $this->caption($serology).' = negative';
            }
        }
        $parts[] = $this->text('lab_other');

        return $this->join($parts, ' , ') ?: null;
    }

    private function ecg(): ?string
    {
        $parts = [];

        if ($this->is('ecg_normal', '0')) {
            $parts[] = 'Normal ECG';
        }
        if ($this->yes('ecg_sinus')) {
            $parts[] = 'Sinus Rhythm';
        } elseif ($rhythm = $this->text('ecg_other_rhythm')) {
            $parts[] = $rhythm;
        }
        if (($rate = $this->text('ecg_ecg_hr')) !== null) {
            $parts[] = "Rate = {$rate}";
        }
        $parts = [...$parts, ...$this->yesCaptions(['ecg_nssttc', 'ecg_omi', 'ecg_lbbb', 'ecg_rbbb'])];
        if ($this->yes('ecg_ami')) {
            $locations = $this->yesCaptions(['ecg_ecg_mi_location_inf', 'ecg_ecg_mi_location_antroseptal', 'ecg_ecg_mi_location_antrolateral', 'ecg_ecg_mi_location_extant', 'ecg_ecg_mi_location_post', 'ecg_ecg_mi_location_rv']);
            $parts[] = $this->join([$this->caption('ecg_ami'), $this->text('ecg_mi'), $locations ? '('.$this->join($locations, ', ').')' : null], ' ');
        }
        $parts[] = $this->text('ecg_other');

        return $this->join($parts, ', ') ?: null;
    }

    private function nonInvasive(): ?string
    {
        $parts = [];

        foreach (['exercise_stress_test', 'stress_echocardiogram', 'spect_MPI', 'cardiac_ct_angiography'] as $test) {
            if ($this->yes($test)) {
                $result = $this->is("result_{$test}", '9') ? null : $this->text("result_{$test}");
                $parts[] = $this->caption($test).($result ? ": {$result}" : '');
            }
        }
        $parts[] = $this->text('other_test');

        return $this->join($parts, ', ') ?: null;
    }

    private function admissionComplications(): ?string
    {
        if ($this->is('diseasestatus', '0')) {
            return __('None');
        }

        $parts = [$this->text('diseasestatus_other')];
        if ($this->yes('comp_after_hematoma')) {
            $parts[] = 'هماتوم محل آنژیوگرافی'
                .(($size = $this->is('comp_after_hematoma_size', '9') ? null : $this->text('comp_after_hematoma_size')) ? " ({$size} سانتی متر)" : '')
                .(($action = $this->is('comp_after_intervention', '9') ? null : $this->text('comp_after_intervention')) ? " - {$action}" : '');
        }
        if ($this->yes('comp_after_pulseless')) {
            $parts[] = $this->caption('comp_after_pulseless')
                .(($action = $this->is('comp_after_pulseless_intervention', '9') ? null : $this->text('comp_after_pulseless_intervention')) ? " ({$action})" : '');
        }
        if ($this->yes('comp_after_cva')) {
            $parts[] = $this->caption('comp_after_cva');
        }
        $parts[] = $this->text('diseasestatus_complication');

        return $this->join($parts) ?: null;
    }

    private function surgicalComplications(): ?string
    {
        $parts = $this->yesCaptions([
            'compl_tamponade', 'compl_inf', 'compl_inf_leg', 'compl_inf_sternum', 'compl_rf', 'compl_dialysis',
            'compl_neurologic', 'compl_neurologic_tia', 'compl_neurologic_cva', 'compl_neurologic_convulsion',
            'compl_endocrine', 'compl_endocrine_diabetes', 'compl_endocrine_hypothyroidism', 'compl_inotrope', 'compl_iabp',
            'compl_iabp_preop', 'compl_iabp_intraop', 'compl_iabp_postop', 'compl_arrhythmic', 'compl_pace',
            'compl_pulmonary', 'compl_pulmonary_athelectasia', 'compl_pulmonary_aspiration', 'compl_pulmonary_pneumonia',
            'compl_pulmonary_lunginjury', 'compl_inhospital_mortality',
        ]);

        return $this->join($parts, ', ') ?: null;
    }

    /**
     * The patient's condition at discharge, worded as heart did for each discharge status.
     */
    private function discharge(): ?string
    {
        $when = $this->join([
            ($date = $this->text('DateOf_disch_date')) ? "در تاریخ {$date}" : null,
            ($time = $this->text('disch_time')) ? "ساعت {$time}" : null,
        ], ' ');
        $patient = trim("بیمار {$when}");

        return match (true) {
            $this->is('disch_status', '1', '2') => "{$patient} با ".$this->text('disch_status').' مرخص شد.',
            $this->is('disch_status', '3') => $patient.(($why = $this->text('disch_reason_for_DAMA')) ? " به علت {$why}" : '').' با میل شخصی مرخص شد.',
            $this->is('disch_status', self::DEATH) => $patient.$this->deathCause().' فوت شد.',
            $this->is('disch_status', '5') => "{$patient} مرخص و توصیه به پیگیری زیر شد".(($followup = $this->text('disch_followup')) ? " ({$followup})" : '').'.',
            $this->is('disch_status', '6') => $patient
                .(($hospital = $this->text('disch_refer_to_hospital')) ? " به {$hospital}" : '')
                .(($why = $this->text('disch_reason_for_despatch')) ? " به علت {$why}" : '').' اعزام شد.',
            $this->is('disch_status', '7') => $patient
                .(($ward = $this->text('disch_transfer_to_ward')) ? " به بخش {$ward}" : '')
                .(($why = $this->text('disch_reason_for_transfer')) ? " به علت {$why}" : '').' منتقل شد.',
            $this->is('disch_status', '8') => $this->text('disch_other_form_discharge') ?? $this->text('disch_status'),
            default => $this->text('disch_status'),
        };
    }

    private function deathCause(): string
    {
        $cause = match (true) {
            $this->is('disch_death_reason', '1') => $this->join([$this->text('disch_death_reason'), $this->text('disch_death_reason_cardiac')], ' - '),
            $this->is('disch_death_reason', '2') => $this->join([$this->text('disch_death_reason'), $this->text('disch_death_reason_noncardiac')], ' - '),
            default => '',
        };

        if ($this->is('disch_status', self::DEATH)) {
            // Recommendations and discharge medicines do not apply; heart left them out.
            $this->consume('drugDischarge', 'other_dicharge_recommendation', 'medication_education', 'nutrition_education', 'attend_baztavani', 'date_attend_baztavani', 'attend_phicition', 'date_attend_physician');
        }

        return $cause !== '' ? " به علت ({$cause})" : '';
    }

    private function recommendations(): ?string
    {
        $parts = $this->yesCaptions(['medication_education', 'nutrition_education']);

        if ($this->yes('attend_baztavani')) {
            $parts[] = $this->caption('attend_baztavani').(($days = $this->text('date_attend_baztavani')) ? " ({$days} روز دیگر جهت بازتوانی مراجعه شود)" : '');
        }
        if ($this->yes('mental_factor')) {
            $parts[] = $this->caption('mental_factor').' ('.$this->join([
                ($v = $this->text('anxiety_grade')) !== null ? "اضطراب {$v}" : null,
                ($v = $this->text('stress_grade')) !== null ? "استرس {$v}" : null,
                ($v = $this->text('depression_grade')) !== null ? "افسردگی {$v}" : null,
                $this->text('other_mental_factor'),
            ]).')';
        }
        if ($this->yes('attend_phicition')) {
            $parts[] = $this->caption('attend_phicition').(($days = $this->text('date_attend_physician')) ? " ({$days} روز دیگر به پزشک مراجعه شود)" : '');
        }
        $parts[] = $this->text('other_dicharge_recommendation');

        return $this->join($parts) ?: null;
    }
}
