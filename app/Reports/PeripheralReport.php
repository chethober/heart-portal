<?php

namespace App\Reports;

/**
 * Peripheral angiography report, ported from heart's reports/templates/peripheral.js: a table of
 * technical details, then indication, description, result, recommendation and complications.
 */
final class PeripheralReport extends ProcedureReport
{
    public const PROCEDURE = 'peripheral';

    protected const SUMMARY = ['Final result'];

    public function title(): string
    {
        return __('Peripheral Angiography Report');
    }

    protected function build(): array
    {
        return [
            self::section(__('Technical details'), [], [
                [__('Date'), __('Time'), __('Contrast'), __('Volume'), __('Access'), __('Catheter'), __('Vessels')],
                array_map(fn (string $name) => $this->text($name) ?? '', ['DateOf_date_peripheral', 'time_peripheral', 'contrast', 'Volume', 'Access', 'Catheter', 'vessels']),
            ]),
            self::section(__('Procedure'), [
                self::line(__('Indication for procedure'), $this->text('Indication_procedure')),
                self::line(__('Description of procedure'), $this->text('Description_procedure')),
                self::line(__('Final result'), $this->text('Final_result')),
                self::line(__('Recommendation'), $this->text('Recommendation')),
                self::line(__('Complication'), $this->text('Complication')),
                self::line(__('Others'), $this->text('Others')),
                self::line(__('Entrance time'), $this->dateTime('DateOf_entrance_date', 'entrance_time')),
                self::line(__('Exit time'), $this->dateTime('DateOf_exit_date', 'exit_time')),
            ]),
        ];
    }
}
