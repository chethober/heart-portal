<?php

/*
|--------------------------------------------------------------------------
| Importing from the heart system and the hospital information system
|--------------------------------------------------------------------------
|
| Used by `php artisan heart:import` (heart's MySQL database, connection
| "heart") and `php artisan his:import-admissions` (the HIS SQL Server view
| heart read admissions from, connection "his"). See README.md.
|
*/

$systolic = [
    '1' => 'With normal systolic function', '2' => 'With mild systolic dysfunction',
    '3' => 'With mild to moderate systolic dysfunction', '4' => 'With moderate systolic dysfunction',
    '5' => 'With moderate to severe systolic dysfunction', '6' => 'With severe systolic dysfunction',
];

// Heart's valve sections share one layout; $v is the valve (MV), $s / $r its stenosis / regurgitation abbreviations.
$valve = fn (string $p, string $field, string $v, string $s, string $r, string $regurgitant, ?array $stenosis = null) => [
    $field => [$v, "{$p}_other_des", ['1' => 'Normal', '2' => 'Abnormal', '3' => [null, 'Mechanical']]],
    "{$p}_type_valve" => ["{$v} prosthesis", "{$p}_other_des", [
        '1' => "Bileaflet {$v} prosthetic", '2' => "Monoleaflet {$v} prosthetic", '3' => "Biologic {$v} prosthetic",
    ]],
    "{$p}_motion" => ["{$v} leaflet motion", "{$p}_other_des", ['1' => 'Normal leaflet motion', '2' => 'Reduced leaflet motion', '3' => 'Fixed leaflet']],
    "{$p}_hemodynamic_study" => ["{$v} hemodynamic study", "{$p}_other_des", ['0' => 'No', '1' => 'Yes']],
    "{$p}_Increased_velocity" => ["{$v} increased velocity", "{$p}_other_des", ['0' => 'No', '1' => 'Yes']],
    "{$p}_stenosis1" => ["{$v} stenosis", "{$p}_other_des", $stenosis ?? [
        '1' => "No {$s}", '2' => "Mild {$s}", '3' => "Moderate {$s}", '4' => "Severe {$s}",
    ]],
    $regurgitant => ["{$v} regurgitation", "{$p}_other_des", [
        '1' => "No {$r}", '2' => "Mild {$r}", '3' => "Mild to Moderate {$r}", '4' => "Moderate {$r}",
        '5' => "Moderate to Severe {$r}", '6' => "Severe {$r}",
    ]],
];

return [

    // Heart tables in import order, with their primary keys. Procedure keys
    // match App\Support\Procedures::MODELS. heart's tbusers is never imported.
    'tables' => [
        'patients' => ['table' => 'patient', 'pk' => 'patientId'],
        'admissions' => ['table' => 'admit', 'pk' => 'admitId'],
        'echo' => ['table' => 'echo', 'pk' => 'idEcho'],
        'angiography' => ['table' => 'angiography', 'pk' => 'idAngio'],
        'angioplasty' => ['table' => 'angioplasty', 'pk' => 'idAngioplasty'],
        'rhc' => ['table' => 'rhc', 'pk' => 'idrhc'],
        'peripheral' => ['table' => 'peripheral', 'pk' => 'idperipheral'],
        'intervention' => ['table' => 'intervention', 'pk' => 'ideintervention'],
        'discharge' => ['table' => 'heart', 'pk' => 'idHeart'],
    ],

    'chunk' => 500,

    /*
    | Heart's echo form (echo.html, table `echo`) predates the echo form that
    | config/echo.php describes. Columns with the same name are copied; the
    | maps below cover the rest. Heart stores choices as codes, Echo Portal's
    | echo form stores the option text, so every choice column needs a code
    | map. Codes not listed (heart's -1 "none" and 9 "Missing" placeholders,
    | or unknown codes) import as null.
    |
    | A code mapped to [value, text] stores value (which may be null) and keeps
    | the detail the new form has no option for: with value "Other", text
    | fills the field's "other" column (Echo_type "Other" + other_Echo_type);
    | otherwise "label: text" is appended to the field's notes column, so it
    | stays on the report.
    */
    'echo' => [
        // heart column => echo_studies column
        'renames' => [
            'DateOf_date_echo' => 'echo_date',
            'lv_PPG' => 'PPG',
            'lv_other_des' => 'other_des_lv',
            'rv_rv_size' => 'rv_size',
            'a_size' => 'av_aorta',
            'a_AV_annulus' => 'av_annulus',
            'a_Sinus_valsalva' => 'av_sinus_valsalva',
            'a_ST_junction' => 'av_st_junction',
            'a_ascending_aorta' => 'av_proximal_ascending_aorta',
            'av_des_abnormal' => 'av_abnormality_description',
            'pv_Velocity' => 'pv_PV_velocity',
            'pv_ACCT' => 'pv_PV_ACCT',
            'pv_des_abnormal' => 'pv_abnormality_description',
            'tv_regurgitant1' => 'tv_regurgitant',
            'tv_des_abnormal' => 'tv_abnormality_description',
        ],

        // Heart columns with no echo_studies column: "label: value" is appended to the notes column.
        'notes' => [
            'lv_GLS' => ['other_des_lv', 'GLS (%)'],
            'lv_LVOT' => ['other_des_lv', 'LVOT VTI (cm)'],
            'lv_LVOT_diameter' => ['other_des_lv', 'LVOT diameter (mm)'],
            'lv_max_thickness' => ['other_des_lv', 'LV max thickness (mm)'],
            'lv_others' => ['other_des_lv', 'LV hypertrophy'],
            'rv_RVOT' => ['rv_other_des', 'RVOT VTI (cm)'],
            'rv_RVOT_diameter' => ['rv_other_des', 'RVOT diameter (mm)'],
            'rv_max_thickness' => ['rv_other_des', 'RV max thickness (mm)'],
            'rv_other_hyper' => ['rv_other_des', 'RV hypertrophy'],
            'la_other_clot' => ['la_other_des', 'LA clot'],
            'mv_other' => ['mv_other_des', 'MV'],
            'av_PHT' => ['av_other_des', 'AI PHT (ms)'],
            'av_planimetry' => ['av_other_des', 'AVA by direct planimetry (cm2)'],
            'av_ACCT' => ['av_other_des', 'AV ACCT (msec)'],
            'av_other' => ['av_other_des', 'AV'],
            'a_des_aorta_abnormal' => ['av_other_des', 'Aorta'],
            'pv_other' => ['pv_other_des', 'PV'],
            'pa_size' => ['pv_other_des', 'Pulmonary artery size', ['1' => 'Normal', '2' => 'Abnormal']],
            'pa_MPA' => ['pv_other_des', 'MPA size (cm)'],
            'pa_des_pulmonary_abnormal' => ['pv_other_des', 'Pulmonary artery'],
            'tv_other' => ['tv_other_des', 'TV'],
            'tv_PHT' => ['tv_other_des', 'TV PHT (msec)'],
        ],

        // heart column => [label, other or notes column, [code => value | [value|null, text]]]
        'choices' => [
            'Echo_indication' => ['Echo indication', 'other_Echo_indication', [
                '1' => 'Chest pain', '2' => 'Dyspnea', '3' => 'Palpitation',
                '4' => ['Other', 'Evaluation cardiac murmur'], '99' => 'Other',
            ]],
            'Rhythm' => ['Rhythm', 'other_Rhythm', ['1' => 'Sinus', '2' => 'AF', '99' => 'Other']],
            'Echo_type' => ['Echo type', 'other_Echo_type', [
                '1' => 'Transthoracic echocardiography (TTE)', '2' => 'Transesophageal echocardiography (TEE)',
                '3' => ['Other', 'Tissue Doppler imaging (TDI)'], '4' => ['Other', 'Congenital (TTE) report'],
                '5' => ['Other', 'Congenital (TEE) report'], '6' => 'Contrast echocardiography', '99' => 'Other',
            ]],
            'lv_size' => ['LV size', 'other_des_lv', [
                '1' => 'Normal LV size', '2' => 'Mild LV enlargement', '3' => 'Mild to moderate LV enlargement',
                '4' => 'Moderate LV enlargement', '5' => 'Moderate to severe LV enlargement', '6' => 'Severe LV enlargement',
            ]],
            'lv_Systolic' => ['LV systolic function', 'other_des_lv', $systolic],
            'lv_Diastolic' => ['LV diastolic function', 'other_des_lv', [
                '1' => 'Normal diastolic function', '2' => 'Grade I diastolic dysfunction',
                '3' => 'Grade II diastolic dysfunction', '4' => 'Grade III diastolic dysfunction',
            ]],
            'lv_Hypertrophy' => ['LV hypertrophy', 'other_des_lv', [
                '1' => 'No LVH', '2' => 'Mild concentric LVH', '3' => 'Moderate concentric LVH', '4' => 'Severe concentric LVH',
                '5' => 'Asymmetric LVH with SAM and LVOT obstruction', '6' => 'Asymmetric LVH without LVOT obstruction',
                '99' => null, // described in heart's lv_others (see notes)
            ]],
            'lv_rest' => ['LV at rest', 'other_des_lv', ['0' => 'No', '1' => 'Yes']],
            'lv_with' => ['LV wall', 'other_des_lv', ['1' => '1', '2' => '2']],
            'lv_clot' => ['LV clot', 'lv_other_clot', [
                '1' => 'No LV clot', '2' => 'Small mobile LV clot', '3' => 'Large mobile LV clot',
                '4' => 'Small organized LV clot', '5' => 'Large organized LV clot', '99' => null, // described in lv_other_clot
            ]],
            ...array_fill_keys([
                'lv_Basal_Anterior', 'lv_Basal_anteroseptum', 'lv_Basal_anterolateral', 'lv_Basal_inferior',
                'lv_Basal_inferoseptum', 'lv_Basal_inferolateral', 'lv_Mid_Anterior', 'lv_Mid_anteroseptum',
                'lv_Mid_anterolateral', 'lv_Mid_inferior', 'lv_Mid_inferoseptum', 'lv_Mid_inferolateral',
                'lv_Apical_anterior', 'lv_Apical_lateral', 'lv_Apical_septum', 'lv_Apical_inferior', 'lv_Apical_cap',
            ], ['LV segment', 'other_des_lv', [
                '1' => 'Normal', '2' => 'Mild hypokinesia', '3' => 'Severe hypokinesia',
                '4' => 'Akinesia', '5' => 'Dyskinesia', '6' => 'Aneurysm',
            ]]),
            'rv_rv_size' => ['RV size', 'rv_other_des', [
                '1' => 'Normal RV size', '2' => 'Mild RV enlargement', '3' => 'Mild to moderate RV enlargement',
                '4' => 'Moderate RV enlargement', '5' => 'Moderate to severe RV enlargement', '6' => 'Severe RV enlargement',
            ]],
            'rv_Systolic' => ['RV systolic function', 'rv_other_des', $systolic],
            'rv_Hypertrophy' => ['RV hypertrophy', 'rv_other_des', [
                '1' => 'No RVH', '2' => 'Mild RVH', '3' => 'Moderate RVH', '4' => 'Severe RVH', '99' => 'Other',
            ]],
            'la_size' => ['LA size', 'la_other_des', [
                '1' => 'Normal LA size', '2' => 'Mild LA enlargement', '3' => 'Moderate LA enlargement', '4' => 'Severe LA enlargement',
            ]],
            // The new form only records smoke as no (0) / yes (1); heart's grade goes to the notes.
            'la_smoky' => ['LA smoke', 'la_other_des', [
                '1' => '0', '2' => ['1', 'mild smoky pattern'], '3' => ['1', 'moderate smoky pattern'], '4' => ['1', 'severe smoky pattern'],
            ]],
            // The new form records LA clot as no (0) / present (1); where it is goes to the notes.
            'la_clot' => ['LA clot', 'la_other_des', [
                '1' => '0', '2' => ['1', 'LA clot'], '3' => ['1', 'LAA clot'], '4' => ['1', 'LA and LAA clot'], '99' => ['1', 'other clot'],
            ]],
            'ra_size' => ['RA size', 'ra_other_des', [
                '1' => 'Normal RA size', '2' => 'Mild RA enlargement', '3' => 'Moderate RA enlargement', '4' => 'Severe RA enlargement',
            ]],
            ...$valve('mv', 'mv_mvls', 'MV', 'MS', 'MR', 'mv_regurgitant', ['1' => 'No MS', '2' => [null, 'progressive stenosis'], '3' => 'Severe MS']),
            ...$valve('av', 'av_Aortic_valve', 'AV', 'AS', 'AR', 'av_regurgitant'),
            ...$valve('pv', 'pv_Pulmonary_valve', 'PV', 'PS', 'PR', 'pv_regurgitant'),
            ...$valve('tv', 'tv_Tricuspid_valve', 'TV', 'TS', 'TR', 'tv_regurgitant1'),
            'a_size' => ['Aorta size', 'av_other_des', ['1' => 'Normal aortic size', '2' => 'Dilated aortic size']],
            'cp_ivc1' => ['IVC', 'finding', ['1' => 'normal size', '2' => 'Dilated']],
            'cp_Respiratory_variation' => ['IVC respiratory variation', 'finding', [
                '1' => 'no respiratory variation', '2' => '<50% respiratory variation', '3' => '>50% respiratory variation',
            ]],
            'cp_quantity' => ['Pulmonary pressure', 'cp_SPAP_des', [
                '1' => 'Normal PAP', '2' => 'Top normal PAP', '3' => 'Mild PH', '4' => 'Moderate PH', '5' => 'Severe PH',
            ]],
            'pre_Morphology' => ['Pericardium', 'pre_Pericardium_others', [
                '1' => 'Normal pericardium', '2' => 'Thickened pericardium', '3' => 'Calcified pericardium', '4' => 'Thick and calcified pericardium',
            ]],
            'pre_Pericardial_effusion' => ['Pericardial effusion', 'pre_Pericardium_others', [
                '1' => 'No PE', '2' => 'Small PE', '3' => 'Moderate PE', '4' => 'Large PE',
            ]],
            'pre_Hemodynamic' => ['Pericardial effusion hemodynamics', 'pre_Pericardium_others', [
                '1' => 'with hemodynamic effect', '2' => 'without hemodynamic effect',
            ]],
        ],
    ],

    // The HIS admissions view heart's sync read (include/connect.php). Credentials
    // come from HIS_DB_* in .env (see config/database.php, connection "his").
    'his' => [
        'view' => env('HIS_DB_VIEW', 'dbo.SSU-Registery-ADM'),
        // Run his:import-admissions from the scheduler for this hospital (slug); empty = off.
        'schedule_hospital' => env('HIS_IMPORT_HOSPITAL'),
        'schedule_cron' => env('HIS_IMPORT_CRON', '*/30 * * * *'),
    ],
];
