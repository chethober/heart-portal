<?php

/*
|--------------------------------------------------------------------------
| Peripheral Angiography fields
|--------------------------------------------------------------------------
|
| Generated from the heart system's peripheral.html form (the fields its form
| builder sends to the database). The list drives the peripheral_studies
| migration, validation, mass assignment, the form and the report.
|
| Column names match heart's peripheral table so its data can be imported
| as is; "legacy" names a heart column that was renamed here. Choice codes are
| heart's codes. "blank" lists heart's placeholder codes (missing, select),
| which are stored as null. "show_if" shows a field only while another field
| has the given value.
|
*/

return [
    'label' => 'Peripheral Angiography',
    'date_field' => 'DateOf_date_peripheral',
    'sections' => [
        [
            'title' => 'peripheral',
            'fields' => [
                'DateOf_date_peripheral' => ['type' => 'date', 'label' => 'تاریخ آنژیوگرافی محیطی', 'group' => 'Technical detail'],
                'time_peripheral' => ['type' => 'time', 'label' => 'ساعت آنژیوگرافی محیطی', 'group' => 'Technical detail'],
                'contrast' => ['type' => 'text', 'label' => 'contrast', 'group' => 'Technical detail'],
                'Volume' => ['type' => 'text', 'label' => 'Volume', 'group' => 'Technical detail'],
                'Access' => ['type' => 'text', 'label' => 'Access', 'group' => 'Technical detail'],
                'Catheter' => ['type' => 'multiline', 'label' => 'Catheter', 'group' => 'Technical detail'],
                'vessels' => ['type' => 'multiline', 'label' => 'vessels', 'group' => 'Technical detail'],
                'Indication_procedure' => ['type' => 'multiline', 'label' => 'Indication for procedure'],
                'Description_procedure' => ['type' => 'multiline', 'label' => 'Description of procedure'],
                'Final_result' => ['type' => 'multiline', 'label' => 'Final result'],
                'Recommendation' => ['type' => 'multiline', 'label' => 'Recommendation'],
                'Complication' => ['type' => 'multiline', 'label' => 'Complication'],
                'Others' => ['type' => 'multiline', 'label' => 'Others'],
                'DateOf_entrance_date' => ['type' => 'date', 'label' => 'Entrance Date'],
                'entrance_time' => ['type' => 'time', 'label' => 'Entrance Time'],
                'DateOf_exit_date' => ['type' => 'date', 'label' => 'Exit Date'],
                'exit_time' => ['type' => 'time', 'label' => 'Exit Time'],
            ],
        ],
    ],
];
