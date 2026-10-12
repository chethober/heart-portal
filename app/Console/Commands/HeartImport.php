<?php

namespace App\Console\Commands;

use App\Models\Hospital;
use App\Support\HeartImport\HeartImporter;
use App\Support\HeartImport\ImportException;
use App\Support\HeartImport\ImportSummary;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('heart:import
    {--hospital= : Slug or id of the hospital to import into}
    {--only=* : Only these tables (patients, admissions, echo, angiography, ...); repeatable or comma separated}
    {--center= : Only admissions of this heart center (admit.centerId), with their patients and studies}
    {--all-centers : Import every heart center into this one hospital}
    {--dry-run : Read and check everything, then roll back}')]
#[Description('Import patients, admissions and studies from the legacy heart database (connection "heart")')]
class HeartImport extends Command
{
    public function handle(): int
    {
        $hospital = self::hospital($this->option('hospital'));

        if (! $hospital) {
            $this->components->error('Choose the hospital to import into with --hospital=<slug or id>.');

            return self::FAILURE;
        }

        $tables = array_keys(config('heart.tables'));
        $only = array_values(array_filter(array_map('trim', explode(',', implode(',', $this->option('only'))))));

        if ($unknown = array_diff($only, $tables)) {
            $this->components->error('Unknown tables: '.implode(', ', $unknown).'. Choose from: '.implode(', ', $tables).'.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->components->info(($dryRun ? 'Dry run: ' : '')."Importing heart into {$hospital->name}.");

        try {
            $summary = (new HeartImporter(
                DB::connection('heart'),
                $hospital,
                $this->option('center') ?: null,
                (bool) $this->option('all-centers'),
                (int) config('heart.chunk', 500),
            ))->run($only, $dryRun);
        } catch (ImportException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        self::report($this, $summary, $dryRun);

        return self::SUCCESS;
    }

    public static function hospital(mixed $option): ?Hospital
    {
        if (! is_string($option) || $option === '') {
            return null;
        }

        return Hospital::where('slug', $option)->when(ctype_digit($option), fn ($query) => $query->orWhere('id', (int) $option))->first();
    }

    public static function report(Command $command, ImportSummary $summary, bool $dryRun): void
    {
        $command->table(['table', ...ImportSummary::COLUMNS], $summary->rows());

        foreach ($summary->messages() as $message) {
            $command->line("  {$message}");
        }

        if ($dryRun) {
            $command->components->warn('Dry run: nothing was saved.');
        }
    }
}
