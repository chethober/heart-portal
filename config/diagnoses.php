<?php

/*
|--------------------------------------------------------------------------
| Final diagnoses
|--------------------------------------------------------------------------
|
| The final-diagnosis guide from the heart system (icd_code.htm and help/icd_code.htm): the wording
| to use for the final diagnosis on a discharge summary, the criteria for each, and its ICD-10 code.
| Chosen on the discharge summary and on admissions (stored by key), printed with the code.
|
| Heart's guide gives ICD-10 codes only for the acute MI and angina items; the other codes were
| added here (WHO ICD-10) and must be reviewed by a clinician/coder before relying on them for
| reporting. Keys are stored, so a code can be corrected here without touching saved studies.
|
*/

return [
    'Coronary artery disease' => [
        'unstable_angina' => ['label' => 'Unstable angina', 'icd10' => 'I20.0', 'criteria' => 'بر اساس شرح حال، ECG و تستهای ایسکمیک قلبی و تروپونین نرمال'],
        'stemi_anterior' => ['label' => 'Acute STEMI of anterior wall', 'icd10' => 'I21.0', 'criteria' => 'بر اساس شرح حال، ECG و تروپونین بالا'],
        'stemi_inferior' => ['label' => 'Acute STEMI of inferior wall', 'icd10' => 'I21.1', 'criteria' => 'بر اساس شرح حال، ECG و تروپونین بالا'],
        'stemi_other' => ['label' => 'Acute STEMI of other sites', 'icd10' => 'I21.2', 'criteria' => 'بر اساس شرح حال، ECG و تروپونین بالا؛ محل آن ذکر شود (مثلاً Acute STEMI of lateral wall)'],
        'nstemi' => ['label' => 'Acute NSTEMI', 'icd10' => 'I21.4', 'criteria' => 'بر اساس شرح حال، ECG و تروپونین بالا'],
        'stable_angina' => ['label' => 'Stable angina pectoris', 'icd10' => 'I20.8', 'criteria' => 'شرح حال آنژین فعالیتی، وجود ایسکمی در تستهای مربوطه و ECG'],
        'chronic_ihd' => ['label' => 'Chronic ischemic heart disease', 'icd10' => 'I25.9', 'criteria' => 'مصداق آن در پرانتز ذکر شود: Atherosclerotic coronary disease، Old MI، Mild CAD، Coronary aneurysm، staged PCI on'],
        'vasospastic_angina' => ['label' => 'Angina pectoris with documented spasm', 'icd10' => 'I20.1', 'criteria' => 'بر اساس شرح حال، ECG و بررسیهای پاراکلینیک'],
        'syndrome_x' => ['label' => 'X-syndrome', 'icd10' => 'I20.8', 'criteria' => 'درد سینه، وجود ایسکمی در تستهای مربوطه و نرمال بودن عروق کرونری اپی کاردیال'],
        'slow_flow' => ['label' => 'Slow flow coronary syndrome', 'icd10' => 'I25.8', 'criteria' => 'بر اساس نتایج انژیوگرافی'],
        'cad_ruled_out' => ['label' => 'Admitted for suspected CAD, it was ruled out', 'icd10' => 'Z03.5', 'criteria' => 'بیمارانی که صرفا جهت انژیوگرافی بستری می شوند، آنژین آتیپیکال و انژیوگرافی نرمال دارند'],
        'preop_cad_evaluation' => ['label' => 'Evaluation for CAD before cardiac and non-cardiac surgery', 'icd10' => 'Z01.8', 'criteria' => 'بررسی عروق کرونر قبل از جراحی قلبی و غیرقلبی در بیمار پرخطر بدون شکایت ایسکمی'],
    ],
    'Heart failure' => [
        'ahf_adchf' => ['label' => 'Acute heart failure (HFrEF-ADCHF)', 'icd10' => 'I50.0', 'criteria' => 'Chronic HF with acute decompensation'],
        'ahf_de_novo' => ['label' => 'Acute heart failure (HFrEF-de novo)', 'icd10' => 'I50.1', 'criteria' => 'New onset HF'],
        'ahf_hfpef' => ['label' => 'Acute heart failure (HFpEF)', 'icd10' => 'I50.9', 'criteria' => 'Criteria for HFpEF'],
        'ahf_pulmonary_edema' => ['label' => 'Acute heart failure with pulmonary edema', 'icd10' => 'I50.1', 'criteria' => 'Presentation with pulmonary edema'],
        'ahf_cardiogenic_shock' => ['label' => 'Acute heart failure with cardiogenic shock', 'icd10' => 'R57.0', 'criteria' => 'Presentation with cardiogenic shock'],
        'ahf_hypertension' => ['label' => 'Acute heart failure with hypertension', 'icd10' => 'I11.0', 'criteria' => 'Presentation with severe HTN'],
        'acute_right_hf' => ['label' => 'Acute right heart failure', 'icd10' => 'I50.9', 'criteria' => 'Dominant RV failure'],
    ],
    'Myocardial and pericardial disease' => [
        'acute_pericarditis' => ['label' => 'Acute nonspecific idiopathic pericarditis', 'icd10' => 'I30.0', 'criteria' => 'Criteria for pericarditis without definite cause'],
        'constrictive_pericarditis' => ['label' => 'Chronic constrictive pericarditis', 'icd10' => 'I31.1', 'criteria' => 'Based on clinical and paraclinical evaluation'],
        'tamponade' => ['label' => 'Cardiac tamponade', 'icd10' => 'I31.9', 'criteria' => 'Based on clinical and echocardiography'],
        'pericardial_disease' => ['label' => 'Disease of pericardium', 'icd10' => 'I31.9', 'criteria' => ''],
        'acute_myocarditis' => ['label' => 'Acute myocarditis', 'icd10' => 'I40.9', 'criteria' => 'Based on clinical and paraclinical evaluation'],
        'dilated_cm' => ['label' => 'Dilated cardiomyopathy', 'icd10' => 'I42.0', 'criteria' => ''],
        'obstructive_hcm' => ['label' => 'Obstructive hypertrophic cardiomyopathy', 'icd10' => 'I42.1', 'criteria' => 'Based on echocardiography'],
        'hcm' => ['label' => 'Hypertrophic cardiomyopathy', 'icd10' => 'I42.2', 'criteria' => 'Based on echocardiography'],
        'restrictive_cm' => ['label' => 'Restrictive cardiomyopathy', 'icd10' => 'I42.5', 'criteria' => ''],
        'drug_cm' => ['label' => 'Cardiomyopathy due to drugs and other external agents', 'icd10' => 'I42.7', 'criteria' => 'Based on history and paraclinical evaluation'],
        'infective_endocarditis' => ['label' => 'Acute and subacute infective endocarditis', 'icd10' => 'I33.0', 'criteria' => 'Based on IE criteria'],
    ],
    'Valvular disease' => [
        'mitral_stenosis' => ['label' => 'Mitral stenosis', 'icd10' => 'I05.0', 'criteria' => 'Based on clinical and echocardiography'],
        'rheumatic_mr' => ['label' => 'Rheumatic mitral insufficiency', 'icd10' => 'I05.1', 'criteria' => 'Based on clinical and echocardiography'],
        'mitral_insufficiency' => ['label' => 'Mitral (valve) insufficiency', 'icd10' => 'I34.0', 'criteria' => 'Based on clinical and echocardiography'],
        'ms_with_mr' => ['label' => 'Mitral stenosis with insufficiency', 'icd10' => 'I05.2', 'criteria' => 'Based on clinical and echocardiography'],
        'rheumatic_ar' => ['label' => 'Rheumatic aortic insufficiency', 'icd10' => 'I06.1', 'criteria' => 'Based on clinical and echocardiography'],
        'rheumatic_as_ar' => ['label' => 'Rheumatic aortic stenosis with insufficiency', 'icd10' => 'I06.2', 'criteria' => 'Based on clinical and echocardiography'],
        'aortic_stenosis' => ['label' => 'Aortic (valve) stenosis', 'icd10' => 'I35.0', 'criteria' => 'Based on clinical and echocardiography'],
        'aortic_insufficiency' => ['label' => 'Aortic (valve) insufficiency', 'icd10' => 'I35.1', 'criteria' => 'Based on clinical and echocardiography'],
        'as_with_ar' => ['label' => 'Aortic (valve) stenosis with insufficiency', 'icd10' => 'I35.2', 'criteria' => 'Based on clinical and echocardiography'],
        'tricuspid_insufficiency' => ['label' => 'Tricuspid insufficiency', 'icd10' => 'I07.1', 'criteria' => 'Based on clinical and echocardiography'],
        'tricuspid_stenosis' => ['label' => 'Tricuspid stenosis', 'icd10' => 'I07.0', 'criteria' => 'Based on clinical and echocardiography'],
        'ts_with_tr' => ['label' => 'Tricuspid stenosis with insufficiency', 'icd10' => 'I07.2', 'criteria' => 'Based on clinical and echocardiography'],
        'nonrheumatic_tr' => ['label' => 'Nonrheumatic tricuspid (valve) insufficiency', 'icd10' => 'I36.1', 'criteria' => 'Based on clinical and echocardiography'],
        'mitral_and_tricuspid' => ['label' => 'Disorders of both mitral and tricuspid valves', 'icd10' => 'I08.1', 'criteria' => 'Based on clinical and echocardiography'],
        'mitral_aortic_tricuspid' => ['label' => 'Combined disorders of mitral, aortic and tricuspid valves', 'icd10' => 'I08.3', 'criteria' => 'Based on clinical and echocardiography'],
        'mechanical_valve_dysfunction' => ['label' => 'Mechanical valve dysfunction', 'icd10' => 'T82.0', 'criteria' => 'Based on clinical and echocardiography'],
    ],
    'Arrhythmia' => [
        'av_block_second' => ['label' => 'Atrioventricular block, second degree', 'icd10' => 'I44.1', 'criteria' => 'Based on ECG'],
        'av_block_complete' => ['label' => 'Atrioventricular block, complete', 'icd10' => 'I44.2', 'criteria' => 'Based on ECG'],
        'trifascicular_block' => ['label' => 'Trifascicular block', 'icd10' => 'I45.3', 'criteria' => 'Based on ECG'],
        'preexcitation' => ['label' => 'Pre-excitation syndrome', 'icd10' => 'I45.6', 'criteria' => 'Based on ECG'],
        'arrest_resuscitated' => ['label' => 'Cardiac arrest with successful resuscitation', 'icd10' => 'I46.0', 'criteria' => 'Based on ECG and history'],
        'sudden_cardiac_death' => ['label' => 'Sudden cardiac death, so described', 'icd10' => 'I46.1', 'criteria' => 'Based on ECG and history'],
        'cardiac_arrest' => ['label' => 'Cardiac arrest, unspecified', 'icd10' => 'I46.9', 'criteria' => 'Based on ECG and history'],
        'svt' => ['label' => 'Supraventricular tachycardia', 'icd10' => 'I47.1', 'criteria' => 'Based on ECG'],
        'vt' => ['label' => 'Ventricular tachycardia', 'icd10' => 'I47.2', 'criteria' => 'Based on ECG'],
        'af_paroxysmal' => ['label' => 'Paroxysmal atrial fibrillation', 'icd10' => 'I48.0', 'criteria' => 'Based on ECG'],
        'af_persistent' => ['label' => 'Persistent atrial fibrillation', 'icd10' => 'I48.1', 'criteria' => 'Based on ECG'],
        'af_chronic' => ['label' => 'Chronic atrial fibrillation', 'icd10' => 'I48.2', 'criteria' => 'Based on ECG'],
        'atrial_tachycardia' => ['label' => 'Atrial tachycardia', 'icd10' => 'I47.1', 'criteria' => 'Based on ECG'],
        'flutter_typical' => ['label' => 'Typical atrial flutter', 'icd10' => 'I48.3', 'criteria' => 'Based on ECG'],
        'flutter_atypical' => ['label' => 'Atypical atrial flutter', 'icd10' => 'I48.4', 'criteria' => 'Based on ECG'],
        'vf' => ['label' => 'Ventricular fibrillation and flutter', 'icd10' => 'I49.0', 'criteria' => 'Based on ECG'],
        'pvc' => ['label' => 'Ventricular premature depolarization', 'icd10' => 'I49.3', 'criteria' => 'Based on ECG'],
        'sick_sinus' => ['label' => 'Sick sinus syndrome', 'icd10' => 'I49.5', 'criteria' => 'Based on ECG'],
    ],
];
