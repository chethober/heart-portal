<?php
/**
 * Patient echo history, ported from the legacy view_echo.php.
 *
 * @var string $nationalID  The searched national ID ('' for the search form).
 * @var list<array<string, mixed>> $echoReports  Derived report rows, newest first.
 */

use App\Support\ReportHtml;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Patient Echos — <?= e(auth()->user()->hospital->name) ?></title>
    <link type="text/css" rel="stylesheet" href="/legacy/fonts.css">
    <link type="text/css" rel="stylesheet" href="/legacy/navbar.css">
    <style>
        /* Tooltip Container */
        .tooltip-container {
            position: relative;
            display: inline-block;
            cursor: help;
            border-bottom: 1px dotted #2b6cb0;
            color: #2b6cb0;
        }

        /* Tooltip Table Box */
        .tooltip-table-box {
            visibility: hidden;
            position: absolute;
            z-index: 9999;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ffffff;
            color: #2d3748;
            padding: 8px 10px;
            border-radius: 6px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            width: min(320px, calc(100vw - 32px));
            font-size: 11px;
            line-height: 1.4;
            transition: opacity 0.2s, visibility 0.2s;
            opacity: 0;
            text-align: left;
            pointer-events: none;
        }

        .tooltip-container:hover .tooltip-table-box {
            visibility: visible;
            opacity: 1;
        }

        :root {
            --gap: 12px;
            --w: calc(25% - var(--gap));
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.4;
            margin: 12px;
            background: #f6f7fb;
            color: #111;
            -webkit-font-smoothing: antialiased;
        }

        @media (min-width: 768px) {
            body {
                margin: 16px;
            }
        }

        h1 {
            font-size: 22px;
            margin-bottom: 12px;
            color: #1a202c;
        }

        .report-container {
            width: 100%;
            max-width: 1200px;
            box-sizing: border-box;
            margin: 0 auto 24px auto;
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .05);
        }

        @media (min-width: 768px) {
            .report-container {
                padding: 24px;
            }
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: var(--gap);
            margin-bottom: 12px;
            width: 100%;
            box-sizing: border-box;
        }

        .field {
            display: flex;
            flex-direction: column;
            flex: 1 1 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        @media (min-width: 640px) {
            .field {
                flex: 1 1 calc(50% - var(--gap));
            }
        }

        @media (min-width: 1024px) {
            .field {
                flex: 1 1 calc(25% - var(--gap));
                min-width: 200px;
            }
        }

        label {
            font-size: 13px;
            font-weight: 550;
            margin-bottom: 6px;
            color: #2d3748;
        }

        .value {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14.5px;
            background: #f8fafc;
            min-height: 44px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            box-sizing: border-box;
            width: 100%;
            word-break: break-word;
        }

        details {
            margin: 12px 0;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fbfbff;
            padding: 10px 12px;
        }

        summary {
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            padding: 8px 4px;
            min-height: 44px;
            display: flex;
            align-items: center;
            user-select: none;
        }

        .small {
            font-size: 12px;
            color: #718096;
        }

        button:not(.voice-mic-btn):not(.navbar-hamburger), .btn {
            padding: 10px 18px;
            border-radius: 8px;
            border: 0;
            background: #2b6cb0;
            color: #fff;
            cursor: pointer;
            min-height: 44px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        button:not(.voice-mic-btn):not(.navbar-hamburger):hover, .btn:hover {
            background: #2c5282;
        }

        .hidden {
            display: none !important;
        }

        .header {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #edf2f7;
        }

        .header h1 {
            font-size: 22px;
            color: #2c3e50;
            margin: 0;
        }

        .patient-info {
            background: #f8fafc;
            padding: 16px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section h2 {
            color: #2b6cb0;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 8px;
            margin-top: 24px;
            margin-bottom: 14px;
            font-size: 18px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #edf2f7;
            color: #718096;
            font-size: 13px;
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #4a5568;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-view {
            text-align: center;
            padding: 40px 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-form-responsive {
            display: flex;
            gap: 8px;
            max-width: 480px;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            padding: 6px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            box-sizing: border-box;
            width: 100%;
        }

        .search-form-responsive input {
            flex: 1 1 auto;
            border: none;
            padding: 10px 14px;
            font-size: 15px;
            border-radius: 6px;
            outline: none;
            min-height: 44px;
            width: 100%;
            box-sizing: border-box;
        }

        .search-form-responsive button {
            padding: 10px 20px;
            background: #3182ce;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            min-height: 44px;
            flex-shrink: 0;
        }

        @media (max-width: 520px) {
            .search-form-responsive {
                flex-direction: column;
                padding: 10px;
            }
            .search-form-responsive button {
                width: 100%;
            }
        }

        /* Reports Table Responsiveness */
        .reports-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin: 20px 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .reports-table th {
            padding: 12px 14px;
            text-align: left;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #2d3748;
            white-space: nowrap;
        }

        .reports-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #edf2f7;
            color: #2d3748;
        }

        .reports-table tr:hover {
            background-color: #fbfbff;
        }

        /* Mobile Card Reflow for Reports Table on small screens (<720px) */
        @media (max-width: 720px) {
            .reports-table-wrapper {
                border: none;
                box-shadow: none;
                overflow-x: visible;
            }

            .reports-table,
            .reports-table thead,
            .reports-table tbody,
            .reports-table tr,
            .reports-table th,
            .reports-table td {
                display: block;
                width: 100%;
            }

            .reports-table thead {
                display: none;
            }

            .reports-table tr {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 14px;
                margin-bottom: 14px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            }

            .reports-table td {
                padding: 8px 0;
                border-bottom: 1px solid #f1f5f9;
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .reports-table td:last-child {
                border-bottom: none;
                padding-top: 12px;
            }

            .reports-table td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 12px;
                color: #718096;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .reports-table td .actions-group {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .reports-table td .btn-view,
            .reports-table td .btn-delete {
                flex: 1 1 auto;
                justify-content: center;
                min-height: 44px;
            }
        }

        .btn-view {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            background-color: #38a169;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 550;
            min-height: 38px;
            box-sizing: border-box;
            transition: background 0.2s;
        }

        .btn-view:hover {
            background-color: #2f855a;
        }

        .btn-delete {
            padding: 8px 14px;
            background-color: #e53e3e;
            color: white;
            border-radius: 6px;
            border: 0;
            cursor: pointer;
            font-size: 13.5px;
            font-weight: 550;
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            transition: background 0.2s;
        }

        .btn-delete:hover {
            background-color: #c53030;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .report-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .tooltip-container {
                border-bottom: none !important;
                color: inherit !important;
                cursor: default !important;
            }
        }
    </style>
</head>
<body>
    <?= view('clinical.navbar')->render() ?>
    
    <div class="report-container">
        <?php if (empty($nationalID)) { ?>
            <!-- Beautiful search view -->
            <div class="search-view">
                <div style="font-size: 48px; margin-bottom: 16px; color: #3182ce;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <h2 style="font-size: 22px; color: #2d3748; margin-bottom: 12px; font-weight: 700;">Find Patient Echo History</h2>
                <p style="color: #718096; margin-bottom: 24px; font-size: 15px; line-height: 1.5;">Enter the patient's National ID below to search for their recorded echocardiograms, view report summaries, or generate detailed printouts.</p>
                
                <form action="<?= e(route('patients.index')) ?>" method="get" class="search-form-responsive" role="search">
                    <input type="text" name="nationalID" placeholder="Enter National ID..." required aria-label="National ID">
                    <button type="submit">Search</button>
                </form>
            </div>
        <?php } elseif (empty($echoReports)) { ?>
            <div class="no-results">
                <div style="font-size: 48px; margin-bottom: 16px; color: #e53e3e;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h2 style="font-size: 22px; color: #2d3748; margin-bottom: 12px;">No Echo Reports Found</h2>
                <p style="color: #718096; margin-bottom: 24px; font-size: 15px;">We couldn't find any reports for the National ID: <strong><?= htmlspecialchars($nationalID) ?></strong></p>
                <a href="<?= e(route('patients.index')) ?>" class="btn" style="background: #e2e8f0; color: #4a5568;">
                    Try Another Search
                </a>
            </div>
        <?php } else { ?>
            <h2 style="font-size: 20px; color: #1a202c; margin-bottom: 6px;">Echocardiogram Reports for <?= htmlspecialchars($echoReports[0]['first_name'].' '.$echoReports[0]['last_name']) ?></h2>
            <p style="color: #718096; margin-bottom: 16px;">National ID: <strong><?= htmlspecialchars($nationalID) ?></strong></p>
            
            <div class="reports-list">
                <div class="reports-table-wrapper">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Summary</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($echoReports as $report) { ?>
                            <tr>
                                <td data-label="Date">
                                    <?= date('Y-m-d', strtotime($report['created_at'])) ?>
                                    <?php $badge = ['draft' => ['Draft', '#c53030', '#fff5f5'], 'final' => ['Final', '#276749', '#f0fff4'], 'amended' => ['Amended', '#975a16', '#fffff0']][$report['status']] ?? null; ?>
                                    <?php if ($badge) { ?>
                                    <span style="display:inline-block;margin-left:6px;padding:1px 8px;border-radius:9999px;font-size:11px;font-weight:600;color:<?= $badge[1] ?>;background:<?= $badge[2] ?>;border:1px solid <?= $badge[1] ?>"><?= $badge[0] ?></span>
                                    <?php } ?>
                                </td>
                                <td data-label="Summary"><?= ! empty($report['summary']) ? substr(htmlspecialchars($report['summary']), 0, 120).'...' : 'No summary available' ?></td>
                                <td data-label="Actions">
                                    <div class="actions-group" style="display: flex; gap: 8px; align-items: center;">
                                        <a href="<?= e(route('studies.show', ['study' => $report['id'], 'nationalID' => $nationalID])) ?>" class="btn-view">View Full Report</a>
                                        <?php if (! empty($report['can_edit'])) { ?>
                                        <a href="<?= e(route('studies.edit', $report['id'])) ?>" class="btn-view" style="background:#4a5568"><?= $report['status'] === 'draft' ? 'Edit' : 'Amend' ?></a>
                                        <?php } ?>
                                        <?php $showDelete = ! empty($report['can_delete']); ?>
                                        <?php if ($showDelete) { ?>
                                        <form method="POST" action="<?= e(route('studies.destroy', $report['id'])) ?>" class="no-print" style="display: inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this report?');">
                                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (request()->filled('id') || request()->filled('print')) { ?>
                <?php
                    $report = null;
                $reportId = request()->query('id') ?? request()->query('print');
                if ($reportId) {
                    foreach ($echoReports as $r) {
                        if (($r['id'] ?? '') == $reportId) {
                            $report = $r;
                            break;
                        }
                    }
                }
                if ($report) {
                    ?>
                <div id="detailed-report" class="detailed-report" style="margin-top: 30px; page-break-before: always;">

                <div class="patient-info">
                    <div class="row">
                        <div class="field">
                            <label>First Name:</label>
                            <div class="value"><?= htmlspecialchars($report['first_name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="field">
                            <label>Last Name:</label>
                            <div class="value"><?= htmlspecialchars($report['last_name'] ?? 'N/A') ?></div>
                        </div>
                        <div class="field">
                            <label>Date of Birth:</label>
                            <div class="value"><?= ! empty($report['dob']) ? date('F j, Y', strtotime($report['dob'])) : 'N/A' ?></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="field">
                            <label>National ID:</label>
                            <div class="value"><?= htmlspecialchars($report['nationalID'] ?? 'N/A') ?></div>
                        </div>
                        <div class="field">
                            <label>Gender:</label>
                            <div class="value"><?= htmlspecialchars($report['gender'] ?? 'N/A') ?></div>
                        </div>
                        <div class="field">
                            <div class="label">Date of Echo</div>
                            <div class="value"><?= htmlspecialchars($report['echo_date'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>Clinical Information</h2>
                    <div class="row">
                        <div class="field">
                            <div class="label">Echo Indication</div>
                            <div class="value">
                                <?php
                                    $indication = '';
                    switch ($report['Echo_indication']) {
                        case '1': $indication = 'Chest pain';
                            break;
                        case '2': $indication = 'Dyspnea';
                            break;
                        case '3': $indication = 'Palpitations';
                            break;
                        case '4': $indication = 'Evaluation cardiac murmur';
                            break;
                        case '99': $indication = 'Other: '.htmlspecialchars($report['other_Echo_indication']);
                            break;
                        default: $indication = 'Not specified';
                    }
                    echo ReportHtml::clean($indication);
                    ?>
                            </div>
                        </div>
                        <div class="field">
                            <div class="label">History of Diseases</div>
                            <div class="value"><?= ! empty($report['History_diseases']) ? nl2br(htmlspecialchars($report['History_diseases'])) : 'None reported' ?></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="field">
                            <div class="label">Blood Pressure</div>
                            <div class="value"><?= ! empty($report['BP']) ? htmlspecialchars($report['BP']) : 'N/A' ?> mmHg</div>
                        </div>
                        <div class="field">
                            <div class="label">Heart Rate</div>
                            <div class="value"><?= ! empty($report['HR']) ? htmlspecialchars($report['HR']).' bpm' : 'N/A' ?></div>
                        </div>
                        <div class="field">
                            <div class="label">O2 Saturation</div>
                            <div class="value"><?= ! empty($report['O2_sat']) ? htmlspecialchars($report['O2_sat']).'%' : 'N/A' ?></div>
                        </div>
                    </div>
                    <div class="row">
                        <?php if (! empty($report['Height'])) { ?>
                        <div class="field">
                            <div class="label">Height</div>
                            <div class="value"><?= htmlspecialchars($report['Height']) ?> cm</div>
                        </div>
                        <?php } ?>
                        <?php if (! empty($report['Weight'])) { ?>
                        <div class="field">
                            <div class="label">Weight</div>
                            <div class="value"><?= htmlspecialchars($report['Weight']) ?> kg</div>
                        </div>
                        <?php } ?>
                        <?php if (! empty($report['computed_BSA'])) { ?>
                        <div class="field">
                            <div class="label">BSA</div>
                            <div class="value">
                                <span class="tooltip-container" style="border-bottom: 1px dotted #0066cc; color: #0066cc; cursor: help;">
                                    <?= round($report['computed_BSA'], 2) ?> m²
                                    <span class="tooltip-table-box no-print" style="width: 250px;">
                                        <strong style="display: block; margin-bottom: 5px; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">BSA Calculation (Mosteller)</strong>
                                        <span style="display: block; margin-top: 5px; font-size: 10px; line-height: 1.5; font-weight: normal; color: #2d3748;">
                                            <strong>Formula:</strong> √((Height × Weight) / 3600)<br>
                                            <strong>Height:</strong> <?= htmlspecialchars($report['Height'] ?? 0) ?> cm<br>
                                            <strong>Weight:</strong> <?= htmlspecialchars($report['Weight'] ?? 0) ?> kg<br>
                                            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 5px 0;">
                                            <strong>Calculation:</strong> √((<?= htmlspecialchars($report['Height'] ?? 0) ?> × <?= htmlspecialchars($report['Weight'] ?? 0) ?>) / 3600) = <?= round($report['computed_BSA'], 4) ?> m²
                                        </span>
                                    </span>
                                </span>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <?php if (! empty($report['lv_EF']) || ! empty($report['lv_LVEDD']) || ! empty($report['lv_LVEDV']) || ! empty($report['computed_LVEDVI'])) { ?>
                <div class="section">
                    <h2>Left Ventricle</h2>
                    <div class="row">
                        <?php if (! empty($report['lv_EF'])) { ?>
                        <div class="field">
                            <div class="label">Ejection Fraction (EF)</div>
                            <div class="value"><?= htmlspecialchars($report['lv_EF']) ?>%</div>
                        </div>
                        <?php } ?>
                        <?php if (! empty($report['lv_LVEDD'])) { ?>
                        <div class="field">
                            <div class="label">LV End-Diastolic Dimension</div>
                            <div class="value"><?= htmlspecialchars($report['lv_LVEDD']) ?> mm</div>
                        </div>
                        <?php } ?>
                        <?php if (! empty($report['lv_LVESD'])) { ?>
                        <div class="field">
                            <div class="label">LV End-Systolic Dimension</div>
                            <div class="value"><?= htmlspecialchars($report['lv_LVESD']) ?> mm</div>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="row">
                        <?php if (! empty($report['lv_LVEDV'])) { ?>
                        <div class="field">
                            <div class="label">LVEDV</div>
                            <div class="value"><?= htmlspecialchars($report['lv_LVEDV']) ?> cc</div>
                        </div>
                        <?php } ?>
                        <?php if (! empty($report['computed_LVEDVI'])) { ?>
                        <div class="field">
                            <div class="label">LVEDVI</div>
                            <div class="value"><?= round($report['computed_LVEDVI'], 1) ?> cc/m²</div>
                        </div>
                        <?php } ?>
                        <?php if (! empty($report['computed_LVEDDI'])) { ?>
                        <div class="field">
                            <div class="label">LVEDDI</div>
                            <div class="value"><?= round($report['computed_LVEDDI'], 1) ?> mm/m²</div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>

                <div class="section">
                    <h2>Summary</h2>
                    <div class="value" style="padding: 15px; margin-bottom: 20px; background: #f8f9fa; border-radius: 6px; line-height: 1.6;">
                        <?php
                        // Function to get display text from value
                        if (! function_exists('historyDisplayText')) {
                            function historyDisplayText($value, $options)
                            {
                                if ($value === null || $value === '' || $value === '-1') {
                                    return null;
                                }
                                if (! is_array($options)) {
                                    return $value;
                                }
                                foreach ($options as $option) {
                                    if (is_array($option) && isset($option['value']) && $option['value'] == $value) {
                                        return $option['text'] ?? $value;
                                    }
                                }

                                return $value;
                            }
                        }

                    if (! function_exists('historyAutoTooltipHtml')) {
                        function historyAutoTooltipHtml($fieldKey, $gender, $inputUsed, $sizeText, $inputValue = null)
                        {
                            $isMale = (strcasecmp($gender, 'Male') === 0 || $gender === '1');
                            $isFemale = (strcasecmp($gender, 'Female') === 0 || $gender === '2');
                            $genderText = $isMale ? 'Male' : ($isFemale ? 'Female' : 'Unknown');

                            $title = '';
                            $headers = [];
                            $rows = [];

                            if ($fieldKey === 'lv_size') {
                                $title = 'LV Size Reference Ranges ('.$genderText.')';
                                $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev'];
                                if ($isFemale) {
                                    $rows = [
                                        'LVEDVI' => ['unit' => 'cc/m²', 'normal' => '≤ 61', 'mild' => '62–70', 'mod' => '71–80', 'sev' => '> 80'],
                                        'LVEDV' => ['unit' => 'cc',    'normal' => '≤ 106', 'mild' => '107–120', 'mod' => '121–130', 'sev' => '> 130'],
                                        'LVEDDI' => ['unit' => 'mm/m²', 'normal' => '≤ 31', 'mild' => '32–34', 'mod' => '35–37', 'sev' => '> 37'],
                                        'LVEDD' => ['unit' => 'mm',    'normal' => '≤ 52', 'mild' => '53–56', 'mod' => '57–61', 'sev' => '> 61'],
                                    ];
                                } else {
                                    $rows = [
                                        'LVEDVI' => ['unit' => 'cc/m²', 'normal' => '≤ 74', 'mild' => '75–89', 'mod' => '90–100', 'sev' => '> 100'],
                                        'LVEDV' => ['unit' => 'cc',    'normal' => '≤ 150', 'mild' => '151–174', 'mod' => '175–200', 'sev' => '> 200'],
                                        'LVEDDI' => ['unit' => 'mm/m²', 'normal' => '≤ 30', 'mild' => '31–33', 'mod' => '34–36', 'sev' => '> 36'],
                                        'LVEDD' => ['unit' => 'mm',    'normal' => '≤ 58', 'mild' => '59–63', 'mod' => '64–68', 'sev' => '> 68'],
                                    ];
                                }
                            } elseif ($fieldKey === 'lv_Systolic') {
                                $title = 'LV Systolic Reference Ranges ('.$genderText.')';
                                $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev'];
                                if ($isFemale) {
                                    $rows = [
                                        'EF' => ['unit' => '%', 'normal' => '≥ 54', 'mild' => '41–53', 'mod' => '30–40', 'sev' => '< 30'],
                                    ];
                                } else {
                                    $rows = [
                                        'EF' => ['unit' => '%', 'normal' => '≥ 52', 'mild' => '41–51', 'mod' => '30–40', 'sev' => '< 30'],
                                    ];
                                }
                            } elseif ($fieldKey === 'lv_Hypertrophy') {
                                $title = 'LV Hypertrophy Reference Ranges ('.$genderText.')';
                                $headers = ['Parameter', 'No LVH', 'Mild', 'Mod', 'Sev'];
                                if ($isFemale) {
                                    $rows = [
                                        'IVS' => ['unit' => 'mm', 'normal' => '< 10', 'mild' => '10–12.9', 'mod' => '13–15', 'sev' => '> 15'],
                                    ];
                                } else {
                                    $rows = [
                                        'IVS' => ['unit' => 'mm', 'normal' => '< 11', 'mild' => '11–13.9', 'mod' => '14–16', 'sev' => '> 16'],
                                    ];
                                }
                            } elseif ($fieldKey === 'lv_Diastolic') {
                                $title = 'LV Diastolic Function Algorithm';
                                $headers = ['Parameter / Category', 'Clinical Criteria / Grading'];
                                $rows = [
                                    "Reduced e' velocity" => ['unit' => 'cm/s', 'criteria' => 'septal ≤ 6, lateral ≤ 7, average ≤ 6.5'],
                                    "Increased E/e'" => ['unit' => '', 'criteria' => 'septal ≥ 15, lateral ≥ 13, average ≥ 14'],
                                    'TR Vel / PASP' => ['unit' => '', 'criteria' => 'TR velocity ≥ 2.8 m/s or PASP ≥ 35 mmHg'],
                                    'All Normal Group' => ['unit' => '', 'criteria' => 'Normal Diastolic Function'],
                                    "Reduced e' only Group" => ['unit' => '', 'criteria' => 'E/A ≤ 0.8: Grade I; E/A > 0.8: Indeterminate'],
                                    '3 Abnormal Group' => ['unit' => '', 'criteria' => 'E/A < 2: Grade II; E/A ≥ 2: Grade III'],
                                    '1-2 Abnormal Group' => ['unit' => '', 'criteria' => 'Indeterminate Diastolic Function'],
                                ];
                            } elseif ($fieldKey === 'rv_size') {
                                $title = 'RV Size Reference Ranges';
                                $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev'];
                                $rows = [
                                    'RV Basal' => ['unit' => 'mm', 'normal' => '≤ 40', 'mild' => '41–44', 'mod' => '45–49', 'sev' => '> 49'],
                                    'RV Mid' => ['unit' => 'mm', 'normal' => '≤ 35', 'mild' => '36–38', 'mod' => '39–43', 'sev' => '> 43'],
                                ];
                            } elseif ($fieldKey === 'rv_Systolic') {
                                $title = 'RV Systolic Reference Ranges';
                                $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev'];
                                $rows = [
                                    'TAPSE' => ['unit' => 'mm', 'normal' => '> 17', 'mild' => '14–17', 'mod' => '10–13', 'sev' => '< 10'],
                                ];
                            } elseif ($fieldKey === 'la_size') {
                                $title = 'LA Size Reference Ranges ('.$genderText.')';
                                $headers = ['Parameter', 'Normal', 'Mild', 'Mod', 'Sev / Enlarged'];
                                if ($isFemale) {
                                    $rows = [
                                        'LAVI' => ['unit' => 'cc/m²', 'normal' => '≤ 34', 'mild' => '35–41', 'mod' => '42–48', 'sev' => '> 48'],
                                        'LA Area' => ['unit' => 'cm²',   'normal' => '≤ 20', 'mild' => '21–30', 'mod' => '31–41', 'sev' => '> 41'],
                                        'LA Diam' => ['unit' => 'mm',    'normal' => '≤ 38', 'mild' => 'N/A',   'mod' => 'N/A',   'sev' => '> 38'],
                                        'LADI' => ['unit' => 'mm/m²', 'normal' => '≤ 23', 'mild' => 'N/A',   'mod' => 'N/A',   'sev' => '> 23'],
                                    ];
                                } else {
                                    $rows = [
                                        'LAVI' => ['unit' => 'cc/m²', 'normal' => '≤ 34', 'mild' => '35–41', 'mod' => '42–48', 'sev' => '> 48'],
                                        'LA Area' => ['unit' => 'cm²',   'normal' => '≤ 20', 'mild' => '21–30', 'mod' => '31–41', 'sev' => '> 41'],
                                        'LA Diam' => ['unit' => 'mm',    'normal' => '≤ 40', 'mild' => 'N/A',   'mod' => 'N/A',   'sev' => '> 40'],
                                        'LADI' => ['unit' => 'mm/m²', 'normal' => '≤ 23', 'mild' => 'N/A',   'mod' => 'N/A',   'sev' => '> 23'],
                                    ];
                                }
                            } elseif ($fieldKey === 'ra_size') {
                                $title = 'RA Size Reference Ranges';
                                $headers = ['Parameter', 'Normal', 'Enlarged'];
                                $rows = [
                                    'RA Area' => ['unit' => 'cm²', 'normal' => '< 18', 'enlarged' => '≥ 18'],
                                ];
                            }

                            if (empty($title)) {
                                return htmlspecialchars($sizeText);
                            }

                            $displayInput = $inputUsed;
                            if ($inputValue !== null && $inputValue !== '') {
                                if ($fieldKey === 'lv_Diastolic') {
                                    $displayInput = $inputValue;
                                } else {
                                    $units = [
                                        'LVEDVI' => 'cc/m²',
                                        'LVEDV' => 'cc',
                                        'LVEDDI' => 'mm/m²',
                                        'LVEDD' => 'mm',
                                        'EF' => '%',
                                        'IVS' => 'mm',
                                        'RV Basal' => 'mm',
                                        'RV Mid' => 'mm',
                                        'TAPSE' => 'mm',
                                        'LAVI' => 'cc/m²',
                                        'LA Area' => 'cm²',
                                        'LA Diam' => 'mm',
                                        'LADI' => 'mm/m²',
                                        'RA Area' => 'cm²',
                                    ];
                                    $unitStr = isset($units[$inputUsed]) ? ' '.$units[$inputUsed] : '';
                                    if (trim($unitStr) === '%') {
                                        $unitStr = '%';
                                    }
                                    $displayInput = $inputUsed.' = '.$inputValue.$unitStr;
                                }
                            }

                            $html = '<span class="tooltip-container">'.htmlspecialchars($sizeText).' ('.htmlspecialchars($displayInput).')';
                            $html .= '<span class="tooltip-table-box no-print">';
                            $html .= '<strong style="display: block; margin-bottom: 5px; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;">'.htmlspecialchars($title).'</strong>';
                            $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 10px;">';
                            $html .= '<thead>';
                            $html .= '<tr style="background-color: #f7fafc;">';
                            foreach ($headers as $h) {
                                $html .= '<th style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center; font-weight: bold; color: #4a5568;">'.htmlspecialchars($h).'</th>';
                            }
                            $html .= '</tr>';
                            $html .= '</thead>';
                            $html .= '<tbody>';

                            foreach ($rows as $param => $r) {
                                $isHighlighted = (strcasecmp($param, $inputUsed) === 0);
                                $rowStyle = $isHighlighted ? ' style="background-color: #ebf8ff; color: #2b6cb0; font-weight: bold;"' : '';
                                $html .= '<tr'.$rowStyle.'>';
                                $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;"><strong>'.htmlspecialchars($param).'</strong> <span style="color:#718096; font-size:9px;">('.htmlspecialchars($r['unit']).')</span></td>';

                                $keys = array_keys($r);
                                foreach ($keys as $k) {
                                    if ($k === 'unit') {
                                        continue;
                                    }
                                    $html .= '<td style="border: 1px solid #e2e8f0; padding: 3px 4px; text-align: center;">'.htmlspecialchars($r[$k]).'</td>';
                                }
                                $html .= '</tr>';
                            }

                            $html .= '</tbody>';
                            $html .= '</table>';
                            $html .= '</span>';
                            $html .= '</span>';

                            return $html;
                        }
                    }

                    // Define options for each dropdown
                    $dropdownOptions = [
                        'Echo_indication' => [
                            ['value' => '1', 'text' => 'Chest pain'],
                            ['value' => '2', 'text' => 'Dyspnea'],
                            ['value' => '3', 'text' => 'Palpitations'],
                            ['value' => '4', 'text' => 'Evaluation cardiac murmur'],
                            ['value' => '99', 'text' => 'other'],
                        ],
                        'Rhythm' => [
                            ['value' => '1', 'text' => 'sinus'],
                            ['value' => '2', 'text' => 'AF'],
                            ['value' => '3', 'text' => 'flutter'],
                            ['value' => '4', 'text' => 'paced'],
                            ['value' => '99', 'text' => 'other'],
                        ],
                        'Echo_type' => [
                            ['value' => '1', 'text' => 'Transthoracic echocardiography (TTE)'],
                            ['value' => '2', 'text' => 'Transesophageal echocardiography (TEE)'],
                            ['value' => '3', 'text' => 'Stress echocardiography'],
                            ['value' => '99', 'text' => 'other'],
                        ],
                        'lv_size' => [
                            ['value' => '1', 'text' => 'Normal LV size'],
                            ['value' => '2', 'text' => 'Mild LV enlargement'],
                            ['value' => '3', 'text' => 'Mild to moderate LV enlargement'],
                            ['value' => '4', 'text' => 'Moderate LV enlargement'],
                            ['value' => '5', 'text' => 'Moderate to severe LV enlargement'],
                            ['value' => '6', 'text' => 'Severe LV enlargement'],
                        ],
                        'lv_Systolic' => [
                            ['value' => '1', 'text' => 'With normal systolic function'],
                            ['value' => '2', 'text' => 'With mild systolic dysfunction'],
                            ['value' => '3', 'text' => 'With moderate systolic dysfunction'],
                            ['value' => '4', 'text' => 'With severe systolic dysfunction'],
                        ],
                        'lv_Diastolic' => [
                            ['value' => '1', 'text' => 'Normal diastolic function'],
                            ['value' => '2', 'text' => 'Grade I diastolic dysfunction'],
                            ['value' => '3', 'text' => 'Grade II diastolic dysfunction'],
                            ['value' => '4', 'text' => 'Grade III diastolic dysfunction'],
                            ['value' => '5', 'text' => 'Indeterminate diastolic function'],
                        ],
                        'lv_Hypertrophy' => [
                            ['value' => '1', 'text' => 'No LVH'],
                        ],
                    ];
                    $summaryParts = [];

                    // Patient Info
                    $patientInfo = [];
                    if (! empty($report['first_name']) || ! empty($report['last_name'])) {
                        $patientInfo[] = '<span class="bold">'.trim($report['first_name'].' '.$report['last_name']).'</span>';
                    }
                    if (! empty($report['gender'])) {
                        $patientInfo[] = ($report['gender'] == 1 ? 'Male' : 'Female');
                    }
                    if (! empty($report['age'])) {
                        $patientInfo[] = $report['age'].' years';
                    }
                    if (! empty($report['national_id'])) {
                        $patientInfo[] = 'ID: '.$report['national_id'];
                    }

                    $summaryParts = array_merge($summaryParts, $dopplerMeasurements);

                    // Build summary following echo2.js format
                    $summaryParts = [];
                    $summaryParts[] = '<span class="bold">Echocardiography indication</span><br>';

                    // Vitals section
                    if (! empty($report['BP'])) {
                        $summaryParts[] = 'BP= '.$report['BP'].' mm/Hg &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['HR'])) {
                        $summaryParts[] = 'HR= '.$report['HR'].' bpm &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['Height'])) {
                        $summaryParts[] = 'Height= '.$report['Height'].' cm &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['Weight'])) {
                        $summaryParts[] = 'Weight= '.$report['Weight'].' kg &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    }

                    // Add rhythm with special handling for 'other' option
                    if (! empty($report['Rhythm'])) {
                        $rhythm = $report['Rhythm'];
                        if ($rhythm == 99 && ! empty($report['other_Rhythm'])) {
                            $summaryParts[] = 'Rhythm= '.htmlspecialchars($report['other_Rhythm']).' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                        } elseif (isset($dropdownOptions['Rhythm'][$rhythm])) {
                            $summaryParts[] = 'Rhythm= '.$dropdownOptions['Rhythm'][$rhythm].' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                        }
                    }

                    if (! empty($report['O2_sat'])) {
                        $summaryParts[] = 'O2_sat= '.$report['O2_sat'].' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    $summaryParts[] = '<br>';

                    // Measurements section
                    $summaryParts[] = '<span class="bold">Measurements:</span><br>';

                    // Left Ventricle measurements
                    $lvMeasurements = [];
                    if (! empty($report['lv_LVEDV'])) {
                        $lvMeasurements[] = 'LVEDV= '.$report['lv_LVEDV'].' cc &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['lv_LVESD'])) {
                        $lvMeasurements[] = 'LVESD= '.$report['lv_LVESD'].' mm &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['lv_LVEDD'])) {
                        $lvMeasurements[] = 'LVEDD= '.$report['lv_LVEDD'].' mm &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['lv_IVS_thickness'])) {
                        $lvMeasurements[] = 'IVS_thickness= '.$report['lv_IVS_thickness'].' mm &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['lv_Posterior'])) {
                        $lvMeasurements[] = 'Posterior= '.$report['lv_Posterior'].' mm &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['lv_EF'])) {
                        $lvMeasurements[] = 'EF= '.$report['lv_EF'].' % &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['computed_BSA'])) {
                        $lvMeasurements[] = 'BSA= '.round($report['computed_BSA'], 2).' m² &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['computed_LVEDVI'])) {
                        $lvMeasurements[] = 'LVEDVI= '.round($report['computed_LVEDVI'], 1).' cc/m² &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['computed_LVEDDI'])) {
                        $lvMeasurements[] = 'LVEDDI= '.round($report['computed_LVEDDI'], 1).' mm/m² &nbsp;&nbsp;&nbsp;';
                    }

                    $summaryParts = array_merge($summaryParts, $lvMeasurements);
                    $summaryParts[] = '<br>';

                    // RV measurements
                    $rvMeasurements = [];
                    if (! empty($report['rv_RV_basal_diameter'])) {
                        $rvMeasurements[] = 'RV_basal_diameter= '.$report['rv_RV_basal_diameter'].' mm &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['rv_RV_mid_diameter'])) {
                        $rvMeasurements[] = 'RV_mid_diameter= '.$report['rv_RV_mid_diameter'].' mm &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['rv_RV_longitudinal_diameter'])) {
                        $rvMeasurements[] = 'RV_longitudinal_diameter= '.$report['rv_RV_longitudinal_diameter'].' mm &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['rv_S_velocity'])) {
                        $rvMeasurements[] = 'S_velocity= '.$report['rv_S_velocity'].' cm/s &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['rv_TAPSE'])) {
                        $rvMeasurements[] = 'TAPSE= '.$report['rv_TAPSE'].' mm &nbsp;&nbsp;&nbsp;';
                    }

                    $summaryParts = array_merge($summaryParts, $rvMeasurements);
                    $summaryParts[] = '<br>';

                    // Doppler measurements
                    $summaryParts[] = '<span class="bold">Doppler measurements:</span><br>';

                    $dopplerMeasurements = [];
                    $annulusVal = ! empty($report['av_annulus']) ? $report['av_annulus'] : (! empty($report['AV_annulus']) ? $report['AV_annulus'] : null);
                    if (! empty($annulusVal)) {
                        $dopplerMeasurements[] = 'Annulus= '.$annulusVal.' mm &nbsp;&nbsp;&nbsp;';
                    }

                    $sinusVal = ! empty($report['av_sinus_valsalva']) ? $report['av_sinus_valsalva'] : (! empty($report['Sinus_valsalva']) ? $report['Sinus_valsalva'] : null);
                    if (! empty($sinusVal)) {
                        $dopplerMeasurements[] = 'Sinus_valsalva= '.$sinusVal.' mm &nbsp;&nbsp;&nbsp;';
                    }

                    $stVal = ! empty($report['av_st_junction']) ? $report['av_st_junction'] : (! empty($report['ST_junction']) ? $report['ST_junction'] : null);
                    if (! empty($stVal)) {
                        $dopplerMeasurements[] = 'ST_junction= '.$stVal.' mm &nbsp;&nbsp;&nbsp;';
                    }

                    $ascVal = ! empty($report['av_proximal_ascending_aorta']) ? $report['av_proximal_ascending_aorta'] : (! empty($report['ascending_aorta']) ? $report['ascending_aorta'] : null);
                    if (! empty($ascVal)) {
                        $dopplerMeasurements[] = 'Ascending_aorta= '.$ascVal.' mm &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['septale'])) {
                        $dopplerMeasurements[] = 'septale= '.$report['septale'].' &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['lateral'])) {
                        $dopplerMeasurements[] = 'lateral= '.$report['lateral'].' &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['E_e'])) {
                        $dopplerMeasurements[] = 'E_e= '.$report['E_e'].' &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['septala'])) {
                        $dopplerMeasurements[] = 'septala= '.$report['septala'].' &nbsp;&nbsp;&nbsp;';
                    }
                    if (! empty($report['septal_Sm'])) {
                        $dopplerMeasurements[] = 'septal_Sm= '.$report['septal_Sm'].' &nbsp;&nbsp;&nbsp;';
                    }

                    $summaryParts = array_merge($summaryParts, $dopplerMeasurements);

                    // Left Ventricle section
                    $standardLvOptions = [
                        'normal lv size',
                        'mild lv enlargement',
                        'mild to moderate lv enlargement',
                        'moderate lv enlargement',
                        'moderate to severe lv enlargement',
                        'severe lv enlargement',
                    ];
                    $lvSizeText = isset($report['lv_size']) ? historyDisplayText($report['lv_size'], $dropdownOptions['lv_size']) : '';
                    $isLvManual = ! empty($lvSizeText) && ! in_array(strtolower(trim($lvSizeText)), $standardLvOptions);

                    if ($isLvManual) {
                        $summaryParts[] = '<br><span class="bold">Left ventricle:</span> ';
                    } else {
                        $summaryParts[] = '<br><span class="bold">Left ventricle:</span> there is ';
                    }

                    $lvParts = [];
                    if (! empty($report['lv_size'])) {
                        $size = historyDisplayText($report['lv_size'], $dropdownOptions['lv_size']);
                        if ($size) {
                            if (! empty($report['lv_size_was_auto']) && ! empty($report['lv_size_input_used'])) {
                                $lvParts[] = historyAutoTooltipHtml('lv_size', $report['gender'] ?? '', $report['lv_size_input_used'], $size, $report['lv_size_input_value'] ?? null);
                            } else {
                                $lvParts[] = $size;
                            }
                        }
                    }

                    if (! empty($report['lv_LVEDV'])) {
                        $lvParts[] = '(LVEDV= '.$report['lv_LVEDV'].' mm/m<sup>2</sup>)';
                    }

                    if (! empty($report['lv_Systolic'])) {
                        $systolic = historyDisplayText($report['lv_Systolic'], $dropdownOptions['lv_Systolic']);
                        if ($systolic) {
                            if (! empty($report['lv_Systolic_was_auto']) && ! empty($report['lv_Systolic_input_used'])) {
                                $lvParts[] = historyAutoTooltipHtml('lv_Systolic', $report['gender'] ?? '', $report['lv_Systolic_input_used'], $systolic, $report['lv_Systolic_input_value'] ?? null);
                            } else {
                                $lvParts[] = $systolic;
                            }
                        }
                    }

                    if (! empty($report['lv_LVEF'])) {
                        $lvParts[] = '(LVEF= '.$report['lv_LVEF'].' %)';
                    }

                    if (! empty($report['lv_Diastolic'])) {
                        $diastolic = historyDisplayText($report['lv_Diastolic'], $dropdownOptions['lv_Diastolic']);
                        if ($diastolic) {
                            if (! empty($report['lv_Diastolic_was_auto']) && ! empty($report['lv_Diastolic_input_used'])) {
                                $lvParts[] = 'and '.historyAutoTooltipHtml('lv_Diastolic', $report['gender'] ?? '', $report['lv_Diastolic_input_used'], $diastolic, $report['lv_Diastolic_input_value'] ?? null).'.';
                            } else {
                                $lvParts[] = 'and '.$diastolic.'.';
                            }
                        }
                    }

                    if (! empty($report['lv_Hypertrophy']) && $report['lv_Hypertrophy'] != -1) {
                        if ($report['lv_Hypertrophy'] == 99 && ! empty($report['lv_others'])) {
                            $lvParts[] = 'there is '.$report['lv_others'].',';
                        } else {
                            $hypertrophy = historyDisplayText($report['lv_Hypertrophy'], $dropdownOptions['lv_Hypertrophy']);
                            if ($hypertrophy) {
                                if (! empty($report['lv_Hypertrophy_was_auto']) && ! empty($report['lv_Hypertrophy_input_used'])) {
                                    $lvParts[] = 'there is '.historyAutoTooltipHtml('lv_Hypertrophy', $report['gender'] ?? '', $report['lv_Hypertrophy_input_used'], $hypertrophy, $report['lv_Hypertrophy_input_value'] ?? null).',';
                                } else {
                                    $lvParts[] = 'there is '.$hypertrophy.',';
                                }
                            }
                        }

                        if ($report['lv_Hypertrophy'] == 5 && ! empty($report['lv_PPG'])) {
                            $lvParts[] = 'PPG= '.$report['lv_PPG'];
                        }
                    }

                    if (! empty($report['lv_max_thickness'])) {
                        $lvParts[] = 'thickness= '.$report['lv_max_thickness'].' cm.';
                    }

                    if (! empty($report['lv_rest']) && $report['lv_rest'] == 1) {
                        $lvParts[] = 'there is RWMA at rest';
                    }

                    // Add LV parts to summary
                    $summaryParts[] = implode(' ', $lvParts);

                    // Right Ventricle section
                    $standardRvOptions = [
                        'normal rv size',
                        'mild rv enlargement',
                        'mild to moderate rv enlargement',
                        'moderate rv enlargement',
                        'moderate to severe rv enlargement',
                        'severe rv enlargement',
                    ];
                    $rvSizeText = isset($report['rv_size']) ? historyDisplayText($report['rv_size'], $dropdownOptions['rv_size']) : '';
                    $isRvManual = ! empty($rvSizeText) && ! in_array(strtolower(trim($rvSizeText)), $standardRvOptions);

                    if ($isRvManual) {
                        $summaryParts[] = '<br><span class="bold">Right ventricle:</span> ';
                    } else {
                        $summaryParts[] = '<br><span class="bold">Right ventricle:</span> there is ';
                    }

                    $rvParts = [];
                    if (! empty($report['rv_size'])) {
                        $size = historyDisplayText($report['rv_size'], $dropdownOptions['rv_size']);
                        if ($size) {
                            if (! empty($report['rv_size_was_auto']) && ! empty($report['rv_size_input_used'])) {
                                $rvParts[] = historyAutoTooltipHtml('rv_size', $report['gender'] ?? '', $report['rv_size_input_used'], $size, $report['rv_size_input_value'] ?? null);
                            } else {
                                $rvParts[] = $size;
                            }
                        }
                    }

                    if (! empty($report['rv_Systolic'])) {
                        $systolic = historyDisplayText($report['rv_Systolic'], $dropdownOptions['rv_Systolic']);
                        if ($systolic) {
                            if (! empty($report['rv_Systolic_was_auto']) && ! empty($report['rv_Systolic_input_used'])) {
                                $rvParts[] = 'with '.historyAutoTooltipHtml('rv_Systolic', $report['gender'] ?? '', $report['rv_Systolic_input_used'], $systolic, $report['rv_Systolic_input_value'] ?? null);
                            } else {
                                $rvParts[] = 'with '.$systolic;
                            }
                        }
                    }

                    if (! empty($report['rv_TAPSE'])) {
                        $rvParts[] = 'TAPSE= '.$report['rv_TAPSE'].' cm/s.';
                    }

                    if (! empty($report['rv_Hypertrophy']) && $report['rv_Hypertrophy'] != -1) {
                        if ($report['rv_Hypertrophy'] == 99 && ! empty($report['rv_other_hyper'])) {
                            $rvParts[] = 'there is '.$report['rv_other_hyper'].'.';
                        } else {
                            $hypertrophy = historyDisplayText($report['rv_Hypertrophy'], $dropdownOptions['rv_Hypertrophy']);
                            if ($hypertrophy) {
                                $rvParts[] = 'there is '.$hypertrophy.'.';
                            }
                        }
                    }

                    if (! empty($report['rv_max_thickness'])) {
                        $rvParts[] = 'thickness= '.$report['rv_max_thickness'].' cm.';
                    }

                    // Add RV parts to summary
                    $summaryParts[] = implode(' ', $rvParts);
                    $summaryParts[] = '<br>';

                    if (! empty($report['rv_other_des'])) {
                        $summaryParts[] = '<span class="bold">Descriptions of Right ventricle: </span>'.$report['rv_other_des'].'. <br>';
                    }

                    // Left Atrium section
                    $summaryParts[] = '<span class="bold">Left atrium:</span> there is ';

                    $laParts = [];
                    if (! empty($report['la_size'])) {
                        $size = historyDisplayText($report['la_size'], $dropdownOptions['la_size']);
                        if ($size) {
                            $cleanSize = $size;
                            if (strncasecmp($cleanSize, 'there is ', 9) === 0) {
                                $cleanSize = substr($cleanSize, 9);
                            }
                            if (! empty($report['la_size_was_auto']) && ! empty($report['la_size_input_used'])) {
                                $laParts[] = historyAutoTooltipHtml('la_size', $report['gender'] ?? '', $report['la_size_input_used'], $cleanSize, $report['la_size_input_value'] ?? null).',';
                            } else {
                                $laParts[] = $cleanSize.',';
                            }
                        }
                    }

                    $laMeasurements = [];
                    if (! empty($report['la_area'])) {
                        $laMeasurements[] = 'area= '.$report['la_area'];
                    }
                    if (! empty($report['la_volume'])) {
                        $laMeasurements[] = 'volume= '.$report['la_volume'];
                    }
                    if (! empty($report['la_diameter'])) {
                        $laMeasurements[] = 'diameter= '.$report['la_diameter'];
                    }

                    if (! empty($laMeasurements)) {
                        $laParts[] = '('.implode(', ', $laMeasurements).').';
                    }

                    if (! empty($report['la_smoky'])) {
                        $smoky = historyDisplayText($report['la_smoky'], [0 => 'No', 1 => 'Yes']);
                        if ($smoky === 'Yes') {
                            $laParts[] = 'there is smoky,';
                        }
                    }

                    if (! empty($report['la_clot']) && $report['la_clot'] != -1) {
                        if ($report['la_clot'] == 99 && ! empty($report['la_other_clot'])) {
                            $laParts[] = 'and '.$report['la_other_clot'].'.';
                        } else {
                            $clot = historyDisplayText($report['la_clot'], $dropdownOptions['la_clot']);
                            if ($clot) {
                                $laParts[] = 'and '.$clot.'.';
                            }
                        }
                    }

                    // Add LA parts to summary
                    $summaryParts[] = implode(' ', $laParts);
                    $summaryParts[] = '<br>';

                    // Left Atrium
                    $laParts = [];
                    if (! empty($report['la_size'])) {
                        $sizeOptions = $dropdownOptions['la_size'] ?? [];
                        $size = historyDisplayText($report['la_size'], $sizeOptions);
                        if ($size) {
                            $sizeText = ($size !== $report['la_size']) ? ($size.' size') : ($report['la_size'].' size');
                            if (! empty($report['la_size_was_auto']) && ! empty($report['la_size_input_used'])) {
                                $laParts[] = historyAutoTooltipHtml('la_size', $report['gender'] ?? '', $report['la_size_input_used'], $sizeText, $report['la_size_input_value'] ?? null);
                            } else {
                                $laParts[] = $sizeText;
                            }
                        }
                    }
                    if (! empty($report['la_volume'])) {
                        $laVolume = $report['la_volume'];
                        $laVolumeText = '';
                        if ($laVolume > 48) {
                            $laVolumeText = 'severely dilated';
                        } elseif ($laVolume > 34) {
                            $laVolumeText = 'moderately dilated';
                        } elseif ($laVolume > 28) {
                            $laVolumeText = 'mildly dilated';
                        }
                        if ($laVolumeText) {
                            $laParts[] = $laVolumeText.' (volume '.$laVolume.' ml/m²)';
                        }
                    }
                    if (! empty($laParts)) {
                        $summaryParts[] = '<div class="section-title">Left Atrium</div><div class="section-content">'.
                            ucfirst(implode('; ', $laParts)).'.</div>';
                    }

                    // Valves
                    $valveFindings = [];

                    // Mitral Valve
                    $mvParts = [];
                    if (! empty($report['mv_regurgitant']) && $report['mv_regurgitant'] !== 'None') {
                        $mr = historyDisplayText($report['mv_regurgitant'], $dropdownOptions['mv_regurgitant'] ?? null);
                        if ($mr) {
                            if (stripos($mr, 'MR') !== false) {
                                $mvParts[] = $mr;
                            } else {
                                $mvParts[] = $mr.' MR';
                            }
                        }
                    }
                    if (! empty($report['mv_stenosis1']) && $report['mv_stenosis1'] !== 'None') {
                        $ms = historyDisplayText($report['mv_stenosis1'], $dropdownOptions['mv_stenosis1'] ?? null);
                        if ($ms) {
                            if (stripos($ms, 'MS') !== false) {
                                $mvParts[] = $ms;
                            } else {
                                $mvParts[] = $ms.' MS';
                            }
                        }
                    }
                    if (! empty($mvParts)) {
                        $valveFindings[] = 'Mitral: '.implode('; ', $mvParts);
                    }

                    // Aortic Valve
                    $avParts = [];
                    if (! empty($report['av_regurgitant']) && $report['av_regurgitant'] !== 'None') {
                        $ar = historyDisplayText($report['av_regurgitant'], $dropdownOptions['av_regurgitant'] ?? null);
                        if ($ar) {
                            if (stripos($ar, 'AR') !== false) {
                                $avParts[] = $ar;
                            } else {
                                $avParts[] = $ar.' AR';
                            }
                        }
                    }
                    if (! empty($report['av_stenosis1']) && $report['av_stenosis1'] !== 'None') {
                        $as = historyDisplayText($report['av_stenosis1'], $dropdownOptions['av_stenosis1'] ?? null);
                        if ($as) {
                            if (stripos($as, 'AS') !== false) {
                                $avParts[] = $as;
                            } else {
                                $avParts[] = $as.' AS';
                            }
                        }
                    }
                    if (! empty($report['av_aorta']) && strcasecmp($report['av_aorta'], 'None') !== 0) {
                        $echoAorta = trim($report['av_aorta']);
                        if (strcasecmp($echoAorta, 'Auto') === 0) {
                            $dilatedSegments = $report['av_aorta_dilated_segments'] ?? [];
                            $echoAorta = ! empty($dilatedSegments)
                                ? 'dilated aortic size ('.implode(', ', $dilatedSegments).')'
                                : 'normal aortic size';
                        }
                        $avParts[] = ucfirst($echoAorta);
                    }
                    if (! empty($avParts)) {
                        $valveFindings[] = 'Aortic: '.implode('; ', $avParts);
                    }

                    // Tricuspid Valve
                    if (! empty($report['tv_regurgitant']) && $report['tv_regurgitant'] !== 'None') {
                        $tr = historyDisplayText($report['tv_regurgitant'], $dropdownOptions['tv_regurgitant'] ?? null);
                        if ($tr) {
                            if (stripos($tr, 'TR') !== false) {
                                $valveFindings[] = 'Tricuspid: '.$tr;
                            } else {
                                $valveFindings[] = 'Tricuspid: '.$tr.' TR';
                            }
                        }

                        // Estimate PASP if TR velocity is available
                        if (! empty($report['tv_peak_velocity'])) {
                            $tvr_velocity = $report['tv_peak_velocity'];
                            $pasp = 4 * pow($tvr_velocity, 2) + 5; // Simplified PASP estimation
                            $valveFindings[] = 'Estimated PASP: '.round($pasp).' mmHg';
                        }
                    }

                    // Pulmonary Valve
                    if (! empty($report['pv_regurgitant']) && $report['pv_regurgitant'] !== 'None') {
                        $pr = historyDisplayText($report['pv_regurgitant'], $dropdownOptions['pv_regurgitant'] ?? null);
                        if ($pr) {
                            if (stripos($pr, 'PR') !== false) {
                                $valveFindings[] = 'Pulmonary: '.$pr;
                            } else {
                                $valveFindings[] = 'Pulmonary: '.$pr.' PR';
                            }
                        }
                    }

                    if (! empty($valveFindings)) {
                        $summaryParts[] = '<div class="section-title">Valvular Assessment</div><div class="section-content">'.
                            implode('<br>', $valveFindings).'</div>';
                    }

                    // Pericardium
                    $pericardium = [];
                    if (! empty($report['pericardial_effusion'])) {
                        $effusion = historyDisplayText($report['pericardial_effusion'], $dropdownOptions['pericardial_effusion']);
                        if ($effusion) {
                            $pericardium[] = $effusion.' pericardial effusion';
                        }
                    }
                    if (! empty($report['tamponade']) && $report['tamponade'] == 1) {
                        $pericardium[] = 'signs of cardiac tamponade';
                    }
                    if (! empty($pericardium)) {
                        $summaryParts[] = '<div class="section-title">Pericardium</div><div class="section-content">'.
                            ucfirst(implode('; ', $pericardium)).'.</div>';
                    }

                    // Additional Findings & Conclusion
                    $findings = [];
                    if (! empty($report['findings'])) {
                        $findings[] = nl2br(htmlspecialchars($report['findings']));
                    }
                    if (! empty($report['conclusion'])) {
                        $findings[] = nl2br(htmlspecialchars($report['conclusion']));
                    }
                    if (! empty($findings)) {
                        $summaryParts[] = '<div class="section-title">Findings & Conclusion</div><div class="section-content findings">'.
                            implode('<br><br>', $findings).'</div>';
                    }

                    // Output the summary with proper spacing
                    echo '<div class="echo-summary">'.ReportHtml::clean(implode("\n", $summaryParts)).'</div>';
                    ?>
                    </div>
                </div>
                
                <style>
                .echo-summary {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                }
                .patient-info {
                    font-size: 1.1em;
                    margin-bottom: 10px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #eee;
                }
                .vitals {
                    background-color: #f5f5f5;
                    padding: 8px 12px;
                    border-radius: 4px;
                    margin-bottom: 15px;
                    display: inline-block;
                }
                .section-title {
                    font-weight: bold;
                    color: #2c5282;
                    margin-top: 15px;
                    margin-bottom: 5px;
                    font-size: 1.05em;
                }
                .section-content {
                    margin-left: 15px;
                    margin-bottom: 10px;
                }
                .findings {
                    white-space: pre-line;
                }
                .bold {
                    font-weight: bold;
                }
                .indication {
                    margin: 10px 0;
                    padding: 8px 12px;
                    background-color: #ebf8ff;
                    border-left: 4px solid #4299e1;
                }
                </style>

                <div class="section">
                    <h2>Additional findings</h2>
                    <div class="row">
                        <div class="field" style="flex: 1 1 100%;">
                            <div class="value" style="min-height: 100px;">
                                <?= ! empty($report['finding']) ? nl2br(htmlspecialchars($report['finding'])) : 'No specific findings recorded.' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>Summary</h2>
                    <div class="row">
                        <div class="field" style="flex: 1 1 100%;">
                            <div class="value" style="min-height: 50px;">
                                <?= ! empty($report['summary']) ? nl2br(htmlspecialchars($report['summary'])) : 'No summary provided.' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>Recommendation</h2>
                    <div class="row">
                        <div class="field" style="flex: 1 1 100%;">
                            <div class="value" style="min-height: 50px;">
                                <?= ! empty($report['recommendation']) ? nl2br(htmlspecialchars($report['recommendation'])) : 'No recommendation provided.' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (! empty($report['doctor'])) { ?>
                <div class="section">
                    <h2>Doctor</h2>
                    <div class="row">
                        <div class="field" style="flex: 1 1 100%;">
                            <div class="value">
                                <?= htmlspecialchars($report['doctor']) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <div class="footer">
                    <p>Report generated on <?= date('F j, Y \a\t g:i A') ?></p>
                </div>

                </div>
                <?php } ?>
            <?php } ?>
        <?php } ?>
    </div>

    <script>
    // Print functionality has been moved to view_report.php
    </script>
</body>
</html>
