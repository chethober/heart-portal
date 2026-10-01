<?php

/*
|--------------------------------------------------------------------------
| Echo study fields
|--------------------------------------------------------------------------
|
| The single whitelist of clinical columns on echo_studies, with their
| storage type. It drives the migration, validation and mass assignment,
| so a field the form sends but that is not listed here is rejected.
| Names match the legacy echo_reports table so imported data and the
| ported report templates keep working unchanged.
|
| Fields marked "legacy" exist in old data but are no longer on the form.
|
*/

return [
    'fields' => [
        'Echo_indication' => 'text',
        'other_Echo_indication' => 'text',
        'History_diseases' => 'text',
        'BP' => 'text',
        'HR' => 'number',
        'Height' => 'number',
        'Weight' => 'number',
        'Rhythm' => 'text',
        'other_Rhythm' => 'text',
        'O2_sat' => 'number',
        'Echo_type' => 'text',
        'other_Echo_type' => 'text',
        'lv_LVEDV' => 'number',
        'lv_LVESV' => 'number',
        'lv_LVEDD' => 'number',
        'lv_LVESD' => 'number',
        'lv_IVS_thickness' => 'number',
        'lv_Posterior' => 'number',
        'lv_EF' => 'number',
        'lv_clot' => 'text',
        'lv_other_clot' => 'text',
        'lv_size' => 'text',
        'lv_Systolic' => 'text',
        'lv_Diastolic' => 'text',
        'lv_Hypertrophy' => 'text',
        'PPG' => 'number',
        'lv_septale' => 'number',
        'lv_septala' => 'number',
        'lv_septal_Sm' => 'number',
        'lv_lateral' => 'number',
        'lv_with' => 'text',
        'other_des_lv' => 'text',
        'lv_rwma_checked' => 'boolean',
        'lv_global_hypokinesia' => 'boolean',
        'lv_rest' => 'text', // legacy, not in current form
        'lv_Basal_Anterior' => 'text', // legacy, not in current form
        'lv_Basal_anteroseptum' => 'text', // legacy, not in current form
        'lv_Basal_anterolateral' => 'text', // legacy, not in current form
        'lv_Basal_inferoseptum' => 'text', // legacy, not in current form
        'lv_Basal_inferior' => 'text', // legacy, not in current form
        'lv_Basal_inferolateral' => 'text', // legacy, not in current form
        'lv_Mid_Anterior' => 'text', // legacy, not in current form
        'lv_Mid_anteroseptum' => 'text', // legacy, not in current form
        'lv_Mid_anterolateral' => 'text', // legacy, not in current form
        'lv_Mid_inferior' => 'text', // legacy, not in current form
        'lv_Mid_inferoseptum' => 'text', // legacy, not in current form
        'lv_Mid_inferolateral' => 'text', // legacy, not in current form
        'lv_Apical_anterior' => 'text', // legacy, not in current form
        'lv_Apical_septum' => 'text', // legacy, not in current form
        'lv_Apical_lateral' => 'text', // legacy, not in current form
        'lv_Apical_inferior' => 'text', // legacy, not in current form
        'lv_Apical_cap' => 'text', // legacy, not in current form
        'rv_RV_basal_diameter' => 'number',
        'rv_RV_mid_diameter' => 'number',
        'rv_RV_longitudinal_diameter' => 'number',
        'rv_TAPSE' => 'number',
        'rv_S_velocity' => 'number',
        'rv_size' => 'text',
        'rv_Systolic' => 'text',
        'rv_Hypertrophy' => 'text',
        'rv_other_des' => 'text',
        'la_size' => 'text',
        'la_area' => 'number',
        'la_volume' => 'number',
        'la_diameter' => 'number',
        'la_smoky' => 'text',
        'la_clot' => 'text',
        'la_other_des' => 'text',
        'ra_size' => 'text',
        'ra_area' => 'number',
        'ra_volume' => 'number',
        'ra_other_des' => 'text',
        'mv_mvls' => 'text',
        'mv_type_valve' => 'text', // legacy, not in current form
        'mv_motion' => 'text', // legacy, not in current form
        'mv_hemodynamic_study' => 'text', // legacy, not in current form
        'mv_Increased_velocity' => 'text', // legacy, not in current form
        'mv_stenosis1' => 'text',
        'mv_planimetry' => 'number', // legacy, not in current form
        'mv_MPG' => 'number', // legacy, not in current form
        'mv_PHT' => 'number', // legacy, not in current form
        'mv_PE_Velocity' => 'number',
        'mv_regurgitant' => 'text',
        'mv_PA_Velocity' => 'number',
        'mv_DT' => 'number', // legacy, not in current form
        'mv_MV_area' => 'number', // legacy, not in current form
        'mv_des_abnormal' => 'text', // legacy, not in current form
        'mv_other_des' => 'text',
        'mv_abnormality_description' => 'text',
        'av_Aortic_valve' => 'text',
        'av_type_valve' => 'text', // legacy, not in current form
        'av_motion' => 'text', // legacy, not in current form
        'av_hemodynamic_study' => 'text', // legacy, not in current form
        'av_Increased_velocity' => 'text', // legacy, not in current form
        'av_stenosis1' => 'text',
        'av_peakvelocity' => 'number',
        'av_AVA_CE' => 'number', // legacy, not in current form
        'av_DVI' => 'number', // legacy, not in current form
        'av_MPG' => 'number', // legacy, not in current form
        'av_regurgitant' => 'text',
        'av_Velocity' => 'number', // legacy, not in current form
        'av_PPG' => 'number', // legacy, not in current form
        'av_other_des' => 'text',
        'av_abnormality_description' => 'text',
        'av_aorta' => 'text',
        'av_annulus' => 'number',
        'av_sinus_valsalva' => 'number',
        'av_st_junction' => 'number',
        'av_proximal_ascending_aorta' => 'number',
        'pv_Pulmonary_valve' => 'text',
        'pv_type_valve' => 'text', // legacy, not in current form
        'pv_motion' => 'text', // legacy, not in current form
        'pv_hemodynamic_study' => 'text', // legacy, not in current form
        'pv_Increased_velocity' => 'text', // legacy, not in current form
        'pv_stenosis1' => 'text',
        'pv_PV_velocity' => 'number',
        'pv_PV_ACCT' => 'number',
        'pv_MPG' => 'number', // legacy, not in current form
        'pv_regurgitant' => 'text',
        'pv_PPG' => 'number', // legacy, not in current form
        'pv_other_des' => 'text',
        'pv_abnormality_description' => 'text',
        'tv_Tricuspid_valve' => 'text',
        'tv_type_valve' => 'text', // legacy, not in current form
        'tv_motion' => 'text', // legacy, not in current form
        'tv_hemodynamic_study' => 'text', // legacy, not in current form
        'tv_Increased_velocity' => 'text', // legacy, not in current form
        'tv_stenosis1' => 'text',
        'tv_TRG' => 'number',
        'tv_MPG' => 'number', // legacy, not in current form
        'tv_PPG' => 'number', // legacy, not in current form
        'tv_regurgitant' => 'text',
        'tv_other_des' => 'text',
        'tv_VC' => 'number', // legacy, not in current form
        'tv_PISA' => 'number', // legacy, not in current form
        'tv_PE_Velocity' => 'number',
        'tv_PA_Velocity' => 'number',
        'tv_abnormality_description' => 'text',
        'cp_ivc1' => 'text',
        'cp_ivc_size' => 'number',
        'cp_Respiratory_variation' => 'text',
        'cp_quantity' => 'text',
        'cp_SPAP' => 'number',
        'cp_SPAP_des' => 'text',
        'pre_Morphology' => 'text',
        'pre_Pericardial_effusion' => 'text',
        'pre_quantity_PE' => 'text',
        'pre_Hemodynamic' => 'text',
        'pre_Pericardium_others' => 'text',
        'finding' => 'text',
        'summary' => 'text',
        'recommendation' => 'text',
    ],

    // Patient demographics as named by the form; stored on the patients table.
    'patient_fields' => ['first_name', 'last_name', 'nationalID', 'dob', 'phone_number', 'gender'],

    // Timezone used when showing timestamps such as signing time.
    'display_timezone' => env('ECHO_DISPLAY_TIMEZONE', 'Asia/Tehran'),

    // Study fields kept on echo_studies but not clinical measurements.
    'meta_fields' => ['echo_date', 'doctor'],
];
