<?php

/*
|--------------------------------------------------------------------------
| Right Heart Catheterization fields
|--------------------------------------------------------------------------
|
| Generated from the heart system's rhc.html form (the fields its form
| builder sends to the database). The list drives the rhc_studies
| migration, validation, mass assignment, the form and the report.
|
| Column names match heart's rhc table so its data can be imported
| as is; "legacy" names a heart column that was renamed here. Choice codes are
| heart's codes. "blank" lists heart's placeholder codes (missing, select),
| which are stored as null. "show_if" shows a field only while another field
| has the given value.
|
*/

return [
    'label' => 'Right Heart Catheterization',
    'date_field' => 'DateOf_entrance_date',
    'sections' => [
        [
            'title' => 'Indication',
            'fields' => [
                'indication' => ['type' => 'choice', 'label' => 'Indication', 'widget' => 'dropdown', 'nest' => false, 'options' => [
                    '1' => 'Evaluation before heart transplant',
                    '2' => 'Evaluation for cause of pulmonary hypertension',
                    '3' => 'Evaluation before valve surgery',
                    '99' => 'Other',
                ]],
                'indication_other' => ['type' => 'text', 'label' => 'Other indication', 'show_if' => ['indication', '99']],
            ],
        ],
        [
            'title' => 'Right heart catheterization',
            'fields' => [
                'qID' => ['type' => 'text', 'label' => 'Registry Code', 'retired' => true],
                'Access' => ['type' => 'text', 'label' => 'Access'],
                'catheter' => ['type' => 'text', 'label' => 'catheter'],
                'bsa' => ['type' => 'number', 'label' => 'BSA'],
                'la_o2' => ['type' => 'number', 'label' => 'LA O2 saturation%', 'group' => 'LA'],
                'la_mean' => ['type' => 'number', 'label' => 'LA (mean)', 'group' => 'LA'],
                'pcwp_o2' => ['type' => 'number', 'label' => 'PCWP O2 saturation%', 'group' => 'PCWP'],
                'pcwp_mean' => ['type' => 'number', 'label' => 'PCWP (mean)', 'group' => 'PCWP'],
                'rpa_o2' => ['type' => 'number', 'label' => 'RPA O2 saturation%', 'group' => 'RPA'],
                'rpa_mean' => ['type' => 'number', 'label' => 'RPA (mean)', 'group' => 'RPA'],
                'rpa_systolic' => ['type' => 'number', 'label' => 'RPA systolic', 'group' => 'RPA'],
                'rpa_diastolic' => ['type' => 'number', 'label' => 'RPA diastolic', 'group' => 'RPA'],
                'lpa_o2' => ['type' => 'number', 'label' => 'LPA O2 saturation%', 'group' => 'LPA'],
                'lpa_mean' => ['type' => 'number', 'label' => 'LPA (mean)', 'group' => 'LPA'],
                'lpa_systolic' => ['type' => 'number', 'label' => 'LPA systolic', 'group' => 'LPA'],
                'lpa_diastolic' => ['type' => 'number', 'label' => 'LPA diastolic', 'group' => 'LPA'],
                'mpa_o2' => ['type' => 'number', 'label' => 'MPA O2 saturation%', 'group' => 'MPA'],
                'mpa_mean' => ['type' => 'number', 'label' => 'MPA (mean)', 'group' => 'MPA'],
                'mpa_systolic' => ['type' => 'number', 'label' => 'MPA systolic', 'group' => 'MPA'],
                'mpa_diastolic' => ['type' => 'number', 'label' => 'MPA diastolic', 'group' => 'MPA'],
                'rv_o2' => ['type' => 'number', 'label' => 'RV O2 saturation%', 'group' => 'RV'],
                'rv_mean' => ['type' => 'number', 'label' => 'RV (mean)', 'group' => 'RV'],
                'rv_systolic' => ['type' => 'number', 'label' => 'RV systolic', 'group' => 'RV'],
                'rv_diastolic' => ['type' => 'number', 'label' => 'RV diastolic', 'group' => 'RV'],
                'ra_o2' => ['type' => 'number', 'label' => 'RA O2 saturation%', 'group' => 'RA'],
                'ra_mean' => ['type' => 'number', 'label' => 'RA (mean)', 'group' => 'RA'],
                'svc_o2' => ['type' => 'number', 'label' => 'SVC O2 saturation%', 'group' => 'SVC'],
                'svc_mean' => ['type' => 'number', 'label' => 'SVC (mean)', 'group' => 'SVC'],
                'ivc_o2' => ['type' => 'number', 'label' => 'IVC O2 saturation%', 'group' => 'IVC'],
                'ivc_mean' => ['type' => 'number', 'label' => 'IVC (mean)', 'group' => 'IVC'],
                'aorta_o2' => ['type' => 'number', 'label' => 'AORTA O2 saturation%', 'group' => 'AORTA'],
                'aorta_mean' => ['type' => 'number', 'label' => 'AORTA (mean)', 'group' => 'AORTA'],
                'aorta_systolic' => ['type' => 'number', 'label' => 'AORTA systolic', 'group' => 'AORTA'],
                'aorta_diastolic' => ['type' => 'number', 'label' => 'AORTA diastolic', 'group' => 'AORTA'],
                'hb' => ['type' => 'number', 'label' => 'HB'],
                'cardiac_output' => ['type' => 'number', 'label' => 'CARDIAC OUTPUT'],
                'cardiac_index' => ['type' => 'number', 'label' => 'CARDIAC INDEX'],
                'pvr' => ['type' => 'number', 'label' => 'PVR'],
                'svr' => ['type' => 'number', 'label' => 'SVR'],
                'DateOf_entrance_date' => ['type' => 'date', 'label' => 'Entrance Date'],
                'entrance_time' => ['type' => 'time', 'label' => 'Entrance Time'],
                'DateOf_exit_date' => ['type' => 'date', 'label' => 'Exit Date'],
                'exit_time' => ['type' => 'time', 'label' => 'Exit Time'],
            ],
        ],
        [
            'title' => 'Result',
            'fields' => [
                'right_heart_result' => ['type' => 'multiline', 'label' => 'RESULT'],
            ],
        ],
        [
            'title' => 'Recommendation',
            'fields' => [
                'right_heart_recommendation' => ['type' => 'multiline', 'label' => 'RECOMMENDATION'],
            ],
        ],
    ],
];
