<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep admissions in step with the hospital information system (HIS_IMPORT_HOSPITAL in .env).
// The most recent admissions are enough: older ones only change when their discharge is recorded.
if ($hospital = config('heart.his.schedule_hospital')) {
    Schedule::command('his:import-admissions', ['--hospital' => $hospital, '--limit' => 2000])
        ->cron(config('heart.his.schedule_cron'))
        ->withoutOverlapping()
        ->onOneServer();
}
