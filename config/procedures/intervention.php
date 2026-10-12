<?php

/*
|--------------------------------------------------------------------------
| Cardiac Intervention fields
|--------------------------------------------------------------------------
|
| Generated from the heart system's intervention.html form (the fields its form
| builder sends to the database). The list drives the intervention_studies
| migration, validation, mass assignment, the form and the report.
|
| Column names match heart's intervention table so its data can be imported
| as is; "legacy" names a heart column that was renamed here. Choice codes are
| heart's codes. "blank" lists heart's placeholder codes (missing, select),
| which are stored as null. "show_if" shows a field only while another field
| has the given value.
|
*/

return [
    'label' => 'Cardiac Intervention',
    'date_field' => 'DateOf_date_intervention',
    'sections' => [
        [
            'title' => 'intervention',
            'fields' => [
                'DateOf_date_intervention' => ['type' => 'date', 'label' => 'تاریخ گزارش مداخله قلبی', 'group' => 'Technical detail'],
                'time_intervention' => ['type' => 'time', 'label' => 'ساعت گزارش مداخله قلبی', 'group' => 'Technical detail'],
                'Access' => ['type' => 'text', 'label' => 'Access', 'group' => 'Technical detail'],
                'Type_Procedure' => ['type' => 'text', 'label' => 'Type of Procedure', 'group' => 'Technical detail'],
                'Catheter_resort' => ['type' => 'multiline', 'label' => 'Catheter and resort', 'group' => 'Technical detail'],
                'Indication_procedure' => ['type' => 'multiline', 'label' => 'Indication for procedure'],
                'Description_procedure' => ['type' => 'multiline', 'label' => 'Description of procedure'],
                'Final_result' => ['type' => 'multiline', 'label' => 'Final result'],
                'Complications' => ['type' => 'multiline', 'label' => 'Complications'],
                'Others' => ['type' => 'multiline', 'label' => 'Others'],
                'DateOf_entrance_date' => ['type' => 'date', 'label' => 'Entrance Date'],
                'entrance_time' => ['type' => 'time', 'label' => 'Entrance Time'],
                'DateOf_exit_date' => ['type' => 'date', 'label' => 'Exit Date'],
                'exit_time' => ['type' => 'time', 'label' => 'Exit Time'],
            ],
        ],
    ],
];
