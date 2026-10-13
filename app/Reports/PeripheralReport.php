<?php

namespace App\Reports;

/**
 * Peripheral angiography report, ported from heart's reports/templates/peripheral.js: a table of
 * technical details, then indication, description, result, recommendation and complications.
 */
final class PeripheralReport extends ProcedureReport
{
    public const PROCEDURE = 'peripheral';

    public function titles(): array
    {
        return ['گزارش آنژیوگرافی محیطی', 'Peripheral Angiography Report'];
    }

    public function signatureBoxes(): array
    {
        return [
            ['title' => 'Assistant physician', 'name' => null],
            ['title' => 'Attending physician', 'name' => null],
        ];
    }

    protected function build(): array
    {
        return [
            self::section('Technical details', [], [
                ['Date', 'Time', 'Contrast', 'Volume', 'Access', 'Catheter', 'Vessels'],
                array_map(fn (string $name) => $this->text($name) ?? '', ['DateOf_date_peripheral', 'time_peripheral', 'contrast', 'Volume', 'Access', 'Catheter', 'vessels']),
            ]),
            self::section('Procedure', [
                self::line('Indication for procedure', $this->text('Indication_procedure')),
                self::line('Description of procedure', $this->text('Description_procedure')),
                self::line('Final result', $this->text('Final_result')),
                self::line('Recommendation', $this->text('Recommendation')),
                self::line('Complication', $this->text('Complication')),
                self::line('Others', $this->text('Others')),
                self::line('Entrance time', $this->dateTime('DateOf_entrance_date', 'entrance_time')),
                self::line('Exit time', $this->dateTime('DateOf_exit_date', 'exit_time')),
            ]),
        ];
    }
}
