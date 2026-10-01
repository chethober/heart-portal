<?php
/**
 * The echo report document itself: shared by the report page (studies/report.blade.php) and the
 * form's live preview (studies/preview.php). Styles are in report-styles.php, scoped to .echo-report.
 *
 * @var array<string, mixed>|null $report  Output of EchoCalculations::derive(), or null.
 * @var Hospital $hospital
 * @var EchoStudy|null $study  The saved study (null in preview).
 * @var EchoStudyVersion|null $version  Set when viewing an earlier signed version.
 */

use App\Enums\StudyStatus;
use App\Models\EchoStudy;
use App\Models\EchoStudyVersion;
use App\Models\Hospital;
use App\Support\JalaliDate;
use App\Support\ReportHtml;
use Illuminate\Support\Facades\Storage;

$study ??= null;
$version ??= null;
$shownStatus = $version?->status ?? $study?->status;
$signer = match (true) {
    $version !== null => $version->signer,
    $shownStatus === StudyStatus::Amended => $study->amender,
    $shownStatus === StudyStatus::Final => $study->reporter,
    default => null,
};
$signedAt = $version?->created_at ?? ($shownStatus === StudyStatus::Amended ? $study?->amended_at : $study?->finalized_at);

// --- Helper Functions ---
if (! function_exists('getDisplayText')) {
    function getDisplayText($value, $options)
    {
        global $enumMappings;
        if (
            $value === null ||
            $value === '' ||
            $value === '-1' ||
            (is_string($value) && trim($value) === '')
        ) {
            return '';
        }
        if (is_array($value)) {
            $results = [];
            foreach ($value as $item) {
                $r = getDisplayText($item, $options);
                if ($r !== '') {
                    $results[] = $r;
                }
            }

            return ! empty($results) ? implode(', ', $results) : '';
        }
        if (
            is_string($value) &&
            ($decoded = json_decode($value, true)) !== null
        ) {
            return getDisplayText($decoded, $options);
        }
        if (is_string($options) && isset($enumMappings[$options])) {
            $options = $enumMappings[$options];
        }
        if (! is_array($options)) {
            return $value;
        }

        $valueStr = (string) $value;
        foreach ($options as $option) {
            if (
                isset($option['value']) &&
                (string) $option['value'] === $valueStr
            ) {
                return $option['text'] ?? '';
            }
        }

        return '';
    }
}

if (! function_exists('shouldDisplay')) {
    function shouldDisplay($value, $fieldName = null)
    {
        if (
            $value === null ||
            $value === '' ||
            $value === '-1' ||
            $value === -1
        ) {
            return false;
        }
        if (
            is_string($fieldName) &&
            (strpos($fieldName, '_stenosis') !== false ||
                strpos($fieldName, '_regurgitant') !== false)
        ) {
            if (is_numeric($value) && $value <= 0) {
                return false;
            }
        }
        if ($value === 0 || $value === '0') {
            return true;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if (
                preg_match(
                    '/^(none|no|not present|not detected|n\/a|na|undefined|null)$/i',
                    $trimmed,
                )
            ) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('getAutoTooltipHtml')) {
    function getAutoTooltipHtml($fieldKey, $gender, $inputUsed, $sizeText, $inputValue = null, $report = null)
    {
        $isMale = (strcasecmp($gender, 'Male') === 0 || $gender === '1');
        $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');
        $genderText = $isMale ? 'Male' : ($isFemale ? 'Female' : 'Unknown');

        $title = '';
        $headers = [];
        $rows = [];

        if ($fieldKey === 'cp_SPAP') {
            $title = 'SPAP Calculation & RAP Reference';

            $trg = null;
            $rap = null;
            $ivc = null;
            $resp = null;
            $spap = null;

            if ($report && is_array($report)) {
                if (isset($report['tv_TRG']) && is_numeric($report['tv_TRG'])) {
                    $trg = (float) $report['tv_TRG'];
                }
                if (isset($report['cp_ivc_size']) && is_numeric($report['cp_ivc_size'])) {
                    $ivc = (float) $report['cp_ivc_size'];
                }
                if (! empty($report['cp_Respiratory_variation'])) {
                    $resp = trim((string) $report['cp_Respiratory_variation']);
                }
                if (isset($report['cp_SPAP']) && is_numeric($report['cp_SPAP'])) {
                    $spap = (float) $report['cp_SPAP'];
                }
                if (isset($report['cp_SPAP_rap']) && is_numeric($report['cp_SPAP_rap'])) {
                    $rap = (float) $report['cp_SPAP_rap'];
                }
            }

            if ($rap === null && $ivc !== null && ! empty($resp) && strtolower($resp) !== 'none') {
                $isOver50 = (strpos(strtolower($resp), '>50%') !== false || strpos(strtolower($resp), '> 50%') !== false);
                $rap = ($ivc <= 21) ? ($isOver50 ? 3 : 8) : ($isOver50 ? 8 : 15);
            }

            $infoBoxHtml = '';
            if ($trg !== null && $rap !== null) {
                $finalSpap = ($spap !== null) ? $spap : round($trg + $rap, 1);
                $infoBoxHtml = '<div style="margin: 4px 0 6px 0; padding: 4px 6px; background: #f7fafc; border-radius: 4px; border: 1px solid #edf2f7; font-size: 9.5px; line-height: 1.4;">'.
                    '<div><strong>Formula:</strong> SPAP = Peak TRG + Estimated RAP</div>'.
                    '<div><strong>TRG:</strong> '.$trg.' mmHg | <strong>Estimated RAP:</strong> '.$rap.' mmHg</div>'.
                    ($ivc !== null ? '<div><strong>IVC:</strong> '.$ivc.' mm'.(! empty($resp) ? ' ('.htmlspecialchars($resp).')' : '').'</div>' : '').
                    '<div style="border-top: 1px dashed #cbd5e0; margin-top: 3px; padding-top: 3px; font-weight: bold; color: #2b6cb0;">'.
                    'Calculation: '.$trg.' + '.$rap.' = '.$finalSpap.' mmHg'.
                    '</div>'.
                    '</div>';
            } else {
                $infoBoxHtml = '<div style="margin: 4px 0 6px 0; padding: 4px 6px; background: #f7fafc; border-radius: 4px; border: 1px solid #edf2f7; font-size: 9.5px; line-height: 1.4;">'.
                    '<div><strong>Formula:</strong> SPAP = Peak TRG (mmHg) + Estimated RAP (mmHg)</div>'.
                    '<div style="color: #718096; font-size: 9px;">RAP is estimated from IVC diameter &amp; inspiratory collapse.</div>'.
                    '</div>';
            }

            $headers = ['IVC Diameter', 'Collapse', 'Est. RAP', 'Range'];
            $rapTableRows = [
                [
                    'ivc' => '≤ 21 mm',
                    'collapse' => '> 50%',
                    'rap' => '3 mmHg',
                    'range' => '0–5 mmHg (Normal)',
                    'match' => ($ivc !== null && $ivc <= 21 && $resp !== null && (strpos(strtolower($resp), '>50%') !== false || strpos(strtolower($resp), '> 50%') !== false)),
                ],
                [
                    'ivc' => '≤ 21 mm',
                    'collapse' => '< 50%',
                    'rap' => '8 mmHg',
                    'range' => '5–10 mmHg (Intermediate)',
                    'match' => ($ivc !== null && $ivc <= 21 && $resp !== null && (strpos(strtolower($resp), '<50%') !== false || strpos(strtolower($resp), '< 50%') !== false || strpos(strtolower($resp), 'no respiratory variation') !== false)),
                ],
                [
                    'ivc' => '> 21 mm',
                    'collapse' => '> 50%',
                    'rap' => '8 mmHg',
                    'range' => '5–10 mmHg (Intermediate)',
                    'match' => ($ivc !== null && $ivc > 21 && $resp !== null && (strpos(strtolower($resp), '>50%') !== false || strpos(strtolower($resp), '> 50%') !== false)),
                ],
                [
                    'ivc' => '> 21 mm',
                    'collapse' => '< 50%',
                    'rap' => '15 mmHg',
                    'range' => '10–20 mmHg (High)',
                    'match' => ($ivc !== null && $ivc > 21 && $resp !== null && (strpos(strtolower($resp), '<50%') !== false || strpos(strtolower($resp), '< 50%') !== false || strpos(strtolower($resp), 'no respiratory variation') !== false)),
                ],
            ];

            $boxWidth = '330px';
            $html = '<span class="tooltip-container">'.htmlspecialchars($sizeText);
            $html .= '<span class="tooltip-table-box no-print" style="width: '.$boxWidth.';">';
            $html .= '<strong style="display: block; margin-bottom: 5px; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">'.htmlspecialchars($title).'</strong>';
            $html .= $infoBoxHtml;
            $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 10px;">';
            $html .= '<thead><tr style="background-color: #f7fafc;">';
            foreach ($headers as $h) {
                $html .= '<th style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center; font-weight: bold; color: #4a5568;">'.htmlspecialchars($h).'</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rapTableRows as $r) {
                $rowStyle = $r['match'] ? ' style="background-color: #ebf8ff; color: #2b6cb0; font-weight: bold;"' : '';
                $html .= '<tr'.$rowStyle.'>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['ivc']).'</td>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['collapse']).'</td>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['rap']).'</td>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['range']).'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></span></span>';

            return $html;
        }

        if ($fieldKey === 'cp_quantity') {
            $title = 'Pulmonary Hypertension (PH) Grading';

            $spap = null;
            if ($report && is_array($report) && isset($report['cp_SPAP']) && is_numeric($report['cp_SPAP'])) {
                $spap = (float) $report['cp_SPAP'];
            } elseif ($inputValue !== null && is_numeric($inputValue)) {
                $spap = (float) $inputValue;
            }

            $infoBoxHtml = '';
            if ($spap !== null) {
                $infoBoxHtml = '<div style="margin: 4px 0 6px 0; padding: 4px 6px; background: #f7fafc; border-radius: 4px; border: 1px solid #edf2f7; font-size: 9.5px; line-height: 1.4;">'.
                    '<div><strong>Current SPAP:</strong> '.$spap.' mmHg</div>'.
                    '<div><strong>Classification:</strong> '.htmlspecialchars($sizeText).'</div>'.
                    '</div>';
            } else {
                $infoBoxHtml = '<div style="margin: 4px 0 6px 0; padding: 4px 6px; background: #f7fafc; border-radius: 4px; border: 1px solid #edf2f7; font-size: 9.5px; line-height: 1.4;">'.
                    '<div style="color: #718096; font-size: 9px;">Grading of Pulmonary Hypertension based on systolic PAP.</div>'.
                    '</div>';
            }

            $headers = ['Grading / Severity', 'SPAP Threshold'];
            $phRows = [
                [
                    'grade' => 'Normal PAP',
                    'range' => '< 35 mmHg',
                    'match' => ($spap !== null ? ($spap < 35) : (stripos($sizeText, 'Normal') !== false)),
                ],
                [
                    'grade' => 'Mild PH',
                    'range' => '35–44 mmHg',
                    'match' => ($spap !== null ? ($spap >= 35 && $spap <= 44) : (stripos($sizeText, 'Mild') !== false)),
                ],
                [
                    'grade' => 'Moderate PH',
                    'range' => '45–59 mmHg',
                    'match' => ($spap !== null ? ($spap >= 45 && $spap <= 59) : (stripos($sizeText, 'Moderate') !== false)),
                ],
                [
                    'grade' => 'Severe PH',
                    'range' => '≥ 60 mmHg',
                    'match' => ($spap !== null ? ($spap >= 60) : (stripos($sizeText, 'Severe') !== false)),
                ],
            ];

            $boxWidth = '260px';
            $html = '<span class="tooltip-container">'.htmlspecialchars($sizeText);
            $html .= '<span class="tooltip-table-box no-print" style="width: '.$boxWidth.';">';
            $html .= '<strong style="display: block; margin-bottom: 5px; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">'.htmlspecialchars($title).'</strong>';
            $html .= $infoBoxHtml;
            $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 10px;">';
            $html .= '<thead><tr style="background-color: #f7fafc;">';
            foreach ($headers as $h) {
                $html .= '<th style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center; font-weight: bold; color: #4a5568;">'.htmlspecialchars($h).'</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($phRows as $r) {
                $rowStyle = $r['match'] ? ' style="background-color: #ebf8ff; color: #2b6cb0; font-weight: bold;"' : '';
                $html .= '<tr'.$rowStyle.'>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;"><strong>'.htmlspecialchars($r['grade']).'</strong></td>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['range']).'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></span></span>';

            return $html;
        }

        if ($fieldKey === 'av_aorta') {
            $title = 'Aorta Reference Ranges ('.$genderText.')';
            $bsa = isset($report['computed_BSA']) ? (float) $report['computed_BSA'] : 0;
            $infoBoxHtml = '';
            if ($bsa > 0) {
                $infoBoxHtml = '<div style="margin: 4px 0 6px 0; padding: 4px 6px; background: #f7fafc; border-radius: 4px; border: 1px solid #edf2f7; font-size: 9.5px; line-height: 1.4;">'.
                    '<div><strong>BSA:</strong> '.round($bsa, 2).' m² | <strong>Indexed formula:</strong> size / BSA</div>'.
                    '</div>';
            }

            $headers = ['Segment', 'Value', 'Abs Limit', 'Indexed', 'Idx Limit'];
            $aortaTableRows = [];

            $segs = [
                [
                    'name' => 'Annulus',
                    'key' => 'av_annulus',
                    'alt' => 'AV_annulus',
                    'absLimit' => $isFemale ? 25 : 29,
                    'idxLimit' => 14,
                ],
                [
                    'name' => 'Sinuses of Valsalva',
                    'key' => 'av_sinus_valsalva',
                    'alt' => 'Sinus_valsalva',
                    'absLimit' => $isFemale ? 33 : 37,
                    'idxLimit' => $isFemale ? 20 : 19,
                ],
                [
                    'name' => 'Sinotubular Junction',
                    'key' => 'av_st_junction',
                    'alt' => 'ST_junction',
                    'absLimit' => $isFemale ? 29 : 32,
                    'idxLimit' => 17,
                ],
                [
                    'name' => 'Proximal Ascending Aorta',
                    'key' => 'av_proximal_ascending_aorta',
                    'alt' => 'ascending_aorta',
                    'absLimit' => $isFemale ? 31 : 34,
                    'idxLimit' => $isFemale ? 19 : 17,
                ],
            ];

            foreach ($segs as $s) {
                $val = 0;
                if ($report && is_array($report)) {
                    if (isset($report[$s['key']]) && is_numeric($report[$s['key']])) {
                        $val = (float) $report[$s['key']];
                    } elseif (isset($report[$s['alt']]) && is_numeric($report[$s['alt']])) {
                        $val = (float) $report[$s['alt']];
                    }
                }
                $idxVal = ($val > 0 && $bsa > 0) ? round($val / $bsa, 1) : 0;
                $isDilated = ($val > 0) && ($val > $s['absLimit'] || ($bsa > 0 && $idxVal > $s['idxLimit']));

                $aortaTableRows[] = [
                    'segment' => $s['name'],
                    'val' => ($val > 0 ? $val.' mm' : '-'),
                    'absLimit' => '≤ '.$s['absLimit'].' mm',
                    'indexed' => ($idxVal > 0 ? $idxVal.' mm/m²' : '-'),
                    'idxLimit' => '≤ '.$s['idxLimit'].' mm/m²',
                    'match' => $isDilated,
                ];
            }

            $boxWidth = '340px';
            $html = '<span class="tooltip-container">'.htmlspecialchars($sizeText);
            $html .= '<span class="tooltip-table-box no-print" style="width: '.$boxWidth.';">';
            $html .= '<strong style="display: block; margin-bottom: 5px; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">'.htmlspecialchars($title).'</strong>';
            $html .= $infoBoxHtml;
            $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 10px;">';
            $html .= '<thead><tr style="background-color: #f7fafc;">';
            foreach ($headers as $h) {
                $html .= '<th style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center; font-weight: bold; color: #4a5568;">'.htmlspecialchars($h).'</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($aortaTableRows as $r) {
                $rowStyle = $r['match'] ? ' style="background-color: #fff5f5; color: #c53030; font-weight: bold;"' : '';
                $html .= '<tr'.$rowStyle.'>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: left;"><strong>'.htmlspecialchars($r['segment']).'</strong></td>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['val']).'</td>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['absLimit']).'</td>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['indexed']).'</td>';
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r['idxLimit']).'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></span></span>';

            return $html;
        }

        if ($fieldKey === 'lv_size') {
            $title = 'LV Size Reference Ranges ('.$genderText.')';
            $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev'];
            if ($isFemale) {
                $rows = [
                    'LVEDVI' => ['unit' => 'cc/m²', 'normal' => '≤ 61', 'mild' => '62–70', 'mod' => '71–80', 'sev' => '> 80'],
                    'LVEDV' => ['unit' => 'cc',    'normal' => '≤ 106', 'mild' => '107–120', 'mod' => '121–130', 'sev' => '> 130'],
                    'LVEDDI' => ['unit' => 'mm/m²', 'normal' => '≤ 31', 'mild' => '32–34', 'mod' => '35–37', 'sev' => '> 37'],
                    'LVEDD' => ['unit' => 'mm',    'normal' => '≤ 52', 'mild' => '53–56', 'mod' => '57–61', 'sev' => '> 61'],
                ];
            } else {
                $rows = [
                    'LVEDVI' => ['unit' => 'cc/m²', 'normal' => '≤ 74', 'mild' => '75–89', 'mod' => '90–100', 'sev' => '> 100'],
                    'LVEDV' => ['unit' => 'cc',    'normal' => '≤ 150', 'mild' => '151–174', 'mod' => '175–200', 'sev' => '> 200'],
                    'LVEDDI' => ['unit' => 'mm/m²', 'normal' => '≤ 30', 'mild' => '31–33', 'mod' => '34–36', 'sev' => '> 36'],
                    'LVEDD' => ['unit' => 'mm',    'normal' => '≤ 58', 'mild' => '59–63', 'mod' => '64–68', 'sev' => '> 68'],
                ];
            }
        } elseif ($fieldKey === 'lv_Systolic') {
            $title = 'LV Systolic Reference Ranges ('.$genderText.')';
            $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev'];
            if ($isFemale) {
                $rows = [
                    'EF' => ['unit' => '%', 'normal' => '≥ 54', 'mild' => '41–53', 'mod' => '30–40', 'sev' => '< 30'],
                ];
            } else {
                $rows = [
                    'EF' => ['unit' => '%', 'normal' => '≥ 52', 'mild' => '41–51', 'mod' => '30–40', 'sev' => '< 30'],
                ];
            }
        } elseif ($fieldKey === 'lv_Hypertrophy') {
            $title = 'LV Hypertrophy Reference Ranges ('.$genderText.')';
            $headers = ['Parameter', 'No LVH', 'Mild', 'Mod', 'Sev'];
            if ($isFemale) {
                $rows = [
                    'IVS' => ['unit' => 'mm', 'normal' => '< 10', 'mild' => '10–12.9', 'mod' => '13–15', 'sev' => '> 15'],
                ];
            } else {
                $rows = [
                    'IVS' => ['unit' => 'mm', 'normal' => '< 11', 'mild' => '11–13.9', 'mod' => '14–16', 'sev' => '> 16'],
                ];
            }
        } elseif ($fieldKey === 'lv_Diastolic') {
            $title = 'LV Diastolic Function Algorithm';
            $headers = ['Parameter / Category', 'Clinical Criteria / Grading'];
            $rows = [
                "Reduced e' velocity" => ['unit' => 'cm/s', 'criteria' => 'septal ≤ 6, lateral ≤ 7, average ≤ 6.5'],
                "Increased E/e'" => ['unit' => '', 'criteria' => 'septal ≥ 15, lateral ≥ 13, average ≥ 14'],
                'TR Vel / PASP' => ['unit' => '', 'criteria' => 'TR velocity ≥ 2.8 m/s or PASP ≥ 35 mmHg'],
                'All Normal Group' => ['unit' => '', 'criteria' => 'Normal Diastolic Function'],
                "Reduced e' only Group" => ['unit' => '', 'criteria' => 'E/A ≤ 0.8: Grade I; E/A > 0.8: Indeterminate'],
                '3 Abnormal Group' => ['unit' => '', 'criteria' => 'E/A < 2: Grade II; E/A ≥ 2: Grade III'],
                '1-2 Abnormal Group' => ['unit' => '', 'criteria' => 'Indeterminate Diastolic Function'],
            ];
        } elseif ($fieldKey === 'rv_size') {
            $title = 'RV Size Reference Ranges';
            $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev'];
            $rows = [
                'RV Basal' => ['unit' => 'mm', 'normal' => '≤ 40', 'mild' => '41–44', 'mod' => '45–49', 'sev' => '> 49'],
                'RV Mid' => ['unit' => 'mm', 'normal' => '≤ 35', 'mild' => '36–38', 'mod' => '39–43', 'sev' => '> 43'],
            ];
        } elseif ($fieldKey === 'rv_Systolic') {
            $title = 'RV Systolic Reference Ranges';
            $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev'];
            $rows = [
                'TAPSE' => ['unit' => 'mm', 'normal' => '> 17', 'mild' => '14–17', 'mod' => '10–13', 'sev' => '< 10'],
            ];
        } elseif ($fieldKey === 'la_size') {
            $title = 'LA Size Reference Ranges ('.$genderText.')';
            $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev / Enlarged'];
            if ($isFemale) {
                $rows = [
                    'LAVI' => ['unit' => 'cc/m²', 'normal' => '≤ 34', 'mild' => '35–41', 'mod' => '42–48', 'sev' => '> 48'],
                    'LA Area' => ['unit' => 'cm²',   'normal' => '≤ 20', 'mild' => '21–30', 'mod' => '31–41', 'sev' => '> 41'],
                    'LA Diam' => ['unit' => 'mm',    'normal' => '≤ 38', 'mild' => 'N/A',   'mod' => 'N/A',   'sev' => '> 38'],
                    'LADI' => ['unit' => 'mm/m²', 'normal' => '≤ 23', 'mild' => 'N/A',   'mod' => 'N/A',   'sev' => '> 23'],
                ];
            } else {
                $rows = [
                    'LAVI' => ['unit' => 'cc/m²', 'normal' => '≤ 34', 'mild' => '35–41', 'mod' => '42–48', 'sev' => '> 48'],
                    'LA Area' => ['unit' => 'cm²',   'normal' => '≤ 20', 'mild' => '21–30', 'mod' => '31–41', 'sev' => '> 41'],
                    'LA Diam' => ['unit' => 'mm',    'normal' => '≤ 40', 'mild' => 'N/A',   'mod' => 'N/A',   'sev' => '> 40'],
                    'LADI' => ['unit' => 'mm/m²', 'normal' => '≤ 23', 'mild' => 'N/A',   'mod' => 'N/A',   'sev' => '> 23'],
                ];
            }
        } elseif ($fieldKey === 'ra_size') {
            $title = 'RA Size Reference Ranges';
            $headers = ['Parameter', 'Normal', 'Enlarged'];
            $rows = [
                'RA Area' => ['unit' => 'cm²', 'normal' => '< 18', 'enlarged' => '≥ 18'],
            ];
        }

        if (empty($title)) {
            return htmlspecialchars($sizeText);
        }

        $displayInput = $inputUsed;
        if ($inputValue !== null && $inputValue !== '') {
            if ($fieldKey === 'lv_Diastolic') {
                $displayInput = $inputValue;
            } else {
                $units = [
                    'LVEDVI' => 'cc/m²',
                    'LVEDV' => 'cc',
                    'LVEDDI' => 'mm/m²',
                    'LVEDD' => 'mm',
                    'EF' => '%',
                    'IVS' => 'mm',
                    'RV Basal' => 'mm',
                    'RV Mid' => 'mm',
                    'TAPSE' => 'mm',
                    'LAVI' => 'cc/m²',
                    'LA Area' => 'cm²',
                    'LA Diam' => 'mm',
                    'LADI' => 'mm/m²',
                    'RA Area' => 'cm²',
                ];
                $unitStr = isset($units[$inputUsed]) ? ' '.$units[$inputUsed] : '';
                if (trim($unitStr) === '%') {
                    $unitStr = '%';
                }
                $displayInput = $inputUsed.' = '.$inputValue.$unitStr;
            }
        }

        $showInputInLabel = ! empty($displayInput);
        $labelHtml = htmlspecialchars($sizeText).($showInputInLabel ? ' ('.htmlspecialchars($displayInput).')' : '');

        $html = '<span class="tooltip-container">'.$labelHtml;
        $html .= '<span class="tooltip-table-box no-print">';
        $html .= '<strong style="display: block; margin-bottom: 5px; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">'.htmlspecialchars($title).'</strong>';
        $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 10px;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #f7fafc;">';
        foreach ($headers as $h) {
            $html .= '<th style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center; font-weight: bold; color: #4a5568;">'.htmlspecialchars($h).'</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($rows as $param => $r) {
            $isHighlighted = (strcasecmp($param, $inputUsed) === 0);
            $rowStyle = $isHighlighted ? ' style="background-color: #ebf8ff; color: #2b6cb0; font-weight: bold;"' : '';
            $html .= '<tr'.$rowStyle.'>';
            $unitSpan = ! empty($r['unit']) ? ' <span style="color:#718096; font-size:9px;">('.htmlspecialchars($r['unit']).')</span>' : '';
            $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;"><strong>'.htmlspecialchars($param).'</strong>'.$unitSpan.'</td>';

            $keys = array_keys($r);
            foreach ($keys as $k) {
                if ($k === 'unit') {
                    continue;
                }
                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r[$k]).'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</span>';
        $html .= '</span>';

        return $html;
    }
}

// --- Summary Generation Logic ---
if (! function_exists('isAbnormalFinding')) {
    function isAbnormalFinding($text)
    {
        if (empty($text) || ! is_string($text)) {
            return false;
        }
        $stripped = trim(strip_tags($text));
        if ($stripped === '') {
            return false;
        }

        // Explicit normal checks (case-insensitive)
        if (preg_match('/^\s*normal\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/\bnormal\s+(lv|rv|la|ra|aorta|aortic)?\s*(size|function|diastolic|systolic)?\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/\bno\s+(lvh|rvh|rwma|pe|clot|smoky|regurgitation|stenosis|mr|ar|tr|pr|ms|as|ts|ps)\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/\bno\s+la\s+clot\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/\bno\s+lv\s+clot\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/\bno\s+smoky\s+appearance\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/\bno\s+significant\s+abnormalities\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/^\s*with\s+normal\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/^\s*there\s+are\s+normal\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/^\s*there\s+is\s+normal\b/i', $stripped)) {
            return false;
        }
        if (preg_match('/>\s*50%\s*respiratory/i', $stripped)) {
            return false;
        }

        // If it already has <u> tag, it's already marked as abnormal
        if (strpos($text, '<u>') !== false) {
            return true;
        }

        // Abnormal patterns
        if (preg_match('/\b(enlargement|enlarged|dilated|dilation|dilatation|hypertrophy|hypertrophied|lvh|rvh)\b/i', $stripped)) {
            return true;
        }
        if (preg_match('/\b(dysfunction|impaired|reduced|depressed|decreased|increased|elevated)\b/i', $stripped)) {
            return true;
        }
        if (preg_match('/\b(mild|moderate|severe|grade\s+[i|v|x]+|indeterminate)\b/i', $stripped)) {
            return true;
        }
        if (preg_match('/\b(clot|thrombus|smoky|hypokinesia|akinesia|dyskinesia|aneurysm|aneurysmal)\b/i', $stripped)) {
            return true;
        }
        if (preg_match('/\b(regurgitation|stenosis|prolapse|thickened|calcified|calcification|rheumatic|bicuspid|restriction|vegetation|vegetations)\b/i', $stripped)) {
            return true;
        }
        if (preg_match('/\b(mr|ar|tr|pr|ms|as|ts|ps|ph|pulmonary\s+hypertension)\b/i', $stripped) && ! preg_match('/\bno\s+(mr|ar|tr|pr|ms|as|ts|ps)\b/i', $stripped)) {
            return true;
        }
        if (preg_match('/\b(pe|pericardial\s+effusion|tamponade|effusion)\b/i', $stripped) && ! preg_match('/\bno\s+(pe|effusion)\b/i', $stripped)) {
            return true;
        }
        if (preg_match('/<\s*50%\s*respiratory/i', $stripped)) {
            return true;
        }
        if (preg_match('/\b(no\s+respiratory\s+variation|plethoric)\b/i', $stripped)) {
            return true;
        }
        if (preg_match('/\babnormal\b/i', $stripped)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('ensureUnderlinedIfAbnormal')) {
    function ensureUnderlinedIfAbnormal($text)
    {
        if (empty($text)) {
            return $text;
        }
        if (strpos($text, '<u>') !== false) {
            return $text;
        }
        if (isAbnormalFinding($text)) {
            return '<u>'.$text.'</u>';
        }

        return $text;
    }
}

if (! function_exists('generateSummary')) {
    function generateSummary($report)
    {
        $sections = [];

        // Convert common short forms to uppercase in the content
        $convertShortForms = function ($text) {
            $shortForms = [
                'rv' => 'RV',
                'lv' => 'LV',
                'lvh' => 'LVH',
                'rvh' => 'RVH',
                'la' => 'LA',
                'ra' => 'RA',
                'ivc' => 'IVC',
                'pa' => 'PA',
                'ef' => 'EF',
                'tapse' => 'TAPSE',
                'rvedv' => 'RVEDV',
                'rvesv' => 'RVESV',
                'lvedv' => 'LVEDV',
                'lvesv' => 'LVESV',
                'lvedd' => 'LVEDD',
                'lvesd' => 'LVESD',
                'rwma' => 'RWMA',
                'mva' => 'MVA',
                'mpg' => 'MPG',
                'ppg' => 'PPG',
                'vc' => 'VC',
                'dt' => 'DT',
                'spap' => 'SPAP',
                'pht' => 'PHT',
                'sam' => 'SAM',
                'lvot' => 'LVOT',
                'mr' => 'MR',
                'ar' => 'AR',
                'tr' => 'TR',
                'pr' => 'PR',
                'ms' => 'MS',
                'as' => 'AS',
                'ts' => 'TS',
                'ps' => 'PS',
            ];

            // Replace short forms with uppercase versions
            foreach ($shortForms as $short => $upper) {
                $text = preg_replace('/\b'.preg_quote($short, '/').'\b/i', $upper, $text);
            }

            return $text;
        };

        // Helper to format "There is <size>, with <findings>"
        $formatSection = function ($sizeKey, $sizeField, $findings) use ($report, $convertShortForms) {
            $sizeText = '';
            $isManual = false;
            if (shouldDisplay($report[$sizeField] ?? null)) {
                $displayText = getDisplayText($report[$sizeField], $sizeKey);
                if (! empty($displayText)) {
                    $standardOptions = [
                        'normal lv size',
                        'mild lv enlargement',
                        'mild to moderate lv enlargement',
                        'moderate lv enlargement',
                        'moderate to severe lv enlargement',
                        'severe lv enlargement',
                        'normal rv size',
                        'mild rv enlargement',
                        'mild to moderate rv enlargement',
                        'moderate rv enlargement',
                        'moderate to severe rv enlargement',
                        'severe rv enlargement',
                        'normal la size',
                        'mild la enlargement',
                        'moderate la enlargement',
                        'severe la enlargement',
                        'there is la enlargement',
                        'normal ra size',
                        'mild ra enlargement',
                        'moderate ra enlargement',
                        'severe ra enlargement',
                        'ra enlargement',
                    ];
                    $isManual = true;
                    $lowerVal = strtolower(trim($displayText));
                    foreach ($standardOptions as $opt) {
                        if (strpos($lowerVal, $opt) === 0) {
                            $isManual = false;
                            break;
                        }
                    }
                    if ($isManual) {
                        $sizeText = $convertShortForms($displayText);
                    } else {
                        $sizeText = strtolower($displayText);
                        $sizeText = $convertShortForms($sizeText);
                        $sizeText = str_replace('ra area', 'RA Area', $sizeText);
                    }
                }
            }

            if (! empty($sizeText)) {
                $isSizeAbnormal = isAbnormalFinding($sizeText);
                if (! $isManual) {
                    if (strpos($sizeText, 'there is ') === 0) {
                        $sizeText = substr($sizeText, 9);
                    }
                    $was_auto_key = $sizeField.'_was_auto';
                    $input_used_key = $sizeField.'_input_used';
                    $input_val_key = $sizeField.'_input_value';
                    if (! empty($report[$was_auto_key]) && ! empty($report[$input_used_key])) {
                        $sizeText = getAutoTooltipHtml($sizeField, $report['gender'] ?? '', $report[$input_used_key], $sizeText, $report[$input_val_key] ?? null);
                    }
                    if ($isSizeAbnormal) {
                        $sizeText = '<u>'.$sizeText.'</u>';
                    }
                    if (! empty($findings)) {
                        $findingsText = array_map(function ($finding) use ($convertShortForms) {
                            return $convertShortForms($finding);
                        }, $findings);

                        return 'There is '.
                            $sizeText.
                            ', with '.
                            implode(', ', $findingsText).
                            '.';
                    }

                    return 'There is '.$sizeText.'.';
                } else {
                    $manualText = ucfirst($sizeText);
                    if ($isSizeAbnormal) {
                        $manualText = '<u>'.$manualText.'</u>';
                    }
                    if (! empty($findings)) {
                        $findingsText = array_map(function ($finding) use ($convertShortForms) {
                            return $convertShortForms($finding);
                        }, $findings);

                        return $manualText.
                            ', with '.
                            implode(', ', $findingsText).
                            '.';
                    }

                    return $manualText.'.';
                }
            } elseif (! empty($findings)) {
                $findingsText = array_map(function ($finding) use ($convertShortForms) {
                    return $convertShortForms($finding);
                }, $findings);

                return 'There is '.implode(', ', $findingsText).'.';
            }

            return null;
        };

        // 1. Left Ventricle (LV)
        $lvFindings = [];
        if (shouldDisplay($report['lv_Systolic'])) {
            $systolicText = strtolower($report['lv_Systolic']);
            // Only add "systolic function" if "systolic" is not already in the text
            if (strpos($systolicText, 'systolic') === false) {
                $systolicText .= ' systolic function';
            }
            $systolicText = preg_replace('/^with\s+/', '', $systolicText);
            if (! empty($report['lv_Systolic_was_auto']) && ! empty($report['lv_Systolic_input_used'])) {
                $systolicText = getAutoTooltipHtml('lv_Systolic', $report['gender'] ?? '', $report['lv_Systolic_input_used'], $systolicText, $report['lv_Systolic_input_value'] ?? null);
            }
            $lvFindings[] = ensureUnderlinedIfAbnormal($systolicText);
        }
        if (shouldDisplay($report['lv_EF'])) {
            $shownInSystolicTooltip = shouldDisplay($report['lv_Systolic']) &&
                                      ! empty($report['lv_Systolic_was_auto']) &&
                                      $report['lv_Systolic_input_used'] === 'EF';
            if (! $shownInSystolicTooltip) {
                $efVal = (float) $report['lv_EF'];
                $efText = 'ejection fraction of '.$report['lv_EF'].'%';
                $gender = isset($report['gender']) ? trim($report['gender']) : '';
                $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');
                $efCutoff = $isFemale ? 54 : 52;
                if ($efVal < $efCutoff) {
                    $efText = '<u>'.$efText.'</u>';
                }
                $lvFindings[] = $efText;
            }
        }
        if (shouldDisplay($report['lv_Diastolic'])) {
            $diastolicText = $report['lv_Diastolic'];
            if (! empty($report['lv_Diastolic_was_auto']) && ! empty($report['lv_Diastolic_input_used'])) {
                $diastolicText = getAutoTooltipHtml('lv_Diastolic', $report['gender'] ?? '', $report['lv_Diastolic_input_used'], $diastolicText, $report['lv_Diastolic_input_value'] ?? null);
            }
            $lvFindings[] = ensureUnderlinedIfAbnormal($diastolicText);
        }
        if (shouldDisplay($report['lv_Hypertrophy'])) {
            $t = getDisplayText($report['lv_Hypertrophy'], 'lv_Hypertrophy');
            if (! empty($t)) {
                $hyperText = strtolower($t);
                if (! empty($report['lv_Hypertrophy_was_auto']) && ! empty($report['lv_Hypertrophy_input_used'])) {
                    $hyperText = getAutoTooltipHtml('lv_Hypertrophy', $report['gender'] ?? '', $report['lv_Hypertrophy_input_used'], $hyperText, $report['lv_Hypertrophy_input_value'] ?? null);
                }
                $lvFindings[] = ensureUnderlinedIfAbnormal($hyperText);
            }
        }
        if (shouldDisplay($report['lv_clot'])) {
            $lvFindings[] = ensureUnderlinedIfAbnormal(strtolower($report['lv_clot']));
        }
        if (! empty(trim($report['other_des_lv'] ?? ''))) {
            $lvFindings[] = ensureUnderlinedIfAbnormal($report['other_des_lv']);
        }

        $content = $formatSection('lv_size', 'lv_size', $lvFindings);
        if ($content) {
            $sections[] = ['title' => 'Left Ventricle (LV)', 'content' => $content, 'allow_html' => true];
        }

        // 1.5 LV Wall Motion
        // Show LV Wall Motion section if checkbox is checked
        if (shouldDisplay($report['lv_rwma_checked'] ?? null) || shouldDisplay($report['lv_global_hypokinesia'] ?? null)) {
            $rwmaSegments = [
                ['field' => 'lv_Basal_Anterior', 'label' => 'basal anterior', 'territory' => 'LAD'],
                ['field' => 'lv_Basal_anteroseptum', 'label' => 'basal anteroseptal', 'territory' => 'LAD'],
                ['field' => 'lv_Basal_inferoseptum', 'label' => 'basal inferoseptal', 'territory' => 'RCA'],
                ['field' => 'lv_Basal_inferior', 'label' => 'basal inferior', 'territory' => 'RCA'],
                ['field' => 'lv_Basal_inferolateral', 'label' => 'basal inferolateral', 'territory' => 'LCX'],
                ['field' => 'lv_Basal_anterolateral', 'label' => 'basal anterolateral', 'territory' => 'LCX'],
                ['field' => 'lv_Mid_Anterior', 'label' => 'mid anterior', 'territory' => 'LAD'],
                ['field' => 'lv_Mid_anteroseptum', 'label' => 'mid anteroseptal', 'territory' => 'LAD'],
                ['field' => 'lv_Mid_inferoseptum', 'label' => 'mid inferoseptal', 'territory' => 'RCA'],
                ['field' => 'lv_Mid_inferior', 'label' => 'mid inferior', 'territory' => 'RCA'],
                ['field' => 'lv_Mid_inferolateral', 'label' => 'mid inferolateral', 'territory' => 'LCX'],
                ['field' => 'lv_Mid_anterolateral', 'label' => 'mid anterolateral', 'territory' => 'LCX'],
                ['field' => 'lv_Apical_anterior', 'label' => 'apical anterior', 'territory' => 'LAD'],
                ['field' => 'lv_Apical_septum', 'label' => 'apical septal', 'territory' => 'LAD'],
                ['field' => 'lv_Apical_inferior', 'label' => 'apical inferior', 'territory' => 'LAD'],
                ['field' => 'lv_Apical_lateral', 'label' => 'apical lateral', 'territory' => 'LCX'],
                ['field' => 'lv_Apical_cap', 'label' => 'apical cap', 'territory' => 'LAD'],
            ];

            $rwmaFindings = [];
            $rwmaDataPresent = false;
            $territoryGroups = [];

            foreach ($rwmaSegments as $segment) {
                $field = $segment['field'];
                if (shouldDisplay($report[$field] ?? null)) {
                    $rwmaDataPresent = true;
                    $txt = getDisplayText($report[$field], $field);

                    if (! empty($txt) && stripos($txt, 'normal') === false) {
                        $territory = $segment['territory'];
                        if (! isset($territoryGroups[$territory])) {
                            $territoryGroups[$territory] = [];
                        }
                        $territoryGroups[$territory][] = strtolower($txt).' of '.$segment['label'];
                    }
                }
            }

            // Build the final findings grouped by territory
            foreach ($territoryGroups as $territory => $segments) {
                $rwmaFindings[] = implode(', ', $segments).' ('.$territory.' Territory)';
            }

            // Also check global resting state if segments weren't touched but lv_rest was
            if (! $rwmaDataPresent && shouldDisplay($report['lv_rest'] ?? null)) {
                $rwmaDataPresent = true;
            }

            $withSuffix = '';
            if (shouldDisplay($report['lv_with'] ?? null)) {
                if ($report['lv_with'] === '1') {
                    $withSuffix = ' (with preserved tissue thickness)';
                } elseif ($report['lv_with'] === '2') {
                    $withSuffix = ' (with thinning and loss of tissue)';
                }
            }

            $hasGlobal = ! empty($report['lv_global_hypokinesia']);

            if ($hasGlobal && ! empty($rwmaFindings)) {
                $rwmaUnderlined = array_map(function ($f) {
                    return ensureUnderlinedIfAbnormal($f);
                }, $rwmaFindings);
                $content = 'There is '.ensureUnderlinedIfAbnormal('global hypokinesia').', with '.implode(', ', $rwmaUnderlined).$withSuffix.'.';
                $sections[] = [
                    'title' => 'LV Wall Motion',
                    'content' => $content,
                    'allow_html' => true,
                ];
            } elseif ($hasGlobal) {
                $content = 'There is '.ensureUnderlinedIfAbnormal('global hypokinesia').$withSuffix.'.';
                $sections[] = [
                    'title' => 'LV Wall Motion',
                    'content' => $content,
                    'allow_html' => true,
                ];
            } elseif (! empty($rwmaFindings)) {
                $rwmaUnderlined = array_map(function ($f) {
                    return ensureUnderlinedIfAbnormal($f);
                }, $rwmaFindings);
                $content = 'There is '.ucfirst(implode(', ', $rwmaUnderlined)).$withSuffix.'.';
                $sections[] = [
                    'title' => 'LV Wall Motion',
                    'content' => $content,
                    'allow_html' => true,
                ];
            } else {
                $sections[] = [
                    'title' => 'LV Wall Motion',
                    'content' => 'There is no RWMA.',
                    'allow_html' => true,
                ];
            }
        }

        // 2. Right Ventricle (RV)
        $rvFindings = [];
        if (shouldDisplay($report['rv_Systolic'] ?? null)) {
            $systolicText = strtolower($report['rv_Systolic']);
            if (strpos($systolicText, 'systolic') === false) {
                $systolicText .= ' systolic function';
            }
            $systolicText = preg_replace('/^with\s+/', '', $systolicText);
            if (! empty($report['rv_Systolic_was_auto']) && ! empty($report['rv_Systolic_input_used'])) {
                $systolicText = getAutoTooltipHtml('rv_Systolic', $report['gender'] ?? '', $report['rv_Systolic_input_used'], $systolicText, $report['rv_Systolic_input_value'] ?? null);
            }
            $rvFindings[] = ensureUnderlinedIfAbnormal($systolicText);
        }
        if (shouldDisplay($report['rv_TAPSE'])) {
            $shownInSystolicTooltip = shouldDisplay($report['rv_Systolic'] ?? null) &&
                                      ! empty($report['rv_Systolic_was_auto']) &&
                                      $report['rv_Systolic_input_used'] === 'TAPSE';
            if (! $shownInSystolicTooltip) {
                $tapseVal = (float) $report['rv_TAPSE'];
                $tapseText = 'TAPSE of '.$report['rv_TAPSE'].' mm';
                if ($tapseVal <= 17) {
                    $tapseText = '<u>'.$tapseText.'</u>';
                }
                $rvFindings[] = $tapseText;
            }
        }
        if (shouldDisplay($report['rv_S_velocity'])) {
            $sVal = (float) $report['rv_S_velocity'];
            $sText = "S' velocity of ".$report['rv_S_velocity'].' cm/s';
            if ($sVal < 9.5) {
                $sText = '<u>'.$sText.'</u>';
            }
            $rvFindings[] = $sText;
        }
        if (shouldDisplay($report['rv_Hypertrophy'] ?? null)) {
            $t = getDisplayText($report['rv_Hypertrophy'], 'rv_Hypertrophy');
            if (! empty($t)) {
                $rvFindings[] = ensureUnderlinedIfAbnormal(strtolower($t));
            }
        }
        if (! empty(trim($report['rv_other_des'] ?? ''))) {
            $rvFindings[] = ensureUnderlinedIfAbnormal($report['rv_other_des']);
        }

        $content = $formatSection('rv_size', 'rv_size', $rvFindings);
        if ($content) {
            $sections[] = [
                'title' => 'Right Ventricle (RV)',
                'content' => $content,
                'allow_html' => true,
            ];
        }

        // 3. Left Atrium (LA)
        $laFindings = [];
        if (shouldDisplay($report['la_area'])) {
            $shownInSizeTooltip = shouldDisplay($report['la_size'] ?? null) &&
                                  ! empty($report['la_size_was_auto']) &&
                                  $report['la_size_input_used'] === 'LA Area';
            if (! $shownInSizeTooltip) {
                $laAreaVal = (float) $report['la_area'];
                $laAreaText = 'area '.$report['la_area'].' cm²';
                if ($laAreaVal > 20) {
                    $laAreaText = '<u>'.$laAreaText.'</u>';
                }
                $laFindings[] = $laAreaText;
            }
        }
        if (shouldDisplay($report['la_volume'])) {
            $shownInSizeTooltip = shouldDisplay($report['la_size'] ?? null) &&
                                  ! empty($report['la_size_was_auto']) &&
                                  $report['la_size_input_used'] === 'LAVI';
            if (! $shownInSizeTooltip) {
                $laFindings[] = 'volume '.$report['la_volume'].' ml';
            }
        }
        if (shouldDisplay($report['la_diameter'])) {
            $shownInSizeTooltip = shouldDisplay($report['la_size'] ?? null) &&
                                  ! empty($report['la_size_was_auto']) &&
                                  ($report['la_size_input_used'] === 'LA Diam' || $report['la_size_input_used'] === 'LADI');
            if (! $shownInSizeTooltip) {
                $laDiamVal = (float) $report['la_diameter'];
                $laDiamText = 'diameter '.$report['la_diameter'].' mm';
                $gender = isset($report['gender']) ? trim($report['gender']) : '';
                $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');
                $laDiamCutoff = $isFemale ? 38 : 40;
                if ($laDiamVal > $laDiamCutoff) {
                    $laDiamText = '<u>'.$laDiamText.'</u>';
                }
                $laFindings[] = $laDiamText;
            }
        }
        if (shouldDisplay($report['la_smoky'] ?? null) && $report['la_smoky'] != '-1') {
            $smokyText = $report['la_smoky'] == '1' ? 'smoky appearance' : 'no smoky appearance';
            $laFindings[] = ensureUnderlinedIfAbnormal($smokyText);
        }
        if (shouldDisplay($report['la_clot'] ?? null) && $report['la_clot'] != '-1') {
            $clotText = $report['la_clot'] == '1' ? 'LA clot present' : 'no LA clot';
            $laFindings[] = ensureUnderlinedIfAbnormal($clotText);
        }
        if (! empty(trim($report['la_other_des'] ?? ''))) {
            $laFindings[] = ensureUnderlinedIfAbnormal($report['la_other_des']);
        }

        $content = $formatSection('la_size', 'la_size', $laFindings);
        if ($content) {
            $sections[] = ['title' => 'Left Atrium (LA)', 'content' => $content, 'allow_html' => true];
        }

        // 4. Right Atrium (RA)
        $raFindings = [];
        if (shouldDisplay($report['ra_area'])) {
            $shownInSizeTooltip = shouldDisplay($report['ra_size'] ?? null) &&
                                  ! empty($report['ra_size_was_auto']) &&
                                  $report['ra_size_input_used'] === 'RA Area';
            if (! $shownInSizeTooltip) {
                $raAreaVal = (float) $report['ra_area'];
                $raAreaText = 'area '.$report['ra_area'].' cm²';
                if ($raAreaVal >= 18) {
                    $raAreaText = '<u>'.$raAreaText.'</u>';
                }
                $raFindings[] = $raAreaText;
            }
        }
        if (shouldDisplay($report['ra_volume'])) {
            $raFindings[] = 'volume '.$report['ra_volume'].' ml';
        }
        if (! empty(trim($report['ra_other_des'] ?? ''))) {
            $raFindings[] = ensureUnderlinedIfAbnormal($report['ra_other_des']);
        }

        $content = $formatSection('ra_size', 'ra_size', $raFindings);
        if ($content) {
            $sections[] = ['title' => 'Right Atrium (RA)', 'content' => $content, 'allow_html' => true];
        }

        // 5. Valves
        $valveMap = [
            [
                'prefix' => 'mv',
                'title' => 'Mitral Valve (MV)',
                'valveField' => 'mv_mvls',
                'leaflets' => 'MVLs',
                'descriptions' => [
                    'mv_other_des',
                ],
            ],
            [
                'prefix' => 'av',
                'title' => 'Aortic Valve (AV)',
                'valveField' => 'av_Aortic_valve',
                'leaflets' => 'AVLs',
                'descriptions' => ['av_other_des'],
            ],
            [
                'prefix' => 'tv',
                'title' => 'Tricuspid Valve (TV)',
                'valveField' => 'tv_Tricuspid_valve',
                'leaflets' => 'TVLs',
                'descriptions' => [
                    'tv_type_valve',
                    'tv_motion',
                    'tv_hemodynamic_study',
                    'tv_other_des',
                ],
            ],
            [
                'prefix' => 'pv',
                'title' => 'Pulmonary Valve (PV)',
                'valveField' => 'pv_Pulmonary_valve',
                'leaflets' => 'PVLs',
                'descriptions' => ['pv_other_des'],
            ],
        ];

        foreach ($valveMap as $v) {
            $prefix = $v['prefix'];
            $findings = [];
            $otherDescriptions = [];

            // Apply short form conversion to findings after they're populated
            $applyShortForms = function ($findings) use ($convertShortForms) {
                return array_map(function ($finding) use ($convertShortForms) {
                    return $convertShortForms($finding);
                }, $findings);
            };

            $abnormalityText = '';
            foreach (
                ["{$prefix}_abnormality_description", "{$prefix}_des_abnormal"] as $abField
            ) {
                $t = trim($report[$abField] ?? '');
                if ($t !== '') {
                    $abnormalityText = $t;
                    break;
                }
            }

            $valveField = $v['valveField'] ?? null;
            $isAuto =
                $valveField &&
                shouldDisplay($report[$valveField] ?? null) &&
                strcasecmp(trim((string) $report[$valveField]), 'Auto') === 0;

            $valveLetter = strtoupper(substr($prefix, 0, 1));
            $regurgAbbr = $valveLetter.'R';
            $stenosisAbbr = $valveLetter.'S';

            // Add regurgitation if present
            $regurg = $report["{$prefix}_regurgitant"] ?? null;
            if ($isAuto && (empty($regurg) || $regurg === 'None')) {
                $findings[] = 'No '.$regurgAbbr;
            } elseif (shouldDisplay($regurg, "{$prefix}_regurgitant")) {
                $t = getDisplayText($regurg, "{$prefix}_regurgitant");
                if (! empty($t) && $t !== 'None') {
                    // Check if it's a "no [valve]R" value
                    if (stripos($t, 'no ') !== false && stripos($t, $regurgAbbr) !== false) {
                        $findings[] = 'No '.$regurgAbbr;
                    } else {
                        // Don't add "regurgitation" if value already ends with R (MR, AR, TR, PR)
                        $tLower = strtolower($t);
                        if (substr($tLower, -1) === 'r') {
                            $findings[] = $tLower;
                        } else {
                            $findings[] = $tLower.' regurgitation';
                        }
                    }
                }
            }

            // Add stenosis if present
            $stenosis = $report["{$prefix}_stenosis1"] ?? null;
            if ($isAuto && (empty($stenosis) || $stenosis === 'None')) {
                $findings[] = 'No '.$stenosisAbbr;
            } elseif (shouldDisplay($stenosis, "{$prefix}_stenosis1")) {
                $t = getDisplayText($stenosis, "{$prefix}_stenosis1");
                if (! empty($t) && $t !== 'None') {
                    // Check if it's a "no [valve]S" value
                    if (stripos($t, 'no ') !== false && stripos($t, $stenosisAbbr) !== false) {
                        $findings[] = 'No '.$stenosisAbbr;
                    } else {
                        // Don't add "stenosis" if value already ends with S (MS, AS, TS, PS)
                        $tLower = strtolower($t);
                        if (substr($tLower, -1) === 's') {
                            $findings[] = $tLower;
                        } else {
                            $findings[] = $tLower.' stenosis';
                        }
                    }
                }
            }

            foreach ($v['descriptions'] ?? [] as $descField) {
                if (! empty(trim($report[$descField] ?? ''))) {
                    $otherDescriptions[] = $report[$descField];
                }
            }

            // Apply short form conversion to findings
            $findings = $applyShortForms($findings);

            // Underline abnormal items in findings and otherDescriptions
            $underlinedFindings = array_map('ensureUnderlinedIfAbnormal', $findings);
            $underlinedDescriptions = array_map('ensureUnderlinedIfAbnormal', $otherDescriptions);

            // Combine findings and descriptions
            $content = '';
            $isNormal =
                $valveField &&
                shouldDisplay($report[$valveField] ?? null) &&
                strcasecmp(trim((string) $report[$valveField]), 'Normal') === 0;
            $isAbnormal =
                $valveField &&
                shouldDisplay($report[$valveField] ?? null) &&
                strcasecmp(trim((string) $report[$valveField]), 'Abnormal') === 0;

            if ($isAuto) {
                // Re-order findings so Stenosis is first, then Regurgitation
                $orderedFindings = [];
                $stenosisFound = null;
                $regurgFound = null;
                foreach ($underlinedFindings as $f) {
                    if (stripos($f, $stenosisAbbr) !== false) {
                        $stenosisFound = $f;
                    } elseif (stripos($f, $regurgAbbr) !== false) {
                        $regurgFound = $f;
                    }
                }
                if ($stenosisFound) {
                    $orderedFindings[] = $stenosisFound;
                }
                if ($regurgFound) {
                    $orderedFindings[] = $regurgFound;
                }
                foreach ($underlinedFindings as $f) {
                    if ($f !== $stenosisFound && $f !== $regurgFound) {
                        $orderedFindings[] = $f;
                    }
                }

                $content = 'There is normal '.$v['leaflets'];
                if (! empty($orderedFindings)) {
                    $content .= ', '.implode(', ', $orderedFindings);
                }
                if (! empty($underlinedDescriptions)) {
                    $content .= '. '.implode('. ', array_map('ucfirst', $underlinedDescriptions));
                }
                $content .= '.';
            } elseif ($isNormal && ! empty($v['leaflets'])) {
                $content = 'There are normal '.$v['leaflets'];
                if (! empty($underlinedFindings)) {
                    $content .= ' with '.implode(' and ', $underlinedFindings);
                }
                if (! empty($underlinedDescriptions)) {
                    $descriptionsText = implode(
                        '. ',
                        array_map('ucfirst', $underlinedDescriptions),
                    );
                    $content .=
                        (empty($underlinedFindings) ? ' with ' : '. ').$descriptionsText;
                }
                $content .= '.';
            } elseif ($isAbnormal && $abnormalityText !== '') {
                $abText = ensureUnderlinedIfAbnormal(ucfirst($abnormalityText));
                $content = $abText;
                if (! empty($underlinedFindings)) {
                    $content .= ' with '.implode(' and ', $underlinedFindings);
                }
                $content .= '.';
                if (! empty($underlinedDescriptions)) {
                    $content .=
                        ' '.
                        implode('. ', array_map('ucfirst', $underlinedDescriptions)).
                        '.';
                }
            } else {
                if (! empty($underlinedFindings)) {
                    $content = ucfirst(implode(' and ', $underlinedFindings)).'.';
                }
                if ($abnormalityText !== '') {
                    $abnormalitySentence = ensureUnderlinedIfAbnormal(ucfirst($abnormalityText)).'.';
                    if (! empty($content)) {
                        $content .= ' '.$abnormalitySentence;
                    } else {
                        $content = $abnormalitySentence;
                    }
                }
                if (! empty($underlinedDescriptions)) {
                    $descriptionsText = implode(
                        '. ',
                        array_map('ucfirst', $underlinedDescriptions),
                    );
                    if (! empty($content)) {
                        $content .= ' ';
                    } else {
                        $content = '';
                    }
                    $content .= $descriptionsText.'.';
                }
            }

            // Clean up any double periods and spaces
            $content = preg_replace('/\.{2,}/', '.', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            $content = trim($content);

            // Add TR Gradient for TV valve
            if ($prefix === 'tv' && shouldDisplay($report['tv_TRG'] ?? null)) {
                $trgValue = $report['tv_TRG'];
                $trgText = 'TRG = '.$trgValue.' mmHg';
                if ((float) $trgValue >= 35) {
                    $trgText = '<u>'.$trgText.'</u>';
                }
                if (! empty($content)) {
                    $content = rtrim($content, '.');
                    $content .= ' ('.$trgText.').';
                } else {
                    $content = $trgText.'.';
                }
            }

            // Add Aorta size statement for AV
            if ($prefix === 'av' && ! empty($report['av_aorta']) && strcasecmp($report['av_aorta'], 'None') !== 0) {
                $aortaVal = trim($report['av_aorta']);
                if (strcasecmp($aortaVal, 'Auto') === 0) {
                    // Resolve Auto if not already preprocessed
                    $dilatedSegments = $report['av_aorta_dilated_segments'] ?? null;
                    if ($dilatedSegments === null) {
                        $dilatedSegments = [];
                        $bsa = floatval($report['computed_BSA'] ?? $report['BSA'] ?? 0);
                        if ($bsa <= 0 && ! empty($report['Height']) && ! empty($report['Weight'])) {
                            $h = floatval($report['Height']);
                            $w = floatval($report['Weight']);
                            if ($h > 0 && $w > 0) {
                                $bsa = sqrt(($h * $w) / 3600);
                            }
                        }
                        $gender = isset($report['gender']) ? trim($report['gender']) : '';
                        $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');

                        $annulus = isset($report['av_annulus']) && is_numeric($report['av_annulus']) ? (float) $report['av_annulus'] : (isset($report['AV_annulus']) && is_numeric($report['AV_annulus']) ? (float) $report['AV_annulus'] : 0);
                        $sinusValsalva = isset($report['av_sinus_valsalva']) && is_numeric($report['av_sinus_valsalva']) ? (float) $report['av_sinus_valsalva'] : (isset($report['Sinus_valsalva']) && is_numeric($report['Sinus_valsalva']) ? (float) $report['Sinus_valsalva'] : 0);
                        $stJunction = isset($report['av_st_junction']) && is_numeric($report['av_st_junction']) ? (float) $report['av_st_junction'] : (isset($report['ST_junction']) && is_numeric($report['ST_junction']) ? (float) $report['ST_junction'] : 0);
                        $proxAscAorta = isset($report['av_proximal_ascending_aorta']) && is_numeric($report['av_proximal_ascending_aorta']) ? (float) $report['av_proximal_ascending_aorta'] : (isset($report['ascending_aorta']) && is_numeric($report['ascending_aorta']) ? (float) $report['ascending_aorta'] : 0);

                        if ($annulus > 0) {
                            $absLimit = $isFemale ? 25 : 29;
                            $idxLimit = 14;
                            if ($annulus > $absLimit || ($bsa > 0 && ($annulus / $bsa) > $idxLimit)) {
                                $dilatedSegments[] = 'annulus';
                            }
                        }
                        if ($sinusValsalva > 0) {
                            $absLimit = $isFemale ? 33 : 37;
                            $idxLimit = $isFemale ? 20 : 19;
                            if ($sinusValsalva > $absLimit || ($bsa > 0 && ($sinusValsalva / $bsa) > $idxLimit)) {
                                $dilatedSegments[] = 'sinuses of valsalva';
                            }
                        }
                        if ($stJunction > 0) {
                            $absLimit = $isFemale ? 29 : 32;
                            $idxLimit = 17;
                            if ($stJunction > $absLimit || ($bsa > 0 && ($stJunction / $bsa) > $idxLimit)) {
                                $dilatedSegments[] = 'sinotubular junction';
                            }
                        }
                        if ($proxAscAorta > 0) {
                            $absLimit = $isFemale ? 31 : 34;
                            $idxLimit = $isFemale ? 19 : 17;
                            if ($proxAscAorta > $absLimit || ($bsa > 0 && ($proxAscAorta / $bsa) > $idxLimit)) {
                                $dilatedSegments[] = 'proximal ascending aorta';
                            }
                        }
                        $hasMeasurements = ($annulus > 0 || $sinusValsalva > 0 || $stJunction > 0 || $proxAscAorta > 0);
                    } else {
                        $annulus = floatval($report['av_annulus'] ?? $report['AV_annulus'] ?? 0);
                        $sinusValsalva = floatval($report['av_sinus_valsalva'] ?? $report['Sinus_valsalva'] ?? 0);
                        $stJunction = floatval($report['av_st_junction'] ?? $report['ST_junction'] ?? 0);
                        $proxAscAorta = floatval($report['av_proximal_ascending_aorta'] ?? $report['ascending_aorta'] ?? 0);
                        $hasMeasurements = ($annulus > 0 || $sinusValsalva > 0 || $stJunction > 0 || $proxAscAorta > 0);
                    }

                    if (! empty($dilatedSegments)) {
                        $aortaVal = 'dilated aortic size ('.implode(', ', $dilatedSegments).')';
                        $report['av_aorta_was_auto'] = true;
                    } elseif ($hasMeasurements) {
                        $aortaVal = 'normal aortic size';
                        $report['av_aorta_was_auto'] = true;
                    } else {
                        $aortaVal = '';
                    }
                }

                if (! empty($aortaVal)) {
                    $isManualAorta = ! in_array(strtolower($aortaVal), [
                        'normal aortic size',
                        'dilated aortic size',
                    ]) && strpos(strtolower($aortaVal), 'dilated aortic size (') !== 0;

                    if ($isManualAorta) {
                        $aortaText = $convertShortForms($aortaVal);
                        if (isAbnormalFinding($aortaText)) {
                            $aortaText = '<u>'.$aortaText.'</u>';
                        }
                        $aortaSentence = ucfirst($aortaText).'.';
                    } else {
                        $isDilatedAorta = (stripos($aortaVal, 'dilated') !== false);
                        $aortaTooltip = ! empty($report['av_aorta_was_auto'])
                            ? getAutoTooltipHtml('av_aorta', $report['gender'] ?? '', 'Aorta', $aortaVal, null, $report)
                            : htmlspecialchars($aortaVal);

                        if ($isDilatedAorta) {
                            $aortaSentence = 'There is <u>'.$aortaTooltip.'</u>.';
                        } else {
                            $aortaSentence = 'There is '.$aortaTooltip.'.';
                        }
                    }

                    if (! empty($content)) {
                        $content .= ' '.$aortaSentence;
                    } else {
                        $content = $aortaSentence;
                    }
                }
            }

            // Add to sections if we have content
            if (! empty($content)) {
                if ($isAbnormal && strpos($content, '<u>') === false) {
                    $content = '<u>'.$content.'</u>';
                }
                $secArr = [
                    'title' => $v['title'],
                    'content' => $content,
                    'allow_html' => true,
                ];
                $sections[] = $secArr;
            }
        }

        // 6. Pericardium
        $periFindings = [];

        // Add morphology if present
        if (shouldDisplay($report['pre_Morphology'] ?? null)) {
            $morphology = strtolower($report['pre_Morphology']);
            $periFindings[] = ensureUnderlinedIfAbnormal($morphology);
        }

        // Add pericardial effusion if present
        $effusionText = '';
        if (shouldDisplay($report['pre_Pericardial_effusion'] ?? null)) {
            $t = getDisplayText(
                $report['pre_Pericardial_effusion'],
                'pre_Pericardial_effusion',
            );
            if (! empty($t)) {
                $effusionText = strtolower($t);
                if (shouldDisplay($report['pre_quantity_PE'] ?? null)) {
                    $q = getDisplayText(
                        $report['pre_quantity_PE'],
                        'pre_quantity_PE',
                    );
                    if (! empty($q)) {
                        $effusionText .= ' ('.strtolower($q).')';
                    }
                }
                $effusionText = preg_replace('/\bpe\b/i', 'PE', $effusionText);
                $effusionText = ensureUnderlinedIfAbnormal($effusionText);
            }
        }

        // Add hemodynamic status if present
        $hemodynamicText = '';
        if (shouldDisplay($report['pre_Hemodynamic'] ?? null)) {
            $h = $report['pre_Hemodynamic'];
            if (! empty($h)) {
                $hemodynamicText = ensureUnderlinedIfAbnormal(strtolower($h));
            }
        }

        // Combine findings with appropriate conjunctions
        $contentParts = [];
        $hasMorphology = ! empty($periFindings);
        $hasEffusion = ! empty($effusionText);
        $hasHemodynamic = ! empty($hemodynamicText);

        if ($hasMorphology && $hasEffusion) {
            $contentParts[] = $periFindings[0].' with '.$effusionText;
        } elseif ($hasMorphology) {
            $contentParts[] = $periFindings[0];
        } elseif ($hasEffusion) {
            $contentParts[] = $effusionText;
        }

        if ($hasHemodynamic) {
            $contentParts[] = $hemodynamicText;
        }

        // Add other pericardium descriptions if present
        if (shouldDisplay($report['pre_Pericardium_others'] ?? null)) {
            $others = trim($report['pre_Pericardium_others']);
            if (! empty($others)) {
                $others = preg_replace('/\bpe\b/i', 'PE', $others);
                $others = ensureUnderlinedIfAbnormal($others);
                if (! empty($contentParts)) {
                    $contentParts[] = 'with '.lcfirst($others);
                } else {
                    $contentParts[] = $others;
                }
            }
        }

        if (! empty($contentParts)) {
            $content = 'There is '.implode(', ', $contentParts).'.';
            $sections[] = ['title' => 'Pericardium', 'content' => $content, 'allow_html' => true];
        }

        // 7. IVC & SPAP
        $ivcFindings = [];

        $rawIvc = strtolower(trim($report['cp_ivc1'] ?? 'auto'));
        if ($rawIvc === '' || $rawIvc === 'auto') {
            $ivcSizeVal = isset($report['cp_ivc_size']) && is_numeric($report['cp_ivc_size']) ? (float) $report['cp_ivc_size'] : null;
            if ($ivcSizeVal !== null && $ivcSizeVal > 21) {
                $ivcStatus = 'dilated';
            } else {
                $ivcStatus = 'normal size';
            }
        } else {
            $ivcStatus = $rawIvc;
        }

        if ($ivcStatus !== 'none' && $ivcStatus !== '') {
            $ivcFindings[] = ensureUnderlinedIfAbnormal($ivcStatus);
        }
        if (shouldDisplay($report['cp_ivc_size'] ?? null)) {
            $ivcSizeVal = (float) $report['cp_ivc_size'];
            $ivcSizeText = 'size '.$report['cp_ivc_size'].' mm';
            if ($ivcSizeVal > 21) {
                $ivcSizeText = '<u>'.$ivcSizeText.'</u>';
            }
            $ivcFindings[] = $ivcSizeText;
        }
        if (shouldDisplay($report['cp_Respiratory_variation'] ?? null)) {
            $variation = strtolower($report['cp_Respiratory_variation']);
            if (strpos($variation, 'no respiratory variation') !== false || strpos($variation, '<50%') !== false || strpos($variation, '< 50%') !== false) {
                $ivcFindings[] = '<u>'.$report['cp_Respiratory_variation'].'</u>';
            } elseif (strpos($variation, '>50%') !== false || strpos($variation, '> 50%') !== false) {
                $ivcFindings[] = $report['cp_Respiratory_variation'];
            } elseif (strpos($variation, 'respiratory variation') !== false) {
                $ivcFindings[] = ensureUnderlinedIfAbnormal(strtolower($variation));
            }
        }

        $ivcContent = '';
        if (! empty($ivcFindings)) {
            $first = array_shift($ivcFindings);
            $ivcContent = 'There is '.$first.' IVC';
            if (! empty($ivcFindings)) {
                $ivcContent .= ', with '.implode(', with ', $ivcFindings);
            }
            $ivcContent .= '.';
        }

        $paFindings = [];
        if (shouldDisplay($report['cp_SPAP'] ?? null)) {
            $systolicPap = (float) $report['cp_SPAP'];
            $spapText = 'SPAP='.$report['cp_SPAP'].' mmHg';
            $spapTooltip = getAutoTooltipHtml('cp_SPAP', $report['gender'] ?? '', $report['cp_SPAP_input_used'] ?? 'TRG + RAP', $spapText, $report['cp_SPAP_input_value'] ?? null, $report);
            if ($systolicPap >= 35) {
                $spapTooltip = '<u>'.$spapTooltip.'</u>';
            }
            $paFindings[] = $spapTooltip;
        }

        $paContent = '';
        if (! empty($paFindings)) {
            $paContent = 'There is '.implode(', with ', $paFindings);
            if (shouldDisplay($report['cp_quantity'] ?? null)) {
                $qtyText = $report['cp_quantity'];
                $qtyTooltip = getAutoTooltipHtml('cp_quantity', $report['gender'] ?? '', 'SPAP', $qtyText, $report['cp_SPAP'] ?? null, $report);
                if (isAbnormalFinding($report['cp_quantity'])) {
                    $qtyTooltip = '<u>'.$qtyTooltip.'</u>';
                }
                $paContent .= ' ('.$qtyTooltip.')';
            }
            $paContent .= '.';
        }

        $mergedContent = trim($ivcContent.' '.$paContent);
        if ($mergedContent !== '') {
            $sections[] = [
                'title' => 'Inferior vena cava & SPAP',
                'content' => $mergedContent,
                'allow_html' => true,
            ];
        }

        // 8. Other Findings
        if (! empty(trim($report['finding'] ?? ''))) {
            $findingText = ensureUnderlinedIfAbnormal($report['finding']);
            $sections[] = [
                'title' => 'Additional findings',
                'content' => $findingText,
                'allow_html' => true,
            ];
        }

        if (empty($sections)) {
            return [
                [
                    'title' => 'Summary',
                    'content' => 'No significant abnormalities detected on echocardiogram.',
                ],
            ];
        }

        return $sections;
    }
}
?>
    <div class="report-container">
        <?php if ($study && $shownStatus === StudyStatus::Draft) { ?>
        <div style="border:2px solid #c53030;color:#c53030;font-weight:700;text-align:center;padding:6px;margin-bottom:10px;letter-spacing:2px">DRAFT — NOT SIGNED</div>
        <?php } ?>
        <div class="header" style="position: relative; padding: 15px 0 5px; display: flex; align-items: center; justify-content: space-between;">
            <?php if (! empty($hospital->settings['secondary_logo_path'])) { ?>
            <img src="<?= e(Storage::disk('public')->url($hospital->settings['secondary_logo_path'])) ?>" alt="" class="header-logo">
            <?php } else { ?><span class="header-logo" aria-hidden="true"></span><?php } ?>
            <h1 lang="fa" style="margin: 0; padding: 0 20px; line-height: 1.2; flex-grow: 1; text-align: center;"><?= e($hospital->name) ?> - گزارش اکوکاردیوگرافی</h1>
            <?php if ($hospital->logo_path) { ?>
            <img src="<?= e(Storage::disk('public')->url($hospital->logo_path)) ?>" alt="<?= e($hospital->name) ?>" class="header-logo">
            <?php } else { ?><span class="header-logo" aria-hidden="true"></span><?php } ?>
        </div>

        <div class="section">
            <div class="patient-info-line">
                <span class="patient-info-item"><span class="label">نام بیمار:</span><span class="value"><?= htmlspecialchars(
                    $report['first_name'].' '.$report['last_name'],
                ) ?></span></span>
                <span class="patient-info-item"><span class="label">تاریخ تولد:</span><span class="value"><?= htmlspecialchars(
                    $report['dob'],
                ) ?></span></span>
                <span class="patient-info-item"><span class="label">کد ملی:</span><span class="value"><?= htmlspecialchars(
                    $report['nationalID'] ?? 'N/A',
                ) ?></span></span>
                <span class="patient-info-item"><span class="label">شماره تماس:</span><span class="value"><?= htmlspecialchars(
                    $report['phone_number'] ?? 'N/A',
                ) ?></span></span>
                <span class="patient-info-item"><span class="label">جنسیت:</span><span class="value"><?= htmlspecialchars(
                    getDisplayText($report['gender'], 'gender') ?: 'نامشخص',
                ) ?></span></span>
                <span class="patient-info-item"><span class="label">نوع مطالعه:</span><span class="value"><?= htmlspecialchars(
                    getDisplayText($report['Echo_type'], 'Echo_type')
                ).
                ($report['Echo_type'] == 'Other' && ! empty($report['other_Echo_type'])
                    ? ' - '.htmlspecialchars($report['other_Echo_type'])
                    : '')
?></span></span>
                <span class="patient-info-item"><span class="label">تاریخ اکو:</span><span class="value"><?= htmlspecialchars(
                    $report['echo_date'] ?? 'N/A',
                ) ?></span></span>
            </div>

            <div class="clinical-info-line">
                <?php
                $clinicalInfo = [];
if (shouldDisplay($report['Echo_indication'])) {
    $ind = getDisplayText(
        $report['Echo_indication'],
        'Echo_indication',
    );
    if (
        $report['Echo_indication'] == 'Other' &&
        ! empty($report['other_Echo_indication'])
    ) {
        $ind = $report['other_Echo_indication'];
    }
    $clinicalInfo[] =
        '<span class="info-item"><span class="label">Indication:</span> '.
        htmlspecialchars($ind).
        '</span>';
}
if (shouldDisplay($report['History_diseases'])) {
    $clinicalInfo[] =
        '<span class="info-item"><span class="label">History:</span> '.
        htmlspecialchars($report['History_diseases']).
        '</span>';
}

$vitals = [];
$measures = [];
if (shouldDisplay($report['BP'])) {
    $vitals[] =
        'BP: '.htmlspecialchars($report['BP']).' mmHg';
}
if (shouldDisplay($report['HR'])) {
    $vitals[] =
        'HR: '.htmlspecialchars($report['HR']).' bpm';
}
if (shouldDisplay($report['O2_sat'])) {
    $vitals[] =
        'O<sub>2</sub> Sat: '.
        htmlspecialchars($report['O2_sat']).
        '%';
}
if (shouldDisplay($report['Weight'])) {
    $vitals[] =
        'Wt: '.htmlspecialchars($report['Weight']).'kg';
}
if (shouldDisplay($report['Height'])) {
    $vitals[] =
        'Ht: '.htmlspecialchars($report['Height']).'cm';
}
if (
    shouldDisplay($report['Weight']) &&
    shouldDisplay($report['Height']) &&
    $report['Height'] > 0
) {
    $vitals[] =
        'BMI: '.
        round(
            $report['Weight'] / ($report['Height'] / 100) ** 2,
            1,
        );
}
if (isset($report['computed_BSA']) && $report['computed_BSA'] > 0) {
    $height = isset($report['Height']) ? $report['Height'] : 0;
    $weight = isset($report['Weight']) ? $report['Weight'] : 0;
    $bsa_val = round($report['computed_BSA'], 2).' m²';

    $bsa_tooltip = '<span class="tooltip-container">'.htmlspecialchars($bsa_val);
    $bsa_tooltip .= '<span class="tooltip-table-box no-print" style="width: 250px;">';
    $bsa_tooltip .= '<strong style="display: block; margin-bottom: 5px; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">BSA Calculation (Mosteller)</strong>';
    $bsa_tooltip .= '<span style="display: block; margin-top: 5px; font-size: 10px; line-height: 1.5; font-weight: normal; color: #2d3748;">';
    $bsa_tooltip .= '<strong>Formula:</strong> √((Height × Weight) / 3600)<br>';
    $bsa_tooltip .= '<strong>Height:</strong> '.htmlspecialchars($height).' cm<br>';
    $bsa_tooltip .= '<strong>Weight:</strong> '.htmlspecialchars($weight).' kg<br>';
    $bsa_tooltip .= '<hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 5px 0;">';
    $bsa_tooltip .= '<strong>Calculation:</strong> √(('.$height.' × '.$weight.') / 3600) = '.round($report['computed_BSA'], 4).' m²';
    $bsa_tooltip .= '</span></span></span>';

    $vitals[] = 'BSA: '.$bsa_tooltip;
}
if (shouldDisplay($report['Rhythm'])) {
    $rh = getDisplayText($report['Rhythm'], 'Rhythm');
    if (
        $report['Rhythm'] == 'Other' &&
        ! empty($report['other_Rhythm'])
    ) {
        $rh = $report['other_Rhythm'];
    }
    $clinicalInfo[] =
        '<span class="info-item"><span class="label">Rhythm:</span> '.
        htmlspecialchars($rh).
        '</span>';
}
if ($vitals) {
    $clinicalInfo[] =
        '<span class="info-item"><span class="label">Clinical Data:</span> '.
        implode(' | ', $vitals).
        '</span>';
}

echo ReportHtml::clean(implode(' ', $clinicalInfo));
?>
            </div>
        </div>

        <div class="section">
            <div style="margin-bottom: 20px;">
                <div style="font-weight: bold; margin-bottom: 8px; text-align: center; font-size: 14px;">Chamber Measurements</div>
                <table>
                    <tbody>
                    <?php
    $chamberRows = [];
// LV
if (shouldDisplay($report['lv_LVEDD'])) {
    $chamberRows[] = ['LVEDD', $report['lv_LVEDD'].' mm'];
}
if (shouldDisplay($report['lv_LVESD'])) {
    $chamberRows[] = ['LVESD', $report['lv_LVESD'].' mm'];
}
if (shouldDisplay($report['lv_LVEDV'])) {
    $chamberRows[] = ['LVEDV', $report['lv_LVEDV'].' cc'];
}
if (shouldDisplay($report['lv_LVESV'])) {
    $chamberRows[] = ['LVESV', $report['lv_LVESV'].' cc'];
}
if (shouldDisplay($report['lv_IVS_thickness'])) {
    $chamberRows[] = [
        'IVS',
        $report['lv_IVS_thickness'].' mm',
    ];
}
if (shouldDisplay($report['lv_Posterior'])) {
    $chamberRows[] = [
        'PW',
        $report['lv_Posterior'].' mm',
    ];
}
if (shouldDisplay($report['lv_EF'])) {
    $chamberRows[] = ['EF', $report['lv_EF'].' %'];
}

if (isset($report['computed_LVEDVI']) && $report['computed_LVEDVI'] > 0) {
    $chamberRows[] = ['LVEDVI', round($report['computed_LVEDVI'], 1).' cc/m²'];
}
if (isset($report['computed_LVEDDI']) && $report['computed_LVEDDI'] > 0) {
    $chamberRows[] = ['LVEDDI', round($report['computed_LVEDDI'], 1).' mm/m²'];
}
// RV
if (shouldDisplay($report['rv_RV_basal_diameter'])) {
    $chamberRows[] = [
        'RV Basal',
        $report['rv_RV_basal_diameter'].' mm',
    ];
}
if (shouldDisplay($report['rv_RV_mid_diameter'])) {
    $chamberRows[] = [
        'RV Mid',
        $report['rv_RV_mid_diameter'].' mm',
    ];
}
if (shouldDisplay($report['rv_RV_longitudinal_diameter'])) {
    $chamberRows[] = [
        'RV Longitudinal',
        $report['rv_RV_longitudinal_diameter'].' mm',
    ];
}
if (shouldDisplay($report['rv_TAPSE'])) {
    $chamberRows[] = ['TAPSE', $report['rv_TAPSE'].' mm'];
}
// LA
if (shouldDisplay($report['la_diameter'])) {
    $chamberRows[] = [
        'LA Diam',
        $report['la_diameter'].' mm',
    ];
}
if (shouldDisplay($report['la_area'])) {
    $chamberRows[] = [
        'LA Area',
        $report['la_area'].' cm²',
    ];
}
if (shouldDisplay($report['la_volume'])) {
    $chamberRows[] = [
        'LA Vol',
        $report['la_volume'].' ml',
    ];
}
// RA
if (shouldDisplay($report['ra_area'])) {
    $chamberRows[] = [
        'RA Area',
        $report['ra_area'].' cm²',
    ];
}
if (shouldDisplay($report['ra_volume'])) {
    $chamberRows[] = [
        'RA Vol',
        $report['ra_volume'].' ml',
    ];
}
if (shouldDisplay($report['av_AVA_CE'])) {
    $chamberRows[] = ['AVA', $report['av_AVA_CE'].' cm²'];
}
// Aorta Measurements
if (shouldDisplay($report['av_annulus'] ?? null)) {
    $chamberRows[] = ['Annulus', $report['av_annulus'].' mm'];
} elseif (shouldDisplay($report['AV_annulus'] ?? null)) {
    $chamberRows[] = ['Annulus', $report['AV_annulus'].' mm'];
}
if (shouldDisplay($report['av_sinus_valsalva'] ?? null)) {
    $chamberRows[] = ['Sinus Valsalva', $report['av_sinus_valsalva'].' mm'];
} elseif (shouldDisplay($report['Sinus_valsalva'] ?? null)) {
    $chamberRows[] = ['Sinus Valsalva', $report['Sinus_valsalva'].' mm'];
}
if (shouldDisplay($report['av_st_junction'] ?? null)) {
    $chamberRows[] = ['ST Junction', $report['av_st_junction'].' mm'];
} elseif (shouldDisplay($report['ST_junction'] ?? null)) {
    $chamberRows[] = ['ST Junction', $report['ST_junction'].' mm'];
}
if (shouldDisplay($report['av_proximal_ascending_aorta'] ?? null)) {
    $chamberRows[] = ['Asc Aorta', $report['av_proximal_ascending_aorta'].' mm'];
} elseif (shouldDisplay($report['ascending_aorta'] ?? null)) {
    $chamberRows[] = ['Asc Aorta', $report['ascending_aorta'].' mm'];
}
// IVC
if (shouldDisplay($report['cp_ivc_size'])) {
    $chamberRows[] = [
        'IVC Size',
        $report['cp_ivc_size'].' mm',
    ];
}

// Display Logic (5 Cols)
$colsCount = 5;
$rowsNeeded = ceil(count($chamberRows) / $colsCount);
for ($i = 0; $i < $rowsNeeded; $i++) {
    echo '<tr>';
    for ($c = 0; $c < $colsCount; $c++) {
        $idx = $i + $c * $rowsNeeded;
        if (isset($chamberRows[$idx])) {
            $val = ! empty($chamberRows[$idx][2]) ? $chamberRows[$idx][1] : htmlspecialchars($chamberRows[$idx][1]);
            echo '<td style="width: 20%;">'.
                '<span style="font-weight: 600;">'.htmlspecialchars($chamberRows[$idx][0]).':</span> '.
                ReportHtml::clean((string) $val).
                '</td>';
        } else {
            echo '<td></td>';
        }
    }
    echo '</tr>';
}
?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px;">
                <div style="font-weight: bold; margin-bottom: 8px; text-align: center; font-size: 14px;">Doppler Measurements</div>
                <table>
                    <tbody>
                    <?php
$dopplerRows = [];
// MV
if (shouldDisplay($report['mv_PE_Velocity'])) {
    $dopplerRows[] = [
        'MV E Vel',
        $report['mv_PE_Velocity'].' m/s',
    ];
}
if (shouldDisplay($report['mv_PA_Velocity'])) {
    $dopplerRows[] = [
        'MV A Vel',
        $report['mv_PA_Velocity'].' m/s',
    ];
}
if (shouldDisplay($report['mv_DT'])) {
    $dopplerRows[] = ['MV DT', $report['mv_DT'].' ms'];
}
if (shouldDisplay($report['mv_MV_area'])) {
    $dopplerRows[] = [
        'MVA',
        $report['mv_MV_area'].' cm²',
    ];
}
if (shouldDisplay($report['mv_MPG'])) {
    $dopplerRows[] = [
        'MV MPG',
        $report['mv_MPG'].' mmHg',
    ];
}
// AV
if (shouldDisplay($report['av_peakvelocity'])) {
    $dopplerRows[] = [
        'AV Vel',
        $report['av_peakvelocity'].' m/s',
    ];
}
if (shouldDisplay($report['av_MPG'])) {
    $dopplerRows[] = [
        'AV MPG',
        $report['av_MPG'].' mmHg',
    ];
}
if (shouldDisplay($report['av_DVI'])) {
    $dopplerRows[] = ['AV DVI', $report['av_DVI']];
}
if (shouldDisplay($report['tv_MPG'])) {
    $dopplerRows[] = [
        'TV MPG',
        $report['tv_MPG'].' mmHg',
    ];
}
if (shouldDisplay($report['tv_PPG'])) {
    $dopplerRows[] = [
        'TV PPG',
        $report['tv_PPG'].' mmHg',
    ];
}
if (shouldDisplay($report['tv_VC'])) {
    $dopplerRows[] = ['TV VC', $report['tv_VC'].' cm'];
}
if (shouldDisplay($report['tv_PE_Velocity'])) {
    $dopplerRows[] = [
        'TV E Vel',
        $report['tv_PE_Velocity'].' m/s',
    ];
}
if (shouldDisplay($report['tv_PA_Velocity'])) {
    $dopplerRows[] = [
        'TV A Vel',
        $report['tv_PA_Velocity'].' m/s',
    ];
}
// PV
if (shouldDisplay($report['pv_PV_velocity'])) {
    $dopplerRows[] = [
        'PV Vmax',
        $report['pv_PV_velocity'].' m/s',
    ];
}
if (shouldDisplay($report['pv_MPG'])) {
    $dopplerRows[] = [
        'PV MPG',
        $report['pv_MPG'].' mmHg',
    ];
}
// Tissue Doppler
if (shouldDisplay($report['lv_septale'])) {
    $dopplerRows[] = [
        "Septal e'",
        $report['lv_septale'].' cm/s',
    ];
}
if (shouldDisplay($report['lv_septala'])) {
    $dopplerRows[] = [
        "Septal a'",
        $report['lv_septala'].' cm/s',
    ];
}
if (shouldDisplay($report['lv_septal_Sm'])) {
    $dopplerRows[] = [
        "Septal s'",
        $report['lv_septal_Sm'].' cm/s',
    ];
}
if (shouldDisplay($report['lv_lateral'])) {
    $dopplerRows[] = [
        "Lateral e'",
        $report['lv_lateral'].' cm/s',
    ];
}
// RV
if (shouldDisplay($report['rv_S_velocity'])) {
    $dopplerRows[] = [
        "RV S' vel",
        $report['rv_S_velocity'].' cm/s',
    ];
}
// Hemodynamics
if (shouldDisplay($report['cp_SPAP'])) {
    $spapTooltip = getAutoTooltipHtml('cp_SPAP', $report['gender'] ?? '', $report['cp_SPAP_input_used'] ?? 'TRG + RAP', $report['cp_SPAP'].' mmHg', $report['cp_SPAP_input_value'] ?? null, $report);
    $dopplerRows[] = ['SPAP', $spapTooltip, true];
}

// Display Logic (5 Cols)
$colsCount = 5;
$rowsNeeded = ceil(count($dopplerRows) / $colsCount);
for ($i = 0; $i < $rowsNeeded; $i++) {
    echo '<tr>';
    for ($c = 0; $c < $colsCount; $c++) {
        $idx = $i + $c * $rowsNeeded;
        if (isset($dopplerRows[$idx])) {
            $isHtml = isset($dopplerRows[$idx][2]) && $dopplerRows[$idx][2] === true;
            $valHtml = $isHtml ? $dopplerRows[$idx][1] : htmlspecialchars($dopplerRows[$idx][1]);
            echo '<td style="width: 20%;">'.
                '<span style="font-weight: 600;">'.htmlspecialchars($dopplerRows[$idx][0]).':</span> '.
                ReportHtml::clean((string) $valHtml).
                '</td>';
        } else {
            echo '<td></td>';
        }
    }
    echo '</tr>';
}
?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <div class="report-summary">
                <?php $summarySections = generateSummary($report); ?>
                <div class="section-title">Report Summary</div>
                <div style="line-height: 1.8; direction: ltr; text-align: left;">
                    <?php foreach ($summarySections as $section) { ?>
                        <div style="margin-bottom: 8px;">
                            <strong><?= htmlspecialchars(
                                $section['title'],
                            ) ?>:</strong>
                            <span><?= ! empty($section['allow_html']) ? ReportHtml::clean($section['content']) : htmlspecialchars($section['content']) ?></span>
                        </div>
                    <?php } ?>
                    <?php if (! empty(trim($report['summary'] ?? ''))) { ?>
                        <div style="margin-bottom: 8px;">
                            <strong>Summary:</strong>
                            <span><?= htmlspecialchars(
                                trim($report['summary']),
                            ) ?></span>
                        </div>
                    <?php } ?>
                    <?php if (! empty(trim($report['recommendation'] ?? ''))) { ?>
                        <div style="margin-bottom: 8px;">
                            <strong>Recommendation:</strong>
                            <span><?= htmlspecialchars(
                                trim($report['recommendation']),
                            ) ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php if ($signer) { ?>
        <?php $shownVersion = $version ?? $study->versions->last(); ?>
        <?php if ($shownStatus === StudyStatus::Amended && $shownVersion?->reason) { ?>
        <div class="section" style="font-size:12px;color:#4a5568;padding:0 10px">
            <strong>Amended</strong> <?= e(JalaliDate::format($signedAt)) ?> — <?= e($shownVersion->reason) ?>
        </div>
        <?php } ?>
        <div class="section doctor-signature" style="margin-top: 20px; display: flex; justify-content: flex-end; padding: 0 10px;">
            <div style="text-align: right; min-width: 220px; border-top: 1px dashed #ccc; padding-top: 8px;">
                <strong>پزشک گزارش دهنده</strong>
                <div><?= e($signer->name) ?></div>
                <?php if ($signer->medical_license_no) { ?>
                <div style="font-size:12px">نظام پزشکی: <?= e($signer->medical_license_no) ?></div>
                <?php } ?>
                <?php if ($shownVersion?->signature_path) { ?>
                <img src="<?= e(route('users.signature', [$signer, 'version' => $shownVersion->id])) ?>" alt="Signature" style="max-height:60px;max-width:200px;display:block;margin-left:auto">
                <?php } ?>
                <div style="font-size:12px">تاریخ امضا: <?= e(JalaliDate::format($signedAt)) ?></div>
            </div>
        </div>
        <?php } elseif (! empty($report['doctor'])) { ?>
        <div class="section doctor-signature" style="margin-top: 20px; display: flex; justify-content: flex-end; padding: 0 10px;">
            <div style="text-align: right; min-width: 200px; border-top: 1px dashed #ccc; padding-top: 8px;">
                <strong>پزشک انجام دهنده</strong>
                <div><?= htmlspecialchars($report['doctor']) ?></div>
            </div>
        </div>
        <?php } ?>
    </div>
