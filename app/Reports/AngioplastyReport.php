<?php

namespace App\Reports;

/**
 * PCI (coronary angioplasty) report, after heart's reports/templates/angioplasty.js: indication,
 * technique and medications, then per treated territory (up to three) the lesion, balloons, stents,
 * bifurcation technique, TIMI flow, result and vessel complications, followed by the overall result,
 * PCI-induced complications and the recommendation.
 */
final class AngioplastyReport extends ProcedureReport
{
    public const PROCEDURE = 'angioplasty';

    protected const SUMMARY = ['Final result'];

    private const TERRITORIES = ['t1', 't2', 't3'];

    public function titles(): array
    {
        return ['گزارش آنژیوپلاستی', 'Percutaneous Coronary Intervention (PCI) Report'];
    }

    public function signatureBoxes(): array
    {
        return [
            ['title' => 'Assistant physician', 'name' => null],
            ['title' => 'Attending physician', 'name' => $this->text('Physician_name')],
        ];
    }

    protected function build(): array
    {
        $sections = [
            self::section(null, [
                self::line(null, $this->introduction()),
                self::line('Coronary vessels planned for PCI', $this->join(array_map(fn (string $t) => $this->territory($t), self::TERRITORIES)) ?: null),
                self::line('Technique', $this->technique()),
                self::line('Medications before and during the procedure', $this->medications()),
            ]),
        ];

        foreach (self::TERRITORIES as $t) {
            if ($territory = $this->territory($t)) {
                $sections[] = self::section("PCI on {$territory}", $this->intervention($t));
            }
        }

        $sections[] = self::section(null, [
            self::line('Description of PCI', $this->text('pci_Description_PCI')),
            self::line('Final result', $this->finalResult()),
            self::line('PCI induced complication', $this->complications()),
            self::line('Second day labs', $this->join([
                ($cr = $this->text('pci_CREATININE')) !== null ? "Cr = {$cr} mg/dl" : null,
                ($hb = $this->text('pci_HB')) !== null ? "Hb = {$hb} g/dl" : null,
            ]) ?: null),
            self::line('Death during procedure', $this->yes('Death_During_procedure')
                ? ($this->join([$this->text('Cause_death'), $this->dateTime('DateOf_Date_death', 'time_death')]) ?: 'Yes')
                : null),
            self::line('Recommendation', $this->text('recommendation')),
            self::line('Other explanations', $this->text('other_explanations')),
            self::line('Nurse', $this->text('Nurse_name')),
            self::line('Entrance', $this->dateTime('DateOf_entrance_date', 'entrance_time')),
            self::line('Exit', $this->dateTime('DateOf_exit_date', 'exit_time')),
        ]);

        return $sections;
    }

    private function introduction(): string
    {
        $indications = $this->yesCaptions([
            'PCI_for_elective_status', 'PCI_for_STEMI_unstable', 'PCI_for_STEMI_stable_higherthan_12hrs',
            'PCI_for_STEMI_stable_after_succesful_thrombolysis', 'Rescue_PCI_for_STEMI_after_faild_thrombolysis',
            'PCI_for_highrisk_nonSTEMI', 'PCI_for_iatrogenic_with_complication', 'Repeated_PCI_for_treating_prior_PCI_complication',
            'staged_PCI', 'Neglected_MI',
        ]);
        $indications[] = $this->text('other_Indication');
        $indications = $this->join($indications);

        $text = 'The patient is '.($this->ageAndGender() ?? 'a patient');
        if ($indications !== '') {
            $text .= ", admitted for {$indications}";
        }
        if ($date = $this->dateTime('DateOf_Date_Balloon_inflation', 'Time_Balloon_inflation')) {
            $text .= ". Balloon inflation: {$date}";
        }

        return self::sentence($text);
    }

    private function technique(): ?string
    {
        $parts = [];

        if ($access = $this->choiceOrOther('arterial_Access_site', 'other_Access')) {
            $parts[] = "{$access} access";
        }
        $agent = $this->is('contrast_agent_1', '-1') ? null : $this->choiceOrOther('contrast_agent_1', 'other_contrast_agent_1');
        $volume = $this->text('contrast_volume');
        if ($agent || $volume) {
            $parts[] = 'contrast: '.$this->join([$agent, $volume !== null ? "{$volume} cc" : null], ' ');
        }
        if (($dose = $this->text('fluro_dose')) !== null) {
            $parts[] = "fluoroscopy dose {$dose} mGy";
        }

        return $parts ? ucfirst($this->join($parts)).'.' : null;
    }

    private function medications(): ?string
    {
        $drugs = $this->yesCaptions(['aspirin', 'heparin', 'IV_TNG', 'clopidogrel', 'prasugrel', 'ticagrelor']);
        $drugs[] = $this->text('others_procedure');

        return $this->join($drugs) ?: null;
    }

    private function territory(string $t): ?string
    {
        if ($this->raw("{$t}_territory_of_intervention") === null || $this->is("{$t}_territory_of_intervention", '0')) {
            return null;
        }

        return $this->choiceOrOther("{$t}_territory_of_intervention", "{$t}_other_territory");
    }

    /**
     * @return list<array{label: string|null, text: string}|null>
     */
    private function intervention(string $t): array
    {
        $tools = $this->join([
            ($catheter = $this->choiceOrOther("{$t}_guiding_catheters_name1", "{$t}_other_guiding_catheters_name1")) ? "{$catheter} guiding catheter" : null,
            ($wire = $this->choiceOrOther("{$t}_guide_wires_name1", "{$t}_other_guide_wires_name1")) ? "{$wire} guide wire" : null,
        ], ' and ');

        $lesion = [
            $this->text("{$t}_description_of_lesion"),
            $this->text("{$t}_lesion_length"),
            $this->text("{$t}_thrombosis"),
            $this->text("{$t}_calcification"),
            $this->yes("{$t}_in_stent_restenosis") ? 'in-stent restenosis' : null,
            $this->text("{$t}_other_lesion"),
        ];

        $lines = [
            self::line('Equipment', $tools !== '' ? ucfirst($tools).'.' : null),
            self::line('Lesion', $this->join($lesion) ?: null),
            self::line('Aspiration thrombectomy', $this->yes("{$t}_aspiration_thrombectomy") ? self::sentence(($this->text("{$t}_name_of_device") ?? 'Done').'') : null),
            self::line('Predilation', $this->balloon($t, "{$t}_predilatation", 'pre')),
            self::line('Balloon only angioplasty (POBA)', $this->yes("{$t}_balloon_angioplasty")
                ? 'Done with balloon ('.$this->balloonSpec("{$t}_angioplasty_BalloonName", "{$t}_angioplasty_BalloonSize", "{$t}_angioplasty_BalloonPressure").').'
                : null),
            ...$this->bifurcation($t),
            ...$this->stents($t),
            self::line('Postdilation', $this->balloon($t, "{$t}_postdilatation", 'post')),
            self::line('TIMI flow', $this->timi($t)),
            self::line('Result', $this->territoryResult($t)),
            self::line('Vessel complication', $this->vesselComplication($t)),
            self::line('Management of complication', $this->text("{$t}_VesselComplicationAction")),
            self::line('Result of management', $this->text("{$t}_VesselComplicationResult")),
        ];

        return $lines;
    }

    private function balloon(string $t, string $done, string $kind): ?string
    {
        if ($this->is($done, '0')) {
            return 'Not done.';
        }
        if (! $this->yes($done)) {
            return null;
        }

        $spec = $this->balloonSpec("{$t}_{$kind}_BalloonName", "{$t}_{$kind}_BalloonSize", "{$t}_{$kind}_BalloonPressure");

        return 'Performed with balloon'.($spec !== '' ? " ({$spec})" : '').'.';
    }

    private function balloonSpec(string $name, string $size, string $pressure): string
    {
        return $this->join([
            $this->text($name),
            $this->text($size),
            ($atm = $this->text($pressure)) !== null ? "{$atm} ATM" : null,
        ]);
    }

    /**
     * @return list<array{label: string|null, text: string}|null>
     */
    private function bifurcation(string $t): array
    {
        $lines = [];

        // Provisional technique (t1 only on heart's form): stent in the main vessel, balloon in the branch.
        if ($this->has("{$t}_Bifurcatinglesion") && $this->yes("{$t}_Bifurcatinglesion")) {
            $site = $this->choiceOrOther("{$t}_Bifurcation_Stenting_Site", "{$t}_other_Bifurcation");
            $lines[] = self::line('Bifurcating lesion', self::sentence($this->join([
                $site ? "{$site} intervention" : 'Intervention',
                'stent ('.$this->join([$this->text("{$t}_Name_Stent_Main_Vessel"), $this->text("{$t}_stent_main_size")]).') in the main vessel',
                'balloon angioplasty ('.$this->join([$this->text("{$t}_name_balloon_branch"), $this->text("{$t}_size_balloon_branch")]).') in the branch',
            ])));
        }

        if ($this->has("{$t}_BifurcationStenting") && $this->yes("{$t}_BifurcationStenting")) {
            $mainName = $this->has("{$t}_namestentMainVessele") ? "{$t}_namestentMainVessele" : "{$t}_NameStentMainVessel";
            $branchName = $this->has("{$t}_namestentbranch") ? "{$t}_namestentbranch" : "{$t}_NameStentBranch";
            $site = $this->choiceOrOther("{$t}_BifurcationStentingSite", "{$t}_otherBifurcation");
            $technique = $this->choiceOrOther("{$t}_nameTechnique", "{$t}_othertechnique");

            $lines[] = self::line('Bifurcation stenting', self::sentence($this->join([
                $site ? "{$site} intervention" : 'Intervention',
                'stent ('.$this->join([$this->text($mainName), $this->text("{$t}_stentmainsize")]).') in the main vessel',
                'stent ('.$this->join([$this->text($branchName), $this->text("{$t}_stentbranchsize")]).') in the branch',
                $technique ? "{$technique} technique" : null,
                $this->is("{$t}_finalKBS", '1') ? 'final kissing balloon done' : ($this->is("{$t}_finalKBS", '2') ? 'final kissing balloon not done' : null),
            ])));
        }

        return $lines;
    }

    /**
     * @return list<array{label: string|null, text: string}|null>
     */
    private function stents(string $t): array
    {
        $lines = [];
        $names = [1 => ['First stent', ''], 2 => ['Second stent', '2'], 3 => ['Third stent', '3']];

        foreach ($names as $n => [$label, $suffix]) {
            if ($this->raw("{$t}_stentType{$n}") === null || $this->is("{$t}_stentType{$n}", '0')) {
                continue;
            }

            $spec = $this->join([
                $this->text("{$t}_stentType{$n}"),
                $this->text("{$t}_nameOfstent{$suffix}"),
                $this->text("{$t}_stentSize{$suffix}"),
                ($atm = $this->text("{$t}_stentPressure{$suffix}")) !== null ? "{$atm} ATM" : null,
            ]);
            $sites = $this->yesCaptions(["{$t}_s{$n}_osteoproximal", "{$t}_s{$n}_proximal", "{$t}_s{$n}_MidPart", "{$t}_s{$n}_distalPart"]);

            $text = "Coronary stent ({$spec}) was implanted".($sites ? ' at '.$this->join($sites) : '');
            if ($n > 1 && $this->is("{$t}_s{$n}_stentOverlapping", '1')) {
                $text .= ', with stent overlapping';
            } elseif ($n > 1 && $this->is("{$t}_s{$n}_stentOverlapping", '0')) {
                $text .= ', without stent overlapping';
            }

            $lines[] = self::line($label, self::sentence($text));
        }

        return $lines;
    }

    private function timi(string $t): ?string
    {
        $pre = $this->text("{$t}_preprocedural");
        $post = $this->text("{$t}_postprocedural");

        return $pre === null && $post === null ? null : 'pre-procedural '.($pre ?? '-').', post-procedural '.($post ?? '-');
    }

    private function territoryResult(string $t): ?string
    {
        if ($this->is("{$t}_Result_Of_PCI_1", '99')) {
            return $this->text("{$t}_other_Result_Of_PCI_1") ?? $this->text("{$t}_Result_Of_PCI_1");
        }

        $result = $this->text("{$t}_Result_Of_PCI_1");
        if ($result !== null && $this->is("{$t}_Result_Of_PCI_1", '2')) {
            $reasons = $this->yesCaptions(["{$t}_inabilityCatheterEngagment", "{$t}_inabilityOfGWCrossing", "{$t}_inabilityOfBalloonCrossing", "{$t}_inabilityOfStentCrossing"]);
            $reasons[] = $this->text("{$t}_other_unsuccessful_PCl");
            $reasons = $this->join($reasons);
            $result .= $reasons !== '' ? " ({$reasons})" : '';
        }

        return $result;
    }

    private function vesselComplication(string $t): ?string
    {
        $names = ["{$t}_no_reflow", "{$t}_stentThrombosis", "{$t}_vesselDissection", "{$t}_vesselPerforation"];
        $complications = $this->yesCaptions($names);
        $complications[] = $this->text("{$t}_othervesselcomplication");
        $complications = $this->join($complications);

        if ($complications !== '') {
            return $complications;
        }

        $answered = array_filter($names, fn (string $name) => $this->is($name, '0'));

        return $answered ? 'No complication.' : null;
    }

    /**
     * "successful PCI on LAD (2 stents); Unsuccessful PCI on RCA", as heart summarised several territories.
     */
    private function finalResult(): ?string
    {
        $results = [];

        foreach (self::TERRITORIES as $t) {
            $territory = $this->territory($t);
            $result = $this->is("{$t}_Result_Of_PCI_1", '1', '2') ? $this->text("{$t}_Result_Of_PCI_1") : null;
            if ($territory === null || $result === null) {
                continue;
            }

            $stents = count(array_filter([1, 2, 3], fn (int $n) => $this->raw("{$t}_stentType{$n}") !== null && ! $this->is("{$t}_stentType{$n}", '0')));
            $results[] = "{$result} on {$territory}".($stents ? ' ('.$stents.' '.($stents === 1 ? 'stent' : 'stents').')' : '');
        }

        return count($results) > 0 ? $this->join($results, '; ') : null;
    }

    private function complications(): ?string
    {
        $names = [
            'pci_Cardiogenic_shock', 'pci_Tamponade', 'pci_Hematoma_needed_treatment', 'pci_Retroperitoneal_bleeding',
            'pci_CI_Bleeding', 'pci_GU_Bleeding', 'pci_CVA_stroke', 'pci_VT_VF_needed_cardio_version',
            'pci_CPR_during_procedure', 'pci_Anaphylactic_reaction', 'pci_Transfusion', 'pci_Emergent_CABG',
        ];
        $parts = $this->yesCaptions($names);

        // Heart wrote "no complication" only when every complication was answered "No" (code 2).
        if ($parts === [] && count(array_filter($names, fn (string $name) => $this->is($name, '2'))) === count($names)) {
            $parts[] = 'No complication';
        }
        $parts[] = $this->text('pci_management_complication');

        return $this->join($parts) ?: null;
    }
}
