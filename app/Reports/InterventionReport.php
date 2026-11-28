<?php

namespace App\Reports;

/**
 * Cardiac intervention report, ported from heart's reports/templates/intervention.js: a table of
 * technical details, then indication, description, result and complications.
 */
final class InterventionReport extends ProcedureReport
{
    public const PROCEDURE = 'intervention';

    protected const SUMMARY = ['Final result'];

    public function title(): string
    {
        return __('Cardiac Intervention Report');
    }

    protected function build(): array
    {
        return [
            self::section(__('Technical details'), [], [
                [__('Date'), __('Time'), __('Access'), __('Type of procedure'), 'Catheter and resort'],
                array_map(fn (string $name) => $this->text($name) ?? '', ['DateOf_date_intervention', 'time_intervention', 'Access', 'Type_Procedure', 'Catheter_resort']),
            ]),
            self::section(__('Procedure'), [
                self::line(__('Indication for procedure'), $this->text('Indication_procedure')),
                self::line(__('Description of procedure'), $this->text('Description_procedure')),
                self::line(__('Final result'), $this->text('Final_result')),
                self::line(__('Complications'), $this->text('Complications')),
                self::line(__('Others'), $this->text('Others')),
                self::line(__('Entrance time'), $this->dateTime('DateOf_entrance_date', 'entrance_time')),
                self::line(__('Exit time'), $this->dateTime('DateOf_exit_date', 'exit_time')),
            ]),
        ];
    }
}
