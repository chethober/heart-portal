<?php

namespace App\Reports;

/**
 * Cardiac intervention report, ported from heart's reports/templates/intervention.js: a table of
 * technical details, then indication, description, result and complications.
 */
final class InterventionReport extends ProcedureReport
{
    public const PROCEDURE = 'intervention';

    protected const SUMMARY = ['Description of procedure', 'Final result', 'Complications'];

    public function titles(): array
    {
        return ['گزارش مداخله قلبی', 'Cardiac Intervention Report'];
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
                ['Date', 'Time', 'Access', 'Type of procedure', 'Catheter and resort'],
                array_map(fn (string $name) => $this->text($name) ?? '', ['DateOf_date_intervention', 'time_intervention', 'Access', 'Type_Procedure', 'Catheter_resort']),
            ]),
            self::section('Procedure', [
                self::line('Indication for procedure', $this->text('Indication_procedure')),
                self::line('Description of procedure', $this->text('Description_procedure')),
                self::line('Final result', $this->text('Final_result')),
                self::line('Complications', $this->text('Complications')),
                self::line('Others', $this->text('Others')),
                self::line('Entrance time', $this->dateTime('DateOf_entrance_date', 'entrance_time')),
                self::line('Exit time', $this->dateTime('DateOf_exit_date', 'exit_time')),
            ]),
        ];
    }
}
