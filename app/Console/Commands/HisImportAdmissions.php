<?php

namespace App\Console\Commands;

use App\Support\HeartImport\HisAdmissionImporter;
use App\Support\HeartImport\LegacyValue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('his:import-admissions
    {--hospital= : Slug or id of the hospital to import into}
    {--since= : Only admissions on or after this Jalali date (YYYY/MM/DD)}
    {--limit=0 : Only the most recent admissions; 0 for all}
    {--dry-run : Read and check everything, then roll back}')]
#[Description('Import admissions and their patients from the hospital information system (connection "his")')]
class HisImportAdmissions extends Command
{
    public function handle(): int
    {
        $hospital = HeartImport::hospital($this->option('hospital'));

        if (! $hospital) {
            $this->components->error('Choose the hospital to import into with --hospital=<slug or id>.');

            return self::FAILURE;
        }

        $since = null;
        if ($this->option('since') !== null) {
            $since = LegacyValue::date($this->option('since'));
            if ($since === null) {
                $this->components->error('--since must be a Jalali date such as 1405/01/01.');

                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $summary = (new HisAdmissionImporter(DB::connection('his'), $hospital, (string) config('heart.his.view')))
            ->run($since, max(0, (int) $this->option('limit')), $dryRun);

        HeartImport::report($this, $summary, $dryRun);

        return self::SUCCESS;
    }
}
