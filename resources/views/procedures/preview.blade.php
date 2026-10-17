@php
    /**
     * The report for unsaved procedure form data, on its own page: shown in the form's live preview
     * pane and when the preview is opened in a new tab (like studies/preview.php for echo).
     *
     * @var string $procedure
     * @var array<string, mixed> $report
     * @var \App\Reports\ProcedureReport|null $narrative
     * @var \App\Models\Hospital|null $hospital
     * @var \App\Models\Admission|null $admission
     */
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ App\Support\Procedures::label($procedure) }} Report — {{ $hospital?->name }}</title>
    <x-favicons />
    @include('studies.partials.report-styles')
    <style>
        body { margin: 0; padding: 8px; background: #fff; }
        .echo-report .no-print { display: none !important; }
        .echo-report .report-container { padding: 10px; }
    </style>
</head>
<body>
    <div class="echo-report">
        @include('procedures.partials.report-document', ['study' => null, 'version' => null])
    </div>
</body>
</html>
