<?php

namespace App\Console\Commands;

use App\Models\Hospital;
use Database\Seeders\HeartDemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-heart-demo')]
#[Description('Add fictional patients, admissions and heart-system studies to the demo hospital')]
class SeedHeartDemo extends Command
{
    public function handle(): int
    {
        // Only ever the demo hospital, so demo patients cannot end up in a real one.
        $hospital = Hospital::where('slug', 'demo-heart-hospital')->first();

        if (! $hospital) {
            $this->components->error('The demo hospital does not exist yet; create it first (app:seed-demo).');

            return self::FAILURE;
        }

        $result = (new HeartDemoSeeder)->run($hospital);

        $this->components->info("Added {$result['created']} demo studies (with their patients and admissions); {$result['skipped']} were already there.");

        return self::SUCCESS;
    }
}
