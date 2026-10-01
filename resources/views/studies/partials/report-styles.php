<?php
/*
 * Styles for the echo report document (partials/report-document.php), scoped to .echo-report so they
 * never leak into the dashboard around it.
 */
?>
<style>

        /* Tooltip Container */
        .echo-report .tooltip-container {
            position: relative;
            display: inline-block;
            cursor: help;
            border-bottom: 1px dotted #0066cc;
            color: #0066cc;
        }
        /* Tooltip Table Box */
        .echo-report .tooltip-table-box {
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

        .echo-report .tooltip-container:hover .tooltip-table-box {
            visibility: visible;
            opacity: 1;
        }

        .echo-report, .echo-report * {
            box-sizing: border-box;
        }

        .echo-report {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.4;
            color: #111;
            direction: ltr;
            text-align: left;
            -webkit-font-smoothing: antialiased;
        }

        .echo-report .report-container {
            width: 100%;
            max-width: 1200px;
            box-sizing: border-box;
            margin: 0 auto;
            background: white;
        }

        .echo-report .section {
            margin-bottom: 24px;
            page-break-inside: avoid;
        }

        .echo-report .section-title {
            font-size: 1.25em;
            font-weight: 700;
            margin: 18px 0 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #0066cc;
            color: #0066cc;
        }

        .echo-report .patient-info-line,
.echo-report .clinical-info-line {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
            padding: 12px;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin: 10px 0;
            box-sizing: border-box;
        }

        .echo-report .patient-info-line {
            direction: rtl;
            gap: 12px 20px;
        }

        .echo-report .info-item,
.echo-report .patient-info-item {
            display: inline-flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 4px;
        }

        .echo-report .label {
            font-weight: 600;
            color: #2c3e50;
            margin-right: 4px;
        }

        .echo-report .value {
            color: #1a365d;
        }

        .echo-report .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 6px 0;
        }

        .echo-report table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            border: 1px solid #e2e8f0;
        }

        .echo-report td {
            padding: 6px 8px;
            border: 1px solid #edf2f7;
        }

        .echo-report .report-summary {
            background-color: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #0066cc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0066cc;
        }

        .echo-report .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #0066cc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .echo-report .header h1 {
            color: #0066cc;
            margin: 0;
            font-size: 1.6em;
            flex-grow: 1;
            text-align: center;
        }

        .echo-report .header-logo {
            height: 76px;
            max-width: 120px;
            width: auto;
            object-fit: contain;
            flex-shrink: 0;
        }

        @media (max-width: 640px) {
            .echo-report .header {
                flex-direction: column;
                gap: 10px;
            }
            .echo-report .header h1 {
                font-size: 1.25em !important;
                padding: 0 !important;
            }
            .echo-report .header-logo {
                height: 52px;
            }
        }

        .echo-report u {
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .echo-report .report-actions {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .echo-report .btn-print {
            padding: 10px 24px;
            min-height: 44px;
            font-size: 14px;
            font-weight: 600;
            background: #0066cc;
            color: #fff;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .echo-report .btn-print:hover {
            background: #0052a3;
        }

        .echo-report .btn-back {
            padding: 10px 20px;
            min-height: 44px;
            font-size: 14px;
            font-weight: 600;
            background: #e2e8f0;
            color: #4a5568;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .echo-report .btn-back:hover {
            background: #cbd5e0;
        }

        @media (max-width: 640px) {
            .echo-report .report-actions {
                flex-direction: column;
            }
            .echo-report .btn-print,
.echo-report .btn-back {
                width: 100%;
            }
        }

        @media print {
            .echo-report { padding: 0; font-size: 12pt; }
            .echo-report .report-container { box-shadow: none; padding: 0; max-width: 100%; }
            .echo-report .no-print { display: none !important; }
            .echo-report .clinical-info-line,
.echo-report .patient-info-line { border: 1px solid #d1d5db; background-color: #fff !important; -webkit-print-color-adjust: exact; }
            .echo-report .tooltip-container { border-bottom: none !important; color: inherit !important; cursor: default !important; }
            .echo-report u .tooltip-container { text-decoration: underline !important; }
            .echo-report .header { flex-direction: row !important; }
            .echo-report .header-logo { height: 66px !important; }
            .echo-report u {
                text-decoration: underline !important;
                -webkit-text-decoration: underline !important;
                text-underline-offset: 2px;
            }
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 10mm;
            }
            .echo-report {
                margin: 0;
                padding: 0;
                font-size: 10.5pt;
                line-height: 1.35;
            }
            .echo-report .report-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                width: 100%;
                zoom: 1.2;
            }
            .echo-report .report-summary {
                padding: 10px !important;
                margin-top: 0 !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .echo-report .section {
                margin-bottom: 8px !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .echo-report .section-title {
                margin: 8px 0 4px !important;
                font-size: 1.15em !important;
                padding-bottom: 2px !important;
            }
            .echo-report .header {
                margin-bottom: 8px !important;
                padding-bottom: 4px !important;
            }
            .echo-report table {
                font-size: 11.5px !important;
            }
            .echo-report td {
                padding: 3px 5px !important;
            }
            .echo-report .patient-info-line,
.echo-report .clinical-info-line {
                margin: 4px 0 !important;
                padding: 5px !important;
                gap: 8px !important;
            }
            .echo-report .no-print {
                display: none !important;
            }
            .echo-report .container {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .echo-report u {
                text-decoration: underline !important;
                -webkit-text-decoration: underline !important;
                text-underline-offset: 2px;
            }
        }
</style>
