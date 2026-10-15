<?php

use App\Models\Hospital;

/**
 * The report for unsaved form data, on its own page: shown in the echo form's live preview pane
 * and when the preview is opened in a new tab.
 *
 * @var array<string, mixed> $report  Output of EchoCalculations::derive().
 * @var Hospital $hospital
 */
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Echo Report — <?= e($hospital->name) ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <?= view('studies.partials.report-styles')->render() ?>
    <style>
        body { margin: 0; padding: 8px; background: #fff; }
        .echo-report .no-print { display: none !important; }
        .echo-report .report-container { padding: 10px; }
    </style>
</head>
<body>
    <div class="echo-report">
        <?= view('studies.partials.report-document', ['report' => $report, 'hospital' => $hospital, 'study' => null, 'version' => null])->render() ?>
    </div>
</body>
</html>
