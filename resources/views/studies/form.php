<?php

use App\Models\EchoStudy;

/**
 * Echo study form, ported from the legacy form.php.
 *
 * @var EchoStudy|null $study  Set when editing a saved study.
 */
$study ??= null;
$editing = $study !== null;
$amending = $editing && $study->isSigned();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>" />
  <title>Echo Form — <?= e(auth()->user()->hospital->name) ?></title>
  <link type="text/css" rel="stylesheet" href="/legacy/fonts.css" />
  <link type="text/css" rel="stylesheet" href="/legacy/navbar.css" />
  <link type="text/css" rel="stylesheet" href="/legacy/persianDatepicker/css/persianDatepicker-default.css" />
  <script type="text/javascript" src="/legacy/persianDatepicker/js/jquery-1.10.1.min.js"></script>
  <script type="text/javascript" src="/legacy/persianDatepicker/js/persianDatepicker.min.js"></script>
  <style>
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
      font-size: 20px;
      margin-bottom: 12px;
      color: #1a202c;
    }

    form#echoForm {
      width: 100%;
      max-width: 1200px;
      box-sizing: border-box;
      margin: 0 auto;
      background: #fff;
      padding: 14px;
      border-radius: 12px;
      box-shadow: 0 4px 18px rgba(0, 0, 0, .05);
    }

    @media (min-width: 768px) {
      form#echoForm {
        padding: 20px;
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

    input[type="text"],
    input[type="number"],
    input[type="time"],
    textarea,
    select,
    input[type="date"] {
      padding: 10px 12px;
      border: 1px solid #d6d9e6;
      border-radius: 8px;
      font-size: 15px;
      background: #fff;
      min-height: 44px;
      box-sizing: border-box;
      width: 100%;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    @media (max-width: 640px) {
      input[type="text"],
      input[type="number"],
      input[type="time"],
      textarea,
      select,
      input[type="date"] {
        font-size: 16px; /* Prevents auto-zoom on iOS */
      }
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    input[type="time"]:focus,
    textarea:focus,
    select:focus,
    input[type="date"]:focus {
      outline: none;
      border-color: #3182ce;
      box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
    }

    textarea {
      min-height: 90px;
      resize: vertical;
      line-height: 1.5;
    }

    details {
      margin: 12px 0;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #fbfbff;
      padding: 10px 12px;
    }

    summary {
      font-size: 14px;
      font-weight: 600;
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

    .controls {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 20px;
      width: 100%;
    }

    @media (max-width: 640px) {
      .controls {
        flex-direction: column-reverse;
      }
      .controls button {
        width: 100%;
      }
    }

    button:not(.voice-mic-btn):not(.section-nav-btn):not(.patient-tab-close):not(.modal-btn):not(.navbar-hamburger) {
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
      transition: background-color 0.2s, box-shadow 0.2s;
    }

    button:not(.voice-mic-btn):not(.section-nav-btn):not(.patient-tab-close):not(.modal-btn):not(.navbar-hamburger):hover {
      background: #2c5282;
      box-shadow: 0 2px 6px rgba(43, 108, 176, 0.25);
    }

    .hidden {
      display: none !important;
    }

    /* Section Navigation Bar Styles */
    .section-nav-wrapper {
      margin: 16px 0;
      border-bottom: 2px solid #e2e8f0;
      padding-bottom: 12px;
      width: 100%;
    }

    .section-nav-container {
      display: flex;
      gap: 8px;
      width: 100%;
      overflow-x: auto;
      padding: 4px 2px 8px 2px;
      scrollbar-width: thin;
      scrollbar-color: #cbd5e0 #f7fafc;
      -webkit-overflow-scrolling: touch;
    }

    .section-nav-container::-webkit-scrollbar {
      height: 6px;
    }

    .section-nav-container::-webkit-scrollbar-track {
      background: #f7fafc;
      border-radius: 4px;
    }

    .section-nav-container::-webkit-scrollbar-thumb {
      background-color: #cbd5e0;
      border-radius: 4px;
    }

    .section-nav-btn {
      flex: 1 0 auto;
      min-width: 72px;
      min-height: 52px;
      padding: 8px 10px;
      height: auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #ffffff;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
      user-select: none;
    }

    .section-nav-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.06);
    }

    /* Unfilled (Red) State */
    .section-nav-btn.status-unfilled {
      background: #fff5f5;
      border-color: #feb2b2;
      color: #c53030;
    }

    .section-nav-btn.status-unfilled:hover {
      border-color: #fc8181;
      background: #fff0f0;
    }

    /* Filled (Green) State */
    .section-nav-btn.status-filled {
      background: #f0fff4;
      border-color: #9ae6b4;
      color: #22543d;
    }

    .section-nav-btn.status-filled:hover {
      border-color: #68d391;
      background: #e6fffa;
    }

    /* Selected (Blue) State - takes precedence */
    .section-nav-btn.active {
      background: #ebf8ff !important;
      border-color: #3182ce !important;
      color: #2b6cb0 !important;
      box-shadow: 0 4px 12px rgba(49, 130, 206, 0.25) !important;
      transform: scale(1.03) translateY(-2px);
    }

    .section-nav-btn .btn-abbr {
      font-size: 14px;
      font-weight: 700;
    }

    /* Persian Datepicker Mobile Viewport Optimization */
    .datepicker-plot-area {
      max-width: calc(100vw - 20px) !important;
      font-family: inherit !important;
      z-index: 100000 !important;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
      border-radius: 8px !important;
    }

    /* Multi-Patient Toolbar Styles */
    .patient-bar-wrapper {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 8px 12px;
      margin: 0 auto 16px auto;
      max-width: 1200px;
      width: 100%;
      box-sizing: border-box;
    }

    .patient-tabs-container {
      display: flex;
      gap: 6px;
      overflow-x: auto;
      flex: 1 1 auto;
      min-width: 0;
      padding-bottom: 2px;
      -webkit-overflow-scrolling: touch;
    }

    .patient-tab {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #ffffff;
      border: 1px solid #cbd5e0;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 13px;
      font-weight: 550;
      color: #4a5568;
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.2s;
      user-select: none;
      min-height: 40px;
    }

    .patient-tab:hover {
      background: #edf2f7;
      color: #2d3748;
    }

    .patient-tab.active {
      background: #3182ce;
      border-color: #3182ce;
      color: #ffffff;
      font-weight: 600;
      box-shadow: 0 2px 6px rgba(49, 130, 206, 0.25);
    }

    .patient-tab-title {
      cursor: pointer;
    }

    .patient-tab-close {
      background: transparent;
      border: 0;
      color: currentColor;
      font-size: 16px;
      line-height: 1;
      padding: 4px;
      cursor: pointer;
      opacity: 0.75;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 20px;
      min-height: 20px;
    }

    .patient-tab-close:hover {
      opacity: 1;
      background: rgba(0,0,0,0.15);
    }

    .add-patient-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #2b6cb0;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 8px 14px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
      transition: background-color 0.2s;
      min-height: 40px;
      flex-shrink: 0;
    }

    .add-patient-btn:hover {
      background: #2c5282;
    }

    /* Form Section Wrapper Styles */
    .form-section {
      display: none;
      margin: 15px 0;
      border: 1px solid #e3e6f0;
      border-radius: 10px;
      background: #fbfbff;
      padding: 16px;
      animation: tabFadeIn 0.3s ease-in-out;
    }

    @media (max-width: 640px) {
      .form-section {
        padding: 12px 10px;
      }
    }

    .form-section.active {
      display: block;
    }

    .form-section-header {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 14px;
      color: #2b6cb0;
      border-bottom: 1.5px solid #e2e8f0;
      padding-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    @keyframes tabFadeIn {
      from {
        opacity: 0;
        transform: translateY(6px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .valve-status-field {
      flex: 1 1 calc(33.333% - var(--gap));
      min-width: 240px;
    }

    @media (max-width: 768px) {
      .valve-status-field {
        flex: 1 1 100%;
        min-width: 100%;
      }
    }

    /* Reusable Custom Modal Dialog Styling */
    .custom-modal {
      border: none;
      border-radius: 16px;
      padding: 0;
      max-width: min(480px, calc(100vw - 32px));
      width: calc(100vw - 32px);
      max-height: 88vh;
      overflow-y: auto;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      direction: rtl;
      text-align: right;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      opacity: 0;
      transform: scale(0.95);
      transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
      margin: auto;
    }

    .custom-modal[open] {
      opacity: 1;
      transform: scale(1);
    }

    .custom-modal::backdrop {
      background: rgba(15, 23, 42, 0.3);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .custom-modal[open]::backdrop {
      opacity: 1;
    }

    .modal-content {
      padding: 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
    }

    .modal-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 4px;
    }

    .modal-icon.success {
      background-color: #ecfdf5;
      color: #10b981;
    }

    .modal-icon.success::after {
      content: "✓";
    }

    .modal-icon.error {
      background-color: #fef2f2;
      color: #ef4444;
    }

    .modal-icon.error::after {
      content: "✕";
    }

    .modal-icon.warning {
      background-color: #fffbeb;
      color: #f59e0b;
    }

    .modal-icon.warning::after {
      content: "!";
    }

    .modal-title {
      font-size: 18px;
      font-weight: 700;
      color: #1e293b;
      margin: 0;
      text-align: center;
      width: 100%;
    }

    .modal-message {
      font-size: 14px;
      color: #475569;
      line-height: 1.6;
      text-align: center;
      margin: 0;
      width: 100%;
    }

    .modal-message ul {
      text-align: right;
      margin: 8px 0 0 0;
      padding-right: 24px;
      list-style-type: disc;
    }

    .modal-message li {
      margin-bottom: 4px;
    }

    .modal-actions {
      display: flex;
      justify-content: center;
      width: 100%;
      margin-top: 8px;
    }

    .modal-btn {
      background-color: #3b82f6;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 24px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s, transform 0.1s;
      width: 100%;
      text-align: center;
      min-height: 44px;
    }

    .modal-btn:hover {
      background-color: #2563eb;
      transform: translateY(-1px);
    }

    .modal-btn:active {
      transform: translateY(0);
    }

    .modal-btn.success-btn {
      background-color: #10b981;
    }

    .modal-btn.success-btn:hover {
      background-color: #059669;
    }

    .modal-btn.error-btn {
      background-color: #ef4444;
    }

    .modal-btn.error-btn:hover {
      background-color: #dc2626;
    }

    .modal-btn.warning-btn {
      background-color: #f59e0b;
    }

    .modal-btn.warning-btn:hover {
      background-color: #d97706;
    }

    /* Dragging overlay to prevent iframe interception */
    body.dragging iframe {
      pointer-events: none !important;
    }

    body.dragging #form-container,
    body.dragging #preview-container {
      transition: none !important;
    }

    #preview-resizer {
      width: 10px;
      cursor: col-resize;
      background: #e2e8f0;
      border-left: 1px solid #cbd5e0;
      border-right: 1px solid #cbd5e0;
      position: relative;
      z-index: 101;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s, border-color 0.2s;
      user-select: none;
    }

    #preview-resizer:hover, #preview-resizer.dragging {
      background: #cbd5e0;
      border-color: #a0aec0;
    }

    #preview-resizer::after {
      content: '||';
      color: #a0aec0;
      font-size: 10px;
      font-weight: bold;
      letter-spacing: -1px;
    }

    #preview-resizer.hidden {
      display: none !important;
    }

    /* Live Preview Layout Styles */
    #app-layout {
      display: flex;
      width: 100%;
      min-height: 100vh;
      transition: all 0.3s ease;
    }

    #form-container {
      flex: 1;
      width: 100%;
      min-width: 0;
      transition: all 0.3s ease;
    }

    /* When preview is active */
    #app-layout.preview-active #form-container {
      flex: 0 0 50%;
      height: 100vh;
      overflow-y: auto;
      border-right: 1px solid #e2e8f0;
      box-shadow: inset -5px 0 10px rgba(0,0,0,0.02);
    }

    #preview-container {
      flex: 0 0 50%;
      height: 100vh;
      display: flex;
      flex-direction: column;
      background: #f7fafc;
      border-left: 2px solid #cbd5e0;
      box-shadow: -4px 0 16px rgba(0,0,0,0.05);
      position: sticky;
      top: 0;
      z-index: 100;
      transition: all 0.3s ease;
    }

    #preview-container.hidden {
      display: none !important;
    }

    #preview-iframe {
      flex: 1;
      width: 100%;
      height: 100%;
      border: 0;
      background: #fff;
    }

    .preview-header {
      background: #2d3748;
      color: #fff;
      padding: 10px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #1a202c;
      user-select: none;
    }

    .preview-title-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .preview-icon {
      color: #90cdf4;
    }

    .preview-title {
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .preview-status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(72, 187, 120, 0.2);
      color: #48bb78;
      padding: 2px 8px;
      border-radius: 9999px;
      font-size: 11px;
      font-weight: 600;
    }

    .preview-status-dot {
      width: 6px;
      height: 6px;
      background-color: #48bb78;
      border-radius: 50%;
      display: inline-block;
      animation: pulse-dot 1.8s infinite ease-in-out;
    }

    @keyframes pulse-dot {
      0% { transform: scale(0.85); opacity: 0.5; }
      50% { transform: scale(1.15); opacity: 1; }
      100% { transform: scale(0.85); opacity: 0.5; }
    }

    .preview-actions {
      display: flex;
      gap: 6px;
    }

    .preview-btn {
      background: transparent;
      border: 0;
      color: #a0aec0;
      padding: 6px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }

    .preview-btn:hover {
      background: #4a5568;
      color: #fff;
    }

    .preview-btn.close-btn:hover {
      background: #e53e3e;
      color: #fff;
    }

    /* Print styles to hide preview container on page print */
    @media print {
      #preview-container {
        display: none !important;
      }
      #app-layout.preview-active #form-container {
        flex: 0 0 100% !important;
        height: auto !important;
        overflow: visible !important;
      }
    }

    /* Tablet and Mobile responsiveness for preview container */
    @media (max-width: 1024px) {
      #app-layout.preview-active {
        flex-direction: column;
      }
      #app-layout.preview-active #form-container {
        flex: 0 0 auto !important;
        height: auto !important;
        overflow: visible !important;
      }
      #app-layout.preview-active #preview-container {
        flex: 0 0 50vh !important;
        width: 100% !important;
        height: 50vh !important;
        position: relative !important;
        border-left: 0;
        border-top: 2px solid #cbd5e0;
      }
    }
  </style>
</head>

<body>
  <?= view('clinical.navbar')->render() ?>
  <div id="app-layout">
    <div id="form-container">
      <!-- Multi-Patient Tab Bar Toolbar -->
      <div class="patient-bar-wrapper"<?= $editing ? ' style="display:none"' : '' ?>>
        <div id="patientTabsContainer" class="patient-tabs-container"></div>
        <button type="button" class="add-patient-btn" onclick="addNewPatientTab()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>New Patient</span>
        </button>
      </div>

      <form id="echoForm" autocomplete="off">
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>" />
      <?php if ($editing) { ?>
      <div class="row" style="background:<?= $amending ? '#fff7ed' : '#ebf8ff' ?>;border:1px solid <?= $amending ? '#fbd38d' : '#bee3f8' ?>;border-radius:8px;padding:10px 14px;margin-bottom:12px">
        <div class="field" style="flex:1 1 100%">
          <strong>
            <?= $amending ? 'Amending signed report' : 'Editing draft' ?> #<?= e($study->id) ?>
            — <?= e($study->patient->first_name.' '.$study->patient->last_name) ?>
          </strong>
          <a href="<?= e(route('studies.show', $study)) ?>" style="margin-left:12px">Cancel</a>
        </div>
        <?php if ($amending) { ?>
        <div class="field" style="flex:1 1 100%">
          <label for="amendment_reason">Reason for amendment <span style="color:red">*</span></label>
          <textarea id="amendment_reason" name="amendment_reason" class="no-voice" required style="min-height:40px"></textarea>
        </div>
        <?php } ?>
      </div>
      <?php } ?>

    <div class="row">
      <div class="field">
        <label for="first_name">First Name <span style="color:red">*</span></label>
        <input id="first_name" name="first_name" type="text" required />
      </div>

      <div class="field">
        <label for="last_name">Last Name <span style="color:red">*</span></label>
        <input id="last_name" name="last_name" type="text" required />
      </div>

      <div class="field">
        <label for="nationalID">National ID <span style="color:red">*</span></label>
        <input id="nationalID" name="nationalID" type="text" required />
      </div>
      
      <div class="field">
        <label for="dob">Date of Birth</label>
        <input id="dob" name="dob" type="text" class="no-voice" />
        <span id="dob-display"></span>
      </div>


      <div class="field">
        <label for="phone_number">Phone Number</label>
        <input id="phone_number" name="phone_number" type="text" />
      </div>

      <div class="field">
        <label for="gender">Gender</label>
        <select id="gender" name="gender">
          <option value="">Select gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
      </div>

      <div class="field">
        <label for="Echo_indication">Echo indication</label>
        <select id="Echo_indication" name="Echo_indication">
          <option value="None">None</option>
          <option value="Chest pain">Chest pain</option>
          <option value="Dyspnea">Dyspnea</option>
          <option value="Palpitation">Palpitation</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="field">
        <label for="echo_date">Date of Echo</label>
        <input id="echo_date" name="echo_date" type="text" class="no-voice" />
        <span id="echo_date-display"></span>
      </div>
      <div class="field hidden" id="other_Echo_indication_wrap">
        <label for="other_Echo_indication">other Echo indication</label>
        <input id="other_Echo_indication" name="other_Echo_indication" type="text" />
      </div>

      <div class="field" style="flex:1 1 100%">
        <label for="History_diseases">History of diseases</label>
        <textarea id="History_diseases" name="History_diseases" style="min-height: 40px;"></textarea>
      </div>
    </div>



    <!-- Section Navigation Bar -->
    <div class="section-nav-wrapper">
      <div class="section-nav-container">
        <button type="button" class="section-nav-btn active" data-target="sec-patient-info" title="Patient information">
          <span class="btn-abbr">Info</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-lv" title="Left Ventricle">
          <span class="btn-abbr">LV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-rv" title="Right Ventricle">
          <span class="btn-abbr">RV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-la" title="Left Atrium">
          <span class="btn-abbr">LA</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-ra" title="Right Atrium">
          <span class="btn-abbr">RA</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-mv" title="Mitral Valve">
          <span class="btn-abbr">MV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-av" title="Aortic Valve">
          <span class="btn-abbr">AV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-pv" title="Pulmonary Valve">
          <span class="btn-abbr">PV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-tv" title="Tricuspid Valve">
          <span class="btn-abbr">TV</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-ivc" title="Inferior vena cava & PAP">
          <span class="btn-abbr">IVC/PAP</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-peri" title="Pericardium">
          <span class="btn-abbr">Peri</span>
        </button>
        <button type="button" class="section-nav-btn" data-target="sec-notes" title="Notes">
          <span class="btn-abbr">Notes</span>
        </button>
      </div>
    </div>

    <div id="sec-patient-info" class="form-section active">
      <div class="form-section-header">Patient information</div>
      <div class="row" style="margin-top:8px">
        <div class="field"><label for="BP">BP (mmHg)</label><input id="BP" name="BP" type="text" />
        </div>
        <div class="field"><label for="HR">HR (beat/min)</label><input id="HR" name="HR" type="number" step="1" /></div>
        <div class="field"><label for="Height">Height (cm)</label><input id="Height" name="Height" type="number" />
        </div>

        <div class="field">
          <label for="Rhythm">Rhythm</label>
          <select id="Rhythm" name="Rhythm">
            <option value="None">None</option>
            <option value="Sinus">Sinus</option>
            <option value="AF">AF</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="field hidden" id="other_Rhythm_wrap">
          <label for="other_Rhythm">other Rhythm</label>
          <input id="other_Rhythm" name="other_Rhythm" type="text" />
        </div>

        <div class="field"><label for="O2_sat">O2 sat (%)</label><input id="O2_sat" name="O2_sat" type="number"
            step="1" /></div>
        <div class="field"><label for="Weight">Weight (kg)</label><input id="Weight" name="Weight" type="number"
            step="1" /></div>
        <div class="field">
          <label for="Echo_type">Echo type</label>
          <select id="Echo_type" name="Echo_type">
            <option value="None">None</option>
            <option value="Transthoracic echocardiography (TTE)">Transthoracic echocardiography (TTE)</option>
            <option value="Transesophageal echocardiography (TEE)">Transesophageal echocardiography (TEE)</option>
            <option value="Stress echocardiography">Stress echocardiography</option>
            <option value="Contrast echocardiography">Contrast echocardiography</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="field hidden" id="other_Echo_type_wrap">
          <label for="other_Echo_type">Other Echo type</label>
          <input id="other_Echo_type" name="other_Echo_type" type="text" />
        </div>
      </div>
    </div>

    <!-- Left ventricle -->
    <div id="sec-lv" class="form-section">
      <div class="form-section-header">Left Ventricle</div>
      <div class="row" style="margin-top:8px">
        <div class="field"><label for="lv_LVEDV">LVEDV (cc)</label><input id="lv_LVEDV" name="lv_LVEDV" type="number"
            step="0.1" /></div>
        <div class="field"><label for="lv_LVESV">LVESV (cc)</label><input id="lv_LVESV" name="lv_LVESV" type="number"
            step="0.1" /></div>
        <div class="field"><label for="lv_LVEDD">LVEDD (mm)</label><input id="lv_LVEDD" name="lv_LVEDD" type="number"
            step="0.1" /></div>
        <div class="field"><label for="lv_LVESD">LVESD (mm)</label><input id="lv_LVESD" name="lv_LVESD" type="number"
            step="0.1" /></div>
        <div class="field"><label for="lv_IVS_thickness">IVS thickness (mm)</label><input id="lv_IVS_thickness"
            name="lv_IVS_thickness" type="number" step="0.1" /></div>
        <div class="field"><label for="lv_Posterior">Posterior wall (mm)</label><input id="lv_Posterior"
            name="lv_Posterior" type="number" step="0.1" /></div>
        <div class="field"><label for="lv_EF">EF (%)</label><input id="lv_EF" name="lv_EF" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="lv_size">LV Size</label>
          <select id="lv_size" name="lv_size">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal LV size">Normal LV size</option>
            <option value="Mild LV enlargement">Mild LV enlargement</option>
            <option value="Mild to moderate LV enlargement">Mild to moderate LV enlargement</option>
            <option value="Moderate LV enlargement">Moderate LV enlargement</option>
            <option value="Moderate to severe LV enlargement">Moderate to severe LV enlargement</option>
            <option value="Severe LV enlargement">Severe LV enlargement</option>
            <option value="Manual">Manual</option>
          </select>
        </div>
        <div class="field">
          <label for="lv_Systolic">Systolic Function</label>
          <select id="lv_Systolic" name="lv_Systolic">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="With normal systolic function">With normal systolic function</option>
            <option value="With mild systolic dysfunction">With mild systolic dysfunction</option>
            <option value="With mild to moderate systolic dysfunction">With mild to moderate systolic dysfunction
            </option>
            <option value="With moderate systolic dysfunction">With moderate systolic dysfunction</option>
            <option value="With moderate to severe systolic dysfunction">With moderate to severe systolic dysfunction
            </option>
            <option value="With severe systolic dysfunction">With severe systolic dysfunction</option>
          </select>
        </div>
        <div class="field">
          <label for="lv_Diastolic">Diastolic Function</label>
          <select id="lv_Diastolic" name="lv_Diastolic">
            <option value="Auto">Auto</option>
            <option value="None" selected>None</option>
            <option value="Normal diastolic function">Normal diastolic function</option>
            <option value="Grade I diastolic dysfunction">Grade I diastolic dysfunction</option>
            <option value="Grade II diastolic dysfunction">Grade II diastolic dysfunction</option>
            <option value="Grade III diastolic dysfunction">Grade III diastolic dysfunction</option>
            <option value="Indeterminate diastolic function">Indeterminate diastolic function</option>
          </select>
        </div>
        <div class="field">
          <label for="lv_Hypertrophy">Hypertrophy</label>
          <select id="lv_Hypertrophy" name="lv_Hypertrophy">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="No LVH">No LVH</option>
            <option value="Mild concentric LVH">Mild concentric LVH</option>
            <option value="Moderate concentric LVH">Moderate concentric LVH</option>
            <option value="Severe concentric LVH">Severe concentric LVH</option>
            <option value="Asymmetric LVH with SAM and LVOT obstruction">Asymmetric LVH with SAM and LVOT obstruction
            </option>
            <option value="Asymmetric LVH without LVOT obstruction">Asymmetric LVH without LVOT obstruction</option>
          </select>
        </div>
        <div class="field">
          <label for="lv_clot">LV Clot</label>
          <select id="lv_clot" name="lv_clot">
            <option value="None" selected>None</option>
            <option value="No LV clot">No LV clot</option>
            <option value="Small mobile LV clot">Small mobile LV clot</option>
            <option value="Large mobile LV clot">Large mobile LV clot</option>
            <option value="Small organized LV clot">Small organized LV clot</option>
            <option value="Large organized LV clot">Large organized LV clot</option>
          </select>
        </div>
        <div class="field hidden" id="PPG_wrap">
          <label for="PPG">PPG (mmHg)</label>
          <input id="PPG" name="PPG" type="number" step="1" />
        </div>
      </div>

      <div class="row">
        <div class="field" id="lv_manual_row" style="display: none;">
          <label for="lv_size_manual">Manual LV Size Description</label>
          <input type="text" id="lv_size_manual" name="lv_size_manual" />
        </div>
      </div>

      <details>
        <summary>Tissue Doppler Imaging (TDI)</summary>
        <div class="row" style="margin-top:8px">
          <div class="field"><label for="lv_septale">Septal e' (cm/s)</label><input id="lv_septale" name="lv_septale"
              type="number" step="0.1" /></div>
          <div class="field"><label for="lv_lateral">Lateral e' (cm/s)</label><input id="lv_lateral" name="lv_lateral"
              type="number" step="0.1" /></div>
        </div>
        <div class="row">
          <div class="field"><label for="lv_septala">Septal a' (cm/s)</label><input id="lv_septala" name="lv_septala"
              type="number" step="0.1" /></div>
          <div class="field"><label for="lv_septal_Sm">Septal S' (cm/s)</label><input id="lv_septal_Sm"
              name="lv_septal_Sm" type="number" step="0.1" /></div>
        </div>
      </details>
      <details>
        <summary>LV Wall Motion</summary>
        <div style="margin-top:8px">
          <div style="display: flex; flex-direction: row; align-items: center; gap: 20px; margin-bottom: 10px; flex-wrap: wrap;">
            <label for="lv_rwma_checked" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0; user-select: none;">
              <input type="checkbox" id="lv_rwma_checked" name="lv_rwma_checked" value="1" style="margin: 0; width: auto;" />
              <span>Include in report</span>
            </label>
            <label for="lv_global_hypokinesia" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0; user-select: none;">
              <input type="checkbox" id="lv_global_hypokinesia" name="lv_global_hypokinesia" value="1" style="margin: 0; width: auto;" />
              <span>Global hypokinesia</span>
            </label>
          </div>
          <div class="small">Select status for each segment (Normal / Mild hypokinesia / Severe hypokinesia / Akinesia /
            Dyskinesia / Aneurysm)</div>
          <div id="rwma-segments"></div>

          <div class="row" style="margin-top:8px">
            <div class="field"><label for="lv_with_preserved">with</label>
              <select id="lv_with_preserved" name="lv_with">
                <option value="none">none</option>
                <option value="1">preserved tissue thickness</option>
                <option value="2">thinning and loss of tissue</option>
              </select>
            </div>
            <div class="field hidden" id="other_clot_wrap_lv"><label for="other_clot_lv">other clot</label><input
                id="lv_other_clot" name="lv_other_clot" /></div>
          </div>

        </div>
      </details>

      <div class="row" style="margin-top:8px">
        <div class="field" style="flex:1 1 100%"><label for="other_des_lv">Other descriptions</label><textarea
            id="other_des_lv" name="other_des_lv"></textarea></div>
      </div>
    </div>

    <!-- Right Ventricle -->
    <div id="sec-rv" class="form-section">
      <div class="form-section-header">Right Ventricle</div>
      <div class="row" style="margin-top:8px">
        <div class="field"><label for="rv_RV_basal_diameter">RV Basal Diameter (mm)</label><input
            id="rv_RV_basal_diameter" name="rv_RV_basal_diameter" type="number" step="0.1" /></div>
        <div class="field"><label for="rv_RV_mid_diameter">RV Mid Diameter (mm)</label><input id="rv_RV_mid_diameter"
            name="rv_RV_mid_diameter" type="number" step="0.1" /></div>
        <div class="field"><label for="rv_RV_longitudinal_diameter">RV Longitudinal Diameter (mm)</label><input
            id="rv_RV_longitudinal_diameter" name="rv_RV_longitudinal_diameter" type="number" step="0.1" /></div>

        <div class="field"><label for="rv_TAPSE">TAPSE (mm)</label><input id="rv_TAPSE" name="rv_TAPSE" type="number"
            step="0.1" /></div>
        <div class="field"><label for="rv_S_velocity">S' Velocity (cm/s)</label><input id="rv_S_velocity"
            name="rv_S_velocity" type="number" step="0.1" /></div>

        <div class="field">
          <label for="rv_size">RV Size</label>
          <select id="rv_size" name="rv_size">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal RV size">Normal RV size</option>
            <option value="Mild RV enlargement">Mild RV enlargement</option>
            <option value="Mild to moderate RV enlargement">Mild to moderate RV enlargement</option>
            <option value="Moderate RV enlargement">Moderate RV enlargement</option>
            <option value="Moderate to severe RV enlargement">Moderate to severe RV enlargement</option>
            <option value="Severe RV enlargement">Severe RV enlargement</option>
            <option value="Manual">Manual</option>
          </select>
        </div>

        <div class="field">
          <label for="rv_systolic">Systolic Function</label>
          <select id="rv_Systolic" name="rv_Systolic">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="With normal systolic function">With normal systolic function</option>
            <option value="With mild systolic dysfunction">With mild systolic dysfunction</option>
            <option value="With mild to moderate systolic dysfunction">With mild to moderate systolic dysfunction
            </option>
            <option value="With moderate systolic dysfunction">With moderate systolic dysfunction</option>
            <option value="With moderate to severe systolic dysfunction">With moderate to severe systolic dysfunction
            </option>
            <option value="With severe systolic dysfunction">With severe systolic dysfunction</option>
          </select>
        </div>

        <div class="field">
          <label for="rv_Hypertrophy">Hypertrophy</label>
          <select id="rv_Hypertrophy" name="rv_Hypertrophy">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="No RVH">No RVH</option>
            <option value="Mild RVH">Mild RVH</option>
            <option value="Moderate RVH">Moderate RVH</option>
            <option value="Severe RVH">Severe RVH</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="field" id="rv_manual_row" style="display: none;">
          <label for="rv_size_manual">Manual RV Size Description</label>
          <input type="text" id="rv_size_manual" name="rv_size_manual" />
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="rv_other_des">Other Descriptions</label>
          <textarea id="rv_other_des" name="rv_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Left Atrium -->
    <div id="sec-la" class="form-section">
      <div class="form-section-header">Left Atrium</div>
      <div class="row" style="margin-top:8px">
        <div class="field">
          <label for="la_size">LA Size</label>
          <select id="la_size" name="la_size">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal LA size">Normal LA size</option>
            <option value="Mild LA enlargement">Mild LA enlargement</option>
            <option value="Moderate LA enlargement">Moderate LA enlargement</option>
            <option value="Severe LA enlargement">Severe LA enlargement</option>
          </select>
        </div>
        <div class="field"><label for="la_area">LA Area (cm²)</label><input id="la_area" name="la_area" type="number"
            step="0.1" /></div>
        <div class="field"><label for="la_volume">LA Volume (cc)</label><input id="la_volume" name="la_volume"
            type="number" step="0.1" /></div>


        <div class="field"><label for="la_diameter">LA Diameter (mm)</label><input id="la_diameter" name="la_diameter"
            type="number" step="0.1" /></div>
        <div class="field">
          <label for="la_smoky">Smoky Appearance</label>
          <select id="la_smoky" name="la_smoky">
            <option value="-1">None</option>
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>

        <div class="field">
          <label for="la_clot">LA Clot</label>
          <select id="la_clot" name="la_clot">
            <option value="-1">None</option>
            <option value="0">No LA clot</option>
            <option value="1">LA clot present</option>
          </select>
        </div>
        <div class="field" style="flex:1 1 100%">
          <label for="la_other_des">Other Descriptions</label>
          <textarea id="la_other_des" name="la_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Right Atrium -->
    <div id="sec-ra" class="form-section">
      <div class="form-section-header">Right Atrium</div>
      <div class="row" style="margin-top:8px">
        <div class="field">
          <label for="ra_size">RA Size</label>
          <select id="ra_size" name="ra_size">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal RA size">Normal RA size</option>
            <option value="Mild RA enlargement">Mild RA enlargement</option>
            <option value="Moderate RA enlargement">Moderate RA enlargement</option>
            <option value="Severe RA enlargement">Severe RA enlargement</option>
          </select>
        </div>
        <div class="field"><label for="ra_area">RA Area (cm²)</label><input id="ra_area" name="ra_area" type="number"
            step="0.1" /></div>
        <div class="field"><label for="ra_volume">RA Volume (cc)</label><input id="ra_volume" name="ra_volume"
            type="number" step="0.1" /></div>

        <div class="field" style="flex:1 1 100%">
          <label for="ra_other_des">Other Descriptions</label>
          <textarea id="ra_other_des" name="ra_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Mitral Valve -->
    <div id="sec-mv" class="form-section">
      <div class="form-section-header">Mitral Valve</div>
      <div class="row" style="margin-top:8px">
        <div class="field valve-status-field">
          <label for="mv_mvls">Mitral Valve</label>
          <select id="mv_mvls" name="mv_mvls">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select>
        </div>
        <div class="field" id="mv_abnormality_container" style="display: none;">
          <label for="mv_abnormality_description">Abnormality Description</label>
          <input id="mv_abnormality_description" name="mv_abnormality_description" type="text" />
        </div>

        <div class="field">
          <label for="mv_PE_Velocity">Peak E Velocity (m/s)</label>
          <input id="mv_PE_Velocity" name="mv_PE_Velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="mv_PA_Velocity">Peak A Velocity (m/s)</label>
          <input id="mv_PA_Velocity" name="mv_PA_Velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="mv_stenosis1">Stenosis</label>
          <select id="mv_stenosis1" name="mv_stenosis1">
            <option value="None">None</option>
            <option value="No MS">No MS</option>
            <option value="Mild MS">Mild MS</option>
            <option value="Moderate MS">Moderate MS</option>
            <option value="Severe MS">Severe MS</option>
          </select>
        </div>
        <div class="field">
          <label for="mv_regurgitant">Regurgitation</label>
          <select id="mv_regurgitant" name="mv_regurgitant">
            <option value="None">None</option>
            <option value="No MR">No MR</option>
            <option value="Trivial MR">Trivial MR</option>
            <option value="Mild MR">Mild MR</option>
            <option value="Mild to Moderate MR">Mild to Moderate MR</option>
            <option value="Moderate MR">Moderate MR</option>
            <option value="Moderate to Severe MR">Moderate to Severe MR</option>
            <option value="Severe MR">Severe MR</option>
          </select>
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="mv_other_des">Other Descriptions</label>
          <textarea id="mv_other_des" name="mv_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Aortic Valve -->
    <div id="sec-av" class="form-section">
      <div class="form-section-header">Aortic Valve</div>
      <div class="row" style="margin-top:8px">
        <div class="field valve-status-field">
          <label for="av_Aortic_valve">Aortic Valve</label>
          <select id="av_Aortic_valve" name="av_Aortic_valve">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select>
        </div>
        <div class="field" id="av_abnormality_container" style="display: none;">
          <label for="av_abnormality_description">Abnormality Description</label>
          <input id="av_abnormality_description" name="av_abnormality_description" type="text" />
        </div>

        <div class="field">
          <label for="av_peakvelocity">Peak Velocity (m/s)</label>
          <input id="av_peakvelocity" name="av_peakvelocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="av_stenosis1">Stenosis</label>
          <select id="av_stenosis1" name="av_stenosis1">
            <option value="None">None</option>
            <option value="No AS">No AS</option>
            <option value="Mild AS">Mild AS</option>
            <option value="Moderate AS">Moderate AS</option>
            <option value="Severe AS">Severe AS</option>
          </select>
        </div>
        <div class="field">
          <label for="av_regurgitant">Regurgitation</label>
          <select id="av_regurgitant" name="av_regurgitant">
            <option value="None">None</option>
            <option value="No AR">No AR</option>
            <option value="Trivial AR">Trivial AR</option>
            <option value="Mild AR">Mild AR</option>
            <option value="Mild to Moderate AR">Mild to Moderate AR</option>
            <option value="Moderate AR">Moderate AR</option>
            <option value="Moderate to Severe AR">Moderate to Severe AR</option>
            <option value="Severe AR">Severe AR</option>
          </select>
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="av_other_des">Other Descriptions</label>
          <textarea id="av_other_des" name="av_other_des"></textarea>
        </div>
      </div>

      <!-- Aorta Subsection -->
      <div style="margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 10px; width: 100%;">
        <div style="font-weight: 600; color: #2d3748; margin-bottom: 8px; font-size: 13px;">Aorta</div>
        <div class="row">
          <div class="field">
            <label for="av_aorta">Aorta</label>
            <select id="av_aorta" name="av_aorta">
              <option value="Auto" selected>Auto</option>
              <option value="None">None</option>
              <option value="Normal aortic size">Normal aortic size</option>
              <option value="Dilated aortic size">Dilated aortic size</option>
              <option value="Manual">Manual</option>
            </select>
          </div>
          <div class="field" id="av_aorta_manual_row" style="display: none;">
            <label for="av_aorta_manual">Manual Aorta Description</label>
            <input type="text" id="av_aorta_manual" name="av_aorta_manual" />
          </div>
          <div class="field">
            <label for="av_annulus">Annulus (mm)</label>
            <input id="av_annulus" name="av_annulus" type="number" step="0.1" />
          </div>
          <div class="field">
            <label for="av_sinus_valsalva">Sinuses of Valsalva (mm)</label>
            <input id="av_sinus_valsalva" name="av_sinus_valsalva" type="number" step="0.1" />
          </div>
          <div class="field">
            <label for="av_st_junction">Sinotubular Junction (mm)</label>
            <input id="av_st_junction" name="av_st_junction" type="number" step="0.1" />
          </div>
          <div class="field">
            <label for="av_proximal_ascending_aorta">Proximal Ascending Aorta (mm)</label>
            <input id="av_proximal_ascending_aorta" name="av_proximal_ascending_aorta" type="number" step="0.1" />
          </div>
        </div>
      </div>
    </div>

    <!-- Pulmonary Valve -->
    <div id="sec-pv" class="form-section">
      <div class="form-section-header">Pulmonary Valve</div>
      <div class="row" style="margin-top:8px">
        <div class="field valve-status-field">
          <label for="pv_Pulmonary_valve">Pulmonary Valve</label>
          <select id="pv_Pulmonary_valve" name="pv_Pulmonary_valve">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select>
        </div>
        <div class="field" id="pv_abnormality_container" style="display: none;">
          <label for="pv_abnormality_description">Abnormality Description</label>
          <input id="pv_abnormality_description" name="pv_abnormality_description" type="text" />
        </div>

        <div class="field">
          <label for="pv_stenosis1">Stenosis</label>
          <select id="pv_stenosis1" name="pv_stenosis1">
            <option value="None">None</option>
            <option value="No PS">No PS</option>
            <option value="Mild PS">Mild PS</option>
            <option value="Moderate PS">Moderate PS</option>
            <option value="Severe PS">Severe PS</option>
          </select>
        </div>
        <div class="field">
          <label for="pv_PV_velocity">PV Velocity (m/s)</label>
          <input id="pv_PV_velocity" name="pv_PV_velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="pv_PV_ACCT">ACCT (msec)</label>
          <input id="pv_PV_ACCT" name="pv_PV_ACCT" type="number" step="1" />
        </div>
        <div class="field">
          <label for="pv_regurgitant">Regurgitation</label>
          <select id="pv_regurgitant" name="pv_regurgitant">
            <option value="None">None</option>
            <option value="No PR">No PR</option>
            <option value="Trivial PR">Trivial PR</option>
            <option value="Mild PR">Mild PR</option>
            <option value="Mild to Moderate PR">Mild to Moderate PR</option>
            <option value="Moderate PR">Moderate PR</option>
            <option value="Moderate to Severe PR">Moderate to Severe PR</option>
            <option value="Severe PR">Severe PR</option>
          </select>
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="pv_other_des">Other Descriptions</label>
          <textarea id="pv_other_des" name="pv_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- Tricuspid Valve -->
    <div id="sec-tv" class="form-section">
      <div class="form-section-header">Tricuspid Valve</div>
      <div class="row" style="margin-top:8px">
        <div class="field valve-status-field">
          <label for="tv_Tricuspid_valve">Tricuspid Valve</label>
          <select id="tv_Tricuspid_valve" name="tv_Tricuspid_valve">
            <option value="Auto" selected>Auto</option>
            <option value="None">None</option>
            <option value="Normal">Normal</option>
            <option value="Abnormal">Abnormal</option>
          </select>
        </div>
        <div class="field" id="tv_abnormality_container" style="display: none;">
          <label for="tv_abnormality_description">Abnormality Description</label>
          <input id="tv_abnormality_description" name="tv_abnormality_description" type="text" />
        </div>

        <div class="field">
          <label for="tv_PE_Velocity">Peak E Velocity (m/s)</label>
          <input id="tv_PE_Velocity" name="tv_PE_Velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="tv_PA_Velocity">Peak A Velocity (m/s)</label>
          <input id="tv_PA_Velocity" name="tv_PA_Velocity" type="number" step="0.1" />
        </div>
        <div class="field">
          <label for="tv_stenosis1">Stenosis</label>
          <select id="tv_stenosis1" name="tv_stenosis1">
            <option value="None">None</option>
            <option value="No TS">No TS</option>
            <option value="Mild TS">Mild TS</option>
            <option value="Moderate TS">Moderate TS</option>
            <option value="Severe TS">Severe TS</option>
          </select>
        </div>
        <div class="field">
          <label for="tv_regurgitant">Regurgitation</label>
          <select id="tv_regurgitant" name="tv_regurgitant">
            <option value="None">None</option>
            <option value="No TR">No TR</option>
            <option value="Trivial TR">Trivial TR</option>
            <option value="Mild TR">Mild TR</option>
            <option value="Mild to Moderate TR">Mild to Moderate TR</option>
            <option value="Moderate TR">Moderate TR</option>
            <option value="Moderate to Severe TR">Moderate to Severe TR</option>
            <option value="Severe TR">Severe TR</option>
          </select>
        </div>
        <div class="field">
          <label for="tv_TRG">TR Gradient (mmHg)</label>
          <input id="tv_TRG" name="tv_TRG" type="number" step="0.1" />
        </div>

        <div class="field" style="flex: 1 1 100%;">
          <label for="tv_other_des">Other Descriptions</label>
          <textarea id="tv_other_des" name="tv_other_des"></textarea>
        </div>
      </div>
    </div>

    <!-- IVC & PAP, Pericardium, Findings, Summary -->
    <div id="sec-ivc" class="form-section">
      <div class="form-section-header">Inferior vena cava & PAP</div>
      <div class="row" style="margin-top:8px">
        <div class="field"><label for="cp_ivc1">IVC</label>
          <select id="cp_ivc1" name="cp_ivc1">
            <option value="auto" selected>auto</option>
            <option value="">none</option>
            <option value="normal size">normal size</option>
            <option value="Dilated">Dilated</option>
          </select>
        </div>
        <div class="field"><label for="cp_ivc_size">IVC size (mm)</label><input id="cp_ivc_size" name="cp_ivc_size"
            type="number" step="0.01" /></div>
        <div class="field"><label for="cp_Respiratory_variation">Respiratory variation</label>
          <select id="cp_Respiratory_variation" name="cp_Respiratory_variation">
            <option value="">none</option>
            <option value="no respiratory variation">no respiratory variation</option>
            <option value="<50% respiratory variation">&lt;50% respiratory variation</option>
            <option value=">50% respiratory variation">&gt;50% respiratory variation</option>
          </select>
        </div>

        <div class="field"><label for="cp_quantity">Quantity</label>
          <select id="cp_quantity" name="cp_quantity">
            <option value="Auto" selected>Auto</option>
            <option value="">none</option>
            <option value="Normal PAP">Normal PAP</option>
            <option value="Top normal PAP">Top normal PAP</option>
            <option value="Mild PH">Mild PH</option>
            <option value="Moderate PH">Moderate PH</option>
            <option value="Severe PH">Severe PH</option>
          </select>
        </div>
        <div class="field"><label for="cp_SPAP">SPAP (mmHg)</label><input id="cp_SPAP" name="cp_SPAP" type="number"
            step="0.01" placeholder="Auto" /></div>
        <div class="field" style="flex:1 1 100%"><label for="cp_SPAP_des">SPAP descriptions</label><textarea
            id="cp_SPAP_des" name="cp_SPAP_des"></textarea></div>
      </div>
    </div>

    <!-- Pericardium -->
    <div id="sec-peri" class="form-section">
      <div class="form-section-header">Pericardium</div>
      <div class="row" style="margin-top:8px">
        <div class="field">
          <label for="pre_Morphology">Morphology</label>
          <select id="pre_Morphology" name="pre_Morphology">
            <option value="Normal pericardium" selected>Normal pericardium</option>
            <option value="">none</option>
            <option value="Thickened pericardium">Thickened pericardium</option>
            <option value="Calcified pericardium">Calcified pericardium</option>
            <option value="Thick and calcified pericardium">Thick and calcified pericardium</option>
          </select>
        </div>

        <div class="field">
          <label for="pre_Pericardial_effusion">Pericardial effusion</label>
          <select id="pre_Pericardial_effusion" name="pre_Pericardial_effusion">
            <option value="No PE" selected>No PE</option>
            <option value="">none</option>
            <option value="Small PE">Small PE</option>
            <option value="Moderate PE">Moderate PE</option>
            <option value="Large PE">Large PE</option>
          </select>
        </div>

        <div class="field">
          <label for="pre_quantity_PE">Quantity</label>
          <input id="pre_quantity_PE" name="pre_quantity_PE" type="text" />
        </div>

        <div class="field">
          <label for="pre_Hemodynamic">Hemodynamic</label>
          <select id="pre_Hemodynamic" name="pre_Hemodynamic">
            <option value="">Select hemodynamic status</option>
            <option value="with hemodynamic effect">with hemodynamic effect</option>
            <option value="without hemodynamic effect">without hemodynamic effect</option>
          </select>
        </div>

        <div class="field" style="flex:1 1 100%"><label for="pre_Pericardium_others">Other descriptions</label><textarea
            id="pre_Pericardium_others" name="pre_Pericardium_others"></textarea></div>
      </div>
    </div>

    <div id="sec-notes" class="form-section">
      <div class="form-section-header">Notes</div>
      <div class="row" style="margin-top:8px">
        <div class="field" style="flex:1 1 100%"><label for="finding">Additional findings</label><textarea id="finding"
            name="finding" maxlength="500" style="min-height: 40px;"></textarea></div>
        <div class="field" style="flex:1 1 100%">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label for="summary" style="margin-bottom: 0;">Summary</label>
            <div style="display: flex; gap: 6px;">
              <button type="button" id="generateSummaryBtn" style="padding: 4px 10px; font-size: 12px; background: #3182ce; color: #fff; border: 0; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-weight: 500;" title="Auto-generate structured summary from form data">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
                </svg>
                <span>Auto Summary</span>
              </button>
              <button type="button" id="clearSummaryBtn" style="padding: 4px 8px; font-size: 12px; background: #718096; color: #fff; border: 0; border-radius: 4px; cursor: pointer;" title="Clear summary text">Clear</button>
            </div>
          </div>
          <textarea id="summary" name="summary" maxlength="1500" style="min-height: 100px; line-height: 1.4;"></textarea>
        </div>
        <div class="field" style="flex:1 1 100%"><label for="recommendation">Recommendation</label><textarea id="recommendation" name="recommendation"
            maxlength="500" style="min-height: 40px;"></textarea></div>
      </div>
    </div>

    <div class="row" style="margin-top:8px">
      <div class="field">
        <label for="doctor">Doctor</label>
        <input id="doctor" name="doctor" type="text" list="doctors_list" />
        <datalist id="doctors_list"></datalist>
      </div>
    </div>

    <div class="controls">
      <button type="button" id="previewToggleBtn" style="background-color: #4a5568; display: flex; align-items: center; gap: 6px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
        <span>Live Preview</span>
      </button>
      <button type="reset">Reset</button>
      <button type="button" id="saveBtn"<?= $editing ? ' style="display:none"' : '' ?>>Save Locally</button>
      <button type="button" id="submitBtn" style="background-color: #2ecc71;"><?= $amending ? 'Save Amendment' : ($editing ? 'Save Changes' : 'Save to Database') ?></button>
    </div>

  </form>
</div>

<div id="preview-resizer" class="hidden no-print"></div>

<div id="preview-container" class="hidden">
  <div class="preview-header no-print">
    <div class="preview-title-group">
      <svg class="preview-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>
      <span class="preview-title">Live Report Preview</span>
      <span class="preview-status-pill">
        <span class="preview-status-dot"></span>
        Live Sync
      </span>
    </div>
    <div class="preview-actions">
      <button type="button" class="preview-btn" id="previewRefreshBtn" title="Force Refresh">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
        </svg>
      </button>
      <button type="button" class="preview-btn" id="previewNewTabBtn" title="Open Report in New Tab">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>
        </svg>
      </button>
      <button type="button" class="preview-btn close-btn" id="previewCloseBtn" title="Close Preview">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
  </div>
  <iframe id="preview-iframe" name="preview-iframe"></iframe>
</div>
</div>

  <!-- Persian Custom Modal Dialog -->
  <dialog id="customAlertModal" class="custom-modal">
    <div class="modal-content">
      <div id="modalIcon" class="modal-icon"></div>
      <h3 id="modalTitle" class="modal-title"></h3>
      <div id="modalMessage" class="modal-message"></div>
      <div class="modal-actions">
        <button type="button" id="modalCloseBtn" class="modal-btn"></button>
      </div>
    </div>
  </dialog>

  <script>
    const ECHO_ROUTES = <?= json_encode([
        'store' => route('studies.store'),
        'preview' => route('studies.preview'),
        'doctors' => route('doctors.index'),
        'storageScope' => auth()->user()->hospital_id.':'.auth()->id(),
        'edit' => $editing ? [
            'url' => route('studies.update', $study),
            'data' => $study->toFormArray(),
        ] : null,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <script>
    // -- Persian Custom Alert Handler
    function showCustomAlert(type, title, message, buttonText, onClose) {
      const modal = document.getElementById('customAlertModal');
      const icon = document.getElementById('modalIcon');
      const titleEl = document.getElementById('modalTitle');
      const messageEl = document.getElementById('modalMessage');
      const closeBtn = document.getElementById('modalCloseBtn');
      
      // Set content
      titleEl.textContent = title;
      messageEl.innerHTML = message;
      closeBtn.textContent = buttonText || 'تایید';
      
      // Set type-specific classes
      icon.className = 'modal-icon ' + type;
      closeBtn.className = 'modal-btn ' + type + '-btn';
      
      // Show dialog
      modal.showModal();
      
      // Animate open
      requestAnimationFrame(() => {
        modal.style.opacity = '1';
        modal.style.transform = 'scale(1)';
      });
      
      const closeHandler = () => {
        // Animate close
        modal.style.opacity = '0';
        modal.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
          modal.close();
          if (onClose) onClose();
        }, 200);
        
        closeBtn.removeEventListener('click', closeHandler);
        modal.removeEventListener('cancel', cancelHandler);
      };
      
      const cancelHandler = (e) => {
        e.preventDefault();
        closeHandler();
      };
      
      closeBtn.addEventListener('click', closeHandler);
      modal.addEventListener('cancel', cancelHandler);
      
      // Close on backdrop click
      const backdropClickHandler = (e) => {
        if (e.target === modal) {
          closeHandler();
          modal.removeEventListener('click', backdropClickHandler);
        }
      };
      modal.addEventListener('click', backdropClickHandler);
    }
    // -- helper for determining if any field in a section has been filled
    function isSectionFilled(section) {
      const inputs = section.querySelectorAll('input, select, textarea');
      for (let input of inputs) {
        if (input.type === 'button' || input.type === 'submit' || input.type === 'reset') continue;

        if (input.type === 'checkbox' || input.type === 'radio') {
          if (input.checked) return true;
        } else {
          const val = input.value.trim();
          if (val !== '' && val.toLowerCase() !== 'none' && val.toLowerCase() !== 'auto' && val !== '-1') {
            return true;
          }
        }
      }
      return false;
    }

    // -- helper for updating navigation button states
    function updateNavButtonStates() {
      const buttons = document.querySelectorAll('.section-nav-btn');
      buttons.forEach(btn => {
        const targetId = btn.getAttribute('data-target');
        const section = document.getElementById(targetId);
        if (!section) return;

        btn.classList.remove('status-unfilled', 'status-filled');

        // Note: 'active' class (blue) is styled via CSS to override unfilled/filled colors.
        // So we can compute and apply the status classes regardless, and CSS handles active precedence.
        const filled = isSectionFilled(section);
        if (filled) {
          btn.classList.add('status-filled');
        } else {
          btn.classList.add('status-unfilled');
        }
      });
    }

    // -- helper for switching active form sections via button navigation
    function initSectionNav() {
      const navButtons = document.querySelectorAll('.section-nav-btn');
      const sections = document.querySelectorAll('.form-section');

      navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const targetId = btn.getAttribute('data-target');

          // Update active button state
          navButtons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');

          // Update active section state
          sections.forEach(sec => {
            if (sec.id === targetId) {
              sec.classList.add('active');
            } else {
              sec.classList.remove('active');
            }
          });

          // Refresh status colors when tab is changed
          updateNavButtonStates();
        });
      });

      // Initial update
      updateNavButtonStates();
    }

    // -- helper for show/hide linked fields
    const linkMap = [
      { src: 'Echo_indication', val: 'Other', target: 'other_Echo_indication_wrap' },
      { src: 'Rhythm', val: 'Other', target: 'other_Rhythm_wrap' },
      { src: 'Echo_type', val: 'Other', target: 'other_Echo_type_wrap' },
      { src: 'lv_Hypertrophy', val: 'Others', target: 'lv_others_wrap' },
      { src: 'lv_Hypertrophy', val: 'Asymmetric LVH with SAM and LVOT obstruction', target: 'PPG_wrap' },
      { src: 'lv_clot', val: 'Other', target: 'other_clot_wrap_lv' },
      { src: 'la_clot', val: '1', target: 'other_clot_wrap_la' },
      { src: 'rv_Hypertrophy', val: 'Other', target: 'rv_other_hyper_wrap' },
    ];

    function applyLinks() {
      linkMap.forEach(m => {
        const src = document.getElementById(m.src);
        const target = document.getElementById(m.target);
        if (!src || !target) return;

        // Check if values match
        const show = String(src.value) === String(m.val);
        target.classList.toggle('hidden', !show);
      });
    }

    // attach listeners
    linkMap.forEach(m => {
      const s = document.getElementById(m.src);
      if (s) s.addEventListener('change', applyLinks);
    });

    // multi-patient auto-save: save to localStorage per patient tab
    const form = document.getElementById('echoForm');
    const STORAGE_PATIENTS_KEY = 'echoForm_patients_v2:' + ECHO_ROUTES.storageScope;
    const STORAGE_ACTIVE_KEY = 'echoForm_active_patient_v2:' + ECHO_ROUTES.storageScope;

    function getPatientsStore() {
      try {
        const raw = localStorage.getItem(STORAGE_PATIENTS_KEY);
        if (!raw) return {};
        return JSON.parse(raw) || {};
      } catch (e) {
        return {};
      }
    }

    function savePatientsStore(store) {
      try {
        localStorage.setItem(STORAGE_PATIENTS_KEY, JSON.stringify(store));
      } catch (e) {}
    }

    function getActivePatientId() {
      return localStorage.getItem(STORAGE_ACTIVE_KEY) || 'p_default';
    }

    function setActivePatientId(id) {
      localStorage.setItem(STORAGE_ACTIVE_KEY, id);
    }

    function generatePatientId() {
      return 'p_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4);
    }

    function initMultiPatientStore() {
      let store = getPatientsStore();
      // Migration from old single-patient autosave if exists
      const oldRaw = localStorage.getItem('echoForm_autosave_v1');
      if (oldRaw && Object.keys(store).length === 0) {
        try {
          const oldData = JSON.parse(oldRaw);
          const defaultId = 'p_default';
          store[defaultId] = {
            id: defaultId,
            title: (oldData.first_name || oldData.last_name) ? `${oldData.first_name || ''} ${oldData.last_name || ''}`.trim() : 'Patient 1',
            data: oldData,
            updatedAt: Date.now()
          };
          savePatientsStore(store);
          setActivePatientId(defaultId);
        } catch (e) {}
      }

      if (Object.keys(store).length === 0) {
        const defaultId = 'p_default';
        store[defaultId] = {
          id: defaultId,
          title: 'Patient 1',
          data: {},
          updatedAt: Date.now()
        };
        savePatientsStore(store);
        setActivePatientId(defaultId);
      }

      if (!store[getActivePatientId()]) {
        setActivePatientId(Object.keys(store)[0]);
      }

      renderPatientTabs();
      loadPatientData(getActivePatientId());
    }

    function renderPatientTabs() {
      const container = document.getElementById('patientTabsContainer');
      if (!container) return;

      const store = getPatientsStore();
      const activeId = getActivePatientId();
      const keys = Object.keys(store);

      container.innerHTML = '';

      keys.forEach((pid, index) => {
        const item = store[pid];
        const fname = item.data ? (item.data.first_name || '') : '';
        const lname = item.data ? (item.data.last_name || '') : '';
        let displayName = (fname || lname) ? `${fname} ${lname}`.trim() : (item.title || `Patient ${index + 1}`);

        const tabBtn = document.createElement('div');
        tabBtn.className = 'patient-tab' + (pid === activeId ? ' active' : '');
        
        const titleSpan = document.createElement('span');
        titleSpan.className = 'patient-tab-title';
        titleSpan.textContent = displayName;
        titleSpan.addEventListener('click', () => switchPatient(pid));

        tabBtn.appendChild(titleSpan);

        if (keys.length > 1) {
          const closeBtn = document.createElement('button');
          closeBtn.type = 'button';
          closeBtn.className = 'patient-tab-close';
          closeBtn.title = 'Remove patient draft';
          closeBtn.innerHTML = '&times;';
          closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            removePatient(pid);
          });
          tabBtn.appendChild(closeBtn);
        }

        container.appendChild(tabBtn);
      });
    }

    function switchPatient(targetId) {
      if (targetId === getActivePatientId()) return;
      saveToLocal(true); // silent save current tab before switching
      setActivePatientId(targetId);
      renderPatientTabs();
      loadPatientData(targetId);
    }

    function addNewPatientTab() {
      saveToLocal(true);
      const store = getPatientsStore();
      const newId = generatePatientId();
      const count = Object.keys(store).length + 1;
      store[newId] = {
        id: newId,
        title: `Patient ${count}`,
        data: {},
        updatedAt: Date.now()
      };
      savePatientsStore(store);
      setActivePatientId(newId);
      renderPatientTabs();
      form.reset();
      loadPatientData(newId);
    }

    function removePatient(pid) {
      const store = getPatientsStore();
      const keys = Object.keys(store);
      if (keys.length <= 1) return;

      delete store[pid];
      savePatientsStore(store);

      if (getActivePatientId() === pid) {
        const nextId = Object.keys(store)[0];
        setActivePatientId(nextId);
        loadPatientData(nextId);
      }
      renderPatientTabs();
    }

    function saveToLocal(silent = false) {
      if (ECHO_ROUTES.edit) return;
      const activeId = getActivePatientId();
      const store = getPatientsStore();
      const data = {};
      new FormData(form).forEach((v, k) => {
        data[k] = v;
      });

      const fname = data.first_name || '';
      const lname = data.last_name || '';
      const title = (fname || lname) ? `${fname} ${lname}`.trim() : (store[activeId]?.title || 'Patient');

      store[activeId] = {
        id: activeId,
        title: title,
        data: data,
        updatedAt: Date.now()
      };

      savePatientsStore(store);
      renderPatientTabs();
      if (!silent) flashSaved();
    }

    function flashSaved() {
      const b = document.getElementById('saveBtn');
      if (!b) return;
      b.textContent = 'Saved';
      setTimeout(() => b.textContent = 'Save Locally', 900);
    }

    // load saved patient data
    function loadPatientData(pid) {
      const store = getPatientsStore();
      const patient = store[pid];
      form.reset();
      const qtyInitEl = document.getElementById('cp_quantity');
      const spapInitEl = document.getElementById('cp_SPAP');
      if (qtyInitEl) { delete qtyInitEl.dataset.userLocked; delete qtyInitEl.dataset.autoCalculated; }
      if (spapInitEl) { delete spapInitEl.dataset.autoCalculated; }

      if (!patient || !patient.data) {
        applyLinks();
        return;
      }

      const obj = patient.data;
      Object.entries(obj).forEach(([k, v]) => {
        const el = form.elements[k];
        if (!el) return;
        try {
          if (el.type === 'checkbox' || el.type === 'radio') { el.checked = !!v; }
          else el.value = v;
        } catch (e) { }
      });
      if (qtyInitEl && obj['cp_quantity'] && obj['cp_quantity'] !== 'Auto') {
        qtyInitEl.dataset.userLocked = 'true';
      }
      applyLinks();
      updateNavButtonStates();
      queueLivePreviewUpdate();
    }

    function loadSaved() {
      if (ECHO_ROUTES.edit) {
        loadEditData(ECHO_ROUTES.edit.data);
        return;
      }
      initMultiPatientStore();
    }

    // Fill the form from a saved study. Values that are not one of a select's
    // options were typed in its "Manual" box, so restore them there.
    function loadEditData(obj) {
      form.reset();
      const manualSelects = { lv_size: 'lv_size_manual', rv_size: 'rv_size_manual', av_aorta: 'av_aorta_manual' };
      Object.entries(obj).forEach(([k, v]) => {
        const el = form.elements[k];
        if (!el) return;
        if (el.type === 'checkbox' || el.type === 'radio') { el.checked = v === '1'; return; }
        if (el.tagName === 'SELECT' && v !== '' && !Array.from(el.options).some((o) => o.value === v)) {
          if (manualSelects[k] && form.elements[manualSelects[k]]) {
            el.value = 'Manual';
            el.dispatchEvent(new Event('change'));
            form.elements[manualSelects[k]].value = v;
          } else {
            const opt = document.createElement('option');
            opt.value = v; opt.textContent = v;
            el.appendChild(opt);
            el.value = v;
          }
          return;
        }
        el.value = v;
      });
      const qtyInitEl = document.getElementById('cp_quantity');
      if (qtyInitEl && obj['cp_quantity'] && obj['cp_quantity'] !== 'Auto') {
        qtyInitEl.dataset.userLocked = 'true';
      }
      applyLinks();
      updateNavButtonStates();
      queueLivePreviewUpdate();
    }

    // export JSON
    function exportJSON() {
      const data = {};
      new FormData(form).forEach((v, k) => {
        data[k] = v;
      });
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'echo-export.json'; document.body.appendChild(a); a.click(); a.remove();
      URL.revokeObjectURL(url);
    }

    // Handle form submission
    async function submitForm() {
      const form = document.getElementById('echoForm');
      
      // Perform validation check for required fields
      const requiredFields = [
        { id: 'first_name', label: 'نام' },
        { id: 'last_name', label: 'نام خانوادگی' },
        { id: 'nationalID', label: 'کد ملی' }
      ];

      const missingFields = [];
      requiredFields.forEach(field => {
        const el = document.getElementById(field.id);
        if (el) {
          if (!el.value || el.value.trim() === '') {
            missingFields.push(field.label);
          }
        }
      });

      if (missingFields.length > 0) {
        const message = 'لطفاً فیلدهای الزامی زیر را تکمیل کنید:<br><ul>' + 
                        missingFields.map(f => `<li>${f}</li>`).join('') + 
                        '</ul>';
        showCustomAlert('warning', 'اطلاعات ناقص', message, 'تایید');
        return;
      }

      const formData = new FormData(form);
      const data = {};

      // Get all select elements to identify which fields should be treated as text
      const selectFields = Array.from(document.querySelectorAll('select')).map(select => select.name);

      // Convert FormData to object with proper field names
      for (let [key, value] of formData.entries()) {
        // Skip empty values for optional fields
        if (value === '' || key === '_token') continue;

        // Skip the temporary manual field so it isn't submitted as a database column
        if (key === 'lv_size_manual' || key === 'rv_size_manual' || key === 'av_aorta_manual') continue;

        // Handle manual override for select fields
        if (key === 'lv_size' && value === 'Manual') {
          const manualVal = formData.get('lv_size_manual');
          if (manualVal) {
            value = manualVal;
          }
        }
        if (key === 'rv_size' && value === 'Manual') {
          const manualVal = formData.get('rv_size_manual');
          if (manualVal) {
            value = manualVal;
          }
        }
        if (key === 'av_aorta' && value === 'Manual') {
          const manualVal = formData.get('av_aorta_manual');
          if (manualVal) {
            value = manualVal;
          }
        }

        // Check if this is a select field - if so, always save as text
        if (selectFields.includes(key)) {
          data[key] = value;
          continue;
        }

        // Handle numeric fields for non-select inputs
        if (key.startsWith('lv_') || key.startsWith('rv_') || key.startsWith('la_') ||
          key.startsWith('ra_') || key.startsWith('mv_') || key.startsWith('av_') ||
          key.startsWith('pv_') || key.startsWith('tv_') || key.startsWith('cp_') ||
          key.startsWith('pre_')) {

          // List of fields that MUST be numbers.
          const numericFields = [
            'LVEDV', 'LVESV', 'LVEDD', 'LVESD', 'IVS_thickness', 'Posterior',
            'EF', 'GLS', 'LVOT', 'LVOT_diameter', 'septale', 'lateral',
            'septala', 'septal_Sm', 'PPG',
            'rv_RVOT', 'rv_RVOT_diameter',
            'la_area', 'la_volume', 'la_diameter', 'ra_area', 'ra_volume',
            'mv_planimetry', 'mv_MPG', 'mv_PHT', 'mv_PE_Velocity', 'mv_PA_Velocity', 'mv_DT',
            'mv_MV_area', 'av_peakvelocity', 'av_AVA_CE', 'av_DVI', 'av_PHT', 'av_planimetry',
            'av_MPG', 'av_ACCT', 'av_Velocity', 'av_PPG', 'a_AV_annulus', 'a_Sinus_valsalva',
            'a_ST_junction', 'a_ascending_aorta', 'av_annulus', 'av_sinus_valsalva',
            'av_st_junction', 'av_proximal_ascending_aorta', 'pv_Velocity', 'pv_ACCT', 'pv_MPG', 'pv_PPG',
            'tv_MPG', 'tv_PHT', 'tv_PE_Velocity', 'tv_PA_Velocity', 'tv_TRG', 'cp_ivc_size',
            'cp_SPAP'
          ];

          if (numericFields.some(field => key.endsWith(field))) {
            // Convert to number
            data[key] = value.includes('.') ? parseFloat(value) : parseInt(value, 10);
          } else {
            // Keep as string (Text)
            data[key] = value;
          }
        } else {
          // Non-prefixed fields
          data[key] = value;
        }
      }

      if (!data['dob']) data['dob'] = '';
      if (!data['first_name']) data['first_name'] = '';
      if (!data['last_name']) data['last_name'] = '';

      if (!data['echo_date']) {
        const d = new Date();
        const pDate = new Intl.DateTimeFormat('en-US-u-ca-persian', {year: 'numeric', month: '2-digit', day: '2-digit'}).format(d);
        const parts = pDate.split('/');
        const year = parts[2].replace(/\D/g, '');
        const month = parts[0].replace(/\D/g, '');
        const day = parts[1].replace(/\D/g, '');
        data['echo_date'] = year + '/' + month + '/' + day;
      }

      try {
        const response = await fetch(ECHO_ROUTES.edit ? ECHO_ROUTES.edit.url : ECHO_ROUTES.store, {
          method: ECHO_ROUTES.edit ? 'PUT' : 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify(data)
        });

        if (response.status === 419 || response.status === 401) {
          showCustomAlert('error', 'نشست منقضی شده', 'لطفاً صفحه را دوباره بارگذاری کرده و وارد شوید. اطلاعات فرم در مرورگر ذخیره شده است.', 'متوجه شدم');
          return;
        }

        const result = await response.json();

        if (response.status === 422 && result.errors) {
          const escapeHtml = (t) => String(t).replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
          const items = Object.values(result.errors).flat().map((m) => `<li>${escapeHtml(m)}</li>`).join('');
          showCustomAlert('warning', 'اطلاعات نامعتبر', `<ul>${items}</ul>`, 'تایید');
          return;
        }

        if (response.ok && result.success && ECHO_ROUTES.edit) {
          window.location.href = result.url;
          return;
        }

        if (response.ok && result.success) {
          showCustomAlert('success', 'ثبت موفقیت‌آمیز', 'اطلاعات اکو با موفقیت ذخیره شد.', 'تایید');
        } else {
          throw new Error(result.message || 'Failed to save data');
        }
      } catch (error) {
        console.error('Error saving data:', error);
        showCustomAlert('error', 'خطا در ثبت اطلاعات', 'خطایی در ذخیره اطلاعات رخ داده است. لطفاً برای اطلاعات بیشتر لاگ‌های سیستم را بررسی کنید.', 'متوجه شدم');
      }
    }

    // Add input validation for numeric fields
    function validateNumericInput(event) {
      if (event.target.type === 'number') {
        const value = parseFloat(event.target.value);
        const min = parseFloat(event.target.min);
        const max = parseFloat(event.target.max);

        if (!isNaN(min) && value < min) {
          event.target.value = min;
        } else if (!isNaN(max) && value > max) {
          event.target.value = max;
        }
      }
    }

    // Add input event listeners for validation
    document.querySelectorAll('input[type="number"]').forEach(input => {
      input.addEventListener('blur', validateNumericInput);
    });

    // Initialize Persian Datepicker
    $(document).ready(function () {
      $("#dob").persianDatepicker({
        observer: true,
        format: 'YYYY/MM/DD',
        altField: '#dob-display',
        altFormat: 'YYYY/MM/DD',
        initialValue: false
      });
      $("#echo_date").persianDatepicker({
        observer: true,
        format: 'YYYY/MM/DD',
        altField: '#echo_date-display',
        altFormat: 'YYYY/MM/DD',
        initialValue: true
      });
    });

    const RWMA_MOTION_OPTIONS = [
      ['none', 'None'],
      ['Normal', 'Normal'],
      ['Mild hypokinesia', 'Mild hypokinesia'],
      ['Severe hypokinesia', 'Severe hypokinesia'],
      ['Akinesia', 'Akinesia'],
      ['Dyskinesia', 'Dyskinesia'],
      ['Aneurysm', 'Aneurysm'],
    ];

    const RWMA_TERRITORIES = [
      {
        title: 'Basal',
        segments: [
          { name: 'lv_Basal_Anterior', label: 'Basal Anterior (LAD)' },
          { name: 'lv_Basal_anteroseptum', label: 'Basal Anteroseptal (LAD)' },
          { name: 'lv_Basal_inferoseptum', label: 'Basal Inferoseptal (RCA)' },
          { name: 'lv_Basal_inferior', label: 'Basal Inferior (RCA)' },
          { name: 'lv_Basal_inferolateral', label: 'Basal Inferolateral (LCX)' },
          { name: 'lv_Basal_anterolateral', label: 'Basal Anterolateral (LCX)' },
        ],
      },
      {
        title: 'Mid',
        segments: [
          { name: 'lv_Mid_Anterior', label: 'Mid Anterior (LAD)' },
          { name: 'lv_Mid_anteroseptum', label: 'Mid Anteroseptal (LAD)' },
          { name: 'lv_Mid_inferoseptum', label: 'Mid Inferoseptal (RCA)' },
          { name: 'lv_Mid_inferior', label: 'Mid Inferior (RCA)' },
          { name: 'lv_Mid_inferolateral', label: 'Mid Inferolateral (LCX)' },
          { name: 'lv_Mid_anterolateral', label: 'Mid Anterolateral (LCX)' },
        ],
      },
      {
        title: 'Apical',
        segments: [
          { name: 'lv_Apical_anterior', label: 'Apical Anterior (LAD)' },
          { name: 'lv_Apical_septum', label: 'Apical Septal (LAD)' },
          { name: 'lv_Apical_inferior', label: 'Apical Inferior (LAD)' },
          { name: 'lv_Apical_lateral', label: 'Apical Lateral (LCX)' },
          { name: 'lv_Apical_cap', label: 'Apical Cap (LAD)' },
        ],
      },
    ];

    function buildRwmaFields() {
      const container = document.getElementById('rwma-segments');
      if (!container || container.dataset.built === '1') return;
      container.dataset.built = '1';

      RWMA_TERRITORIES.forEach((group) => {
        const heading = document.createElement('div');
        heading.className = 'small';
        heading.style.cssText = 'font-weight:600;margin-top:12px;margin-bottom:4px';
        heading.textContent = group.title;
        container.appendChild(heading);

        const row = document.createElement('div');
        row.className = 'row';
        row.style.marginTop = '8px';

        group.segments.forEach((seg) => {
          const field = document.createElement('div');
          field.className = 'field';
          const label = document.createElement('label');
          label.htmlFor = seg.name;
          label.textContent = seg.label;
          const select = document.createElement('select');
          select.id = seg.name;
          select.name = seg.name;
          RWMA_MOTION_OPTIONS.forEach(([text, val]) => {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = text;
            select.appendChild(opt);
          });
          select.addEventListener('change', () => {
            const val = select.value;
            if (val && val.toLowerCase() !== 'normal' && val.toLowerCase() !== 'none') {
              const rwmaCb = document.getElementById('lv_rwma_checked');
              if (rwmaCb && !rwmaCb.checked) {
                rwmaCb.checked = true;
              }
            }
          });
          field.appendChild(label);
          field.appendChild(select);
          row.appendChild(field);
        });

        container.appendChild(row);
      });
    }

    // Function to handle valve abnormality description visibility
    function setupValveAbnormalityToggle(selectId, containerId) {
      const select = document.getElementById(selectId);
      const container = document.getElementById(containerId);

      if (!select || !container) return;

      // Add event listener
      select.addEventListener('change', function () {
        container.style.display = (this.value === 'Abnormal') ? 'flex' : 'none';
      });
    }

    function updateValveAbnormalityToggles() {
      [
        { select: 'mv_mvls', container: 'mv_abnormality_container' },
        { select: 'av_Aortic_valve', container: 'av_abnormality_container' },
        { select: 'pv_Pulmonary_valve', container: 'pv_abnormality_container' },
        { select: 'tv_Tricuspid_valve', container: 'tv_abnormality_container' }
      ].forEach(cfg => {
        const select = document.getElementById(cfg.select);
        const container = document.getElementById(cfg.container);
        if (select && container) {
          container.style.display = (select.value === 'Abnormal') ? 'flex' : 'none';
        }
      });
    }

    // Set up event listeners for all valve sections
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(function () {
        applyLinks(); // Ensure links apply on load

        // Set up valve abnormality toggles
        setupValveAbnormalityToggle('mv_mvls', 'mv_abnormality_container');
        setupValveAbnormalityToggle('av_Aortic_valve', 'av_abnormality_container');
        setupValveAbnormalityToggle('pv_Pulmonary_valve', 'pv_abnormality_container');
        setupValveAbnormalityToggle('tv_Tricuspid_valve', 'tv_abnormality_container');

        updateValveAbnormalityToggles();
        setupAutoSpapAndQuantityListeners();
      }, 100);
    });

    // Auto-calculate SPAP from TR Gradient, IVC size and Respiratory variation, and PAP Quantity
    function calculateAutoSpapAndQuantity(isDirectSpapEdit = false) {
      const trgInput = document.getElementById('tv_TRG');
      const ivcSizeInput = document.getElementById('cp_ivc_size');
      const respVarSelect = document.getElementById('cp_Respiratory_variation');
      const spapInput = document.getElementById('cp_SPAP');
      const qtySelect = document.getElementById('cp_quantity');

      if (!spapInput || !qtySelect) return;

      const trgVal = parseFloat(trgInput ? trgInput.value : '');
      const ivcSizeVal = parseFloat(ivcSizeInput ? ivcSizeInput.value : '');
      const respVarVal = (respVarSelect ? respVarSelect.value : '').toLowerCase().trim();

      // 1. Calculate SPAP if inputs are valid and user is not typing SPAP directly
      if (!isDirectSpapEdit) {
        if (!isNaN(trgVal) && trgVal > 0 && !isNaN(ivcSizeVal) && respVarVal && respVarVal !== 'none') {
          const isCollapseOver50 = respVarVal.includes('>50%') || respVarVal.includes('> 50%');
          let rap = 0;
          if (ivcSizeVal <= 21) {
            rap = isCollapseOver50 ? 3 : 8;
          } else {
            rap = isCollapseOver50 ? 8 : 15;
          }
          const calculatedSpap = Math.round((trgVal + rap) * 10) / 10;
          spapInput.value = calculatedSpap;
          spapInput.dataset.autoCalculated = 'true';
        } else if (spapInput.dataset.autoCalculated === 'true') {
          if (isNaN(trgVal) || trgVal <= 0) {
            spapInput.value = '';
            delete spapInput.dataset.autoCalculated;
          }
        }
      }

      // 2. Calculate Quantity based on SPAP range: <35: normal, 35-44: Mild PH, 45-59: Moderate PH, >=60: severe
      const spapVal = parseFloat(spapInput.value);
      if (!isNaN(spapVal)) {
        let autoQty = '';
        if (spapVal < 35) {
          autoQty = 'Normal PAP';
        } else if (spapVal <= 44) {
          autoQty = 'Mild PH';
        } else if (spapVal <= 59) {
          autoQty = 'Moderate PH';
        } else {
          autoQty = 'Severe PH';
        }

        if (qtySelect.value === 'Auto' || qtySelect.value === '' || qtySelect.dataset.autoCalculated === 'true' || !qtySelect.dataset.userLocked) {
          qtySelect.value = autoQty;
          qtySelect.dataset.autoCalculated = 'true';
        }
      } else {
        if (qtySelect.dataset.autoCalculated === 'true') {
          qtySelect.value = 'Auto';
          delete qtySelect.dataset.autoCalculated;
        }
      }
    }

    function setupAutoSpapAndQuantityListeners() {
      const trgEl = document.getElementById('tv_TRG');
      const ivcSizeEl = document.getElementById('cp_ivc_size');
      const respVarEl = document.getElementById('cp_Respiratory_variation');
      const spapEl = document.getElementById('cp_SPAP');
      const qtyEl = document.getElementById('cp_quantity');

      if (trgEl) {
        trgEl.addEventListener('input', () => { calculateAutoSpapAndQuantity(false); });
        trgEl.addEventListener('change', () => { calculateAutoSpapAndQuantity(false); });
      }
      if (ivcSizeEl) {
        ivcSizeEl.addEventListener('input', () => { calculateAutoSpapAndQuantity(false); });
        ivcSizeEl.addEventListener('change', () => { calculateAutoSpapAndQuantity(false); });
      }
      if (respVarEl) {
        respVarEl.addEventListener('change', () => { calculateAutoSpapAndQuantity(false); });
      }
      if (spapEl) {
        spapEl.addEventListener('input', () => {
          delete spapEl.dataset.autoCalculated;
          calculateAutoSpapAndQuantity(true);
        });
        spapEl.addEventListener('change', () => {
          delete spapEl.dataset.autoCalculated;
          calculateAutoSpapAndQuantity(true);
        });
      }
      if (qtyEl) {
        qtyEl.addEventListener('change', () => {
          if (qtyEl.value === 'Auto') {
            delete qtyEl.dataset.userLocked;
            delete qtyEl.dataset.autoCalculated;
            calculateAutoSpapAndQuantity(false);
          } else {
            qtyEl.dataset.userLocked = 'true';
            delete qtyEl.dataset.autoCalculated;
          }
        });
      }
    }

    // wire events
    form.addEventListener('change', () => {
      window._dirty = true;
      saveToLocal();
      updateNavButtonStates();
      queueLivePreviewUpdate();
    });
    form.addEventListener('input', () => {
      updateNavButtonStates();
      queueLivePreviewUpdate();
    });

    document.getElementById('saveBtn').addEventListener('click', saveToLocal);
    document.getElementById('submitBtn').addEventListener('click', submitForm);

    // Live Preview Logic
    const previewToggleBtn = document.getElementById('previewToggleBtn');
    const previewContainer = document.getElementById('preview-container');
    const previewResizer = document.getElementById('preview-resizer');
    const previewCloseBtn = document.getElementById('previewCloseBtn');
    const previewRefreshBtn = document.getElementById('previewRefreshBtn');
    const previewNewTabBtn = document.getElementById('previewNewTabBtn');
    const appLayout = document.getElementById('app-layout');
    const formContainer = document.getElementById('form-container');

    function toggleLivePreview(forceState) {
      const isCurrentlyActive = appLayout.classList.contains('preview-active');
      const shouldActivate = forceState !== undefined ? forceState : !isCurrentlyActive;

      if (shouldActivate) {
        appLayout.classList.add('preview-active');
        previewContainer.classList.remove('hidden');
        if (previewResizer) previewResizer.classList.remove('hidden');
        previewToggleBtn.classList.add('active');
        previewToggleBtn.style.backgroundColor = '#2b6cb0'; // Active state color
        localStorage.setItem('echoForm_live_preview', 'true');
        
        // Restore saved split width if exists
        const savedWidth = localStorage.getItem('echoForm_preview_split_width');
        if (savedWidth && window.innerWidth > 1024) {
          formContainer.style.flex = `0 0 ${savedWidth}%`;
          previewContainer.style.flex = `0 0 ${100 - savedWidth}%`;
        } else if (window.innerWidth > 1024) {
          formContainer.style.flex = `0 0 50%`;
          previewContainer.style.flex = `0 0 50%`;
        }
        
        updateLivePreview();
      } else {
        appLayout.classList.remove('preview-active');
        previewContainer.classList.add('hidden');
        if (previewResizer) previewResizer.classList.add('hidden');
        previewToggleBtn.classList.remove('active');
        previewToggleBtn.style.backgroundColor = '#4a5568'; // Inactive state color
        localStorage.setItem('echoForm_live_preview', 'false');
        
        // Reset flex styles
        formContainer.style.flex = '';
        previewContainer.style.flex = '';
      }
    }

    // Draggable Resizer Logic
    let isDragging = false;
    if (previewResizer) {
      previewResizer.addEventListener('mousedown', (e) => {
        isDragging = true;
        document.body.classList.add('dragging');
        previewResizer.classList.add('dragging');
        e.preventDefault();
      });

      document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const containerWidth = appLayout.clientWidth;
        if (containerWidth <= 0) return;
        
        let newFormWidth = (e.clientX / containerWidth) * 100;
        // Constrain form width percentage between 20% and 80%
        if (newFormWidth < 20) newFormWidth = 20;
        if (newFormWidth > 80) newFormWidth = 80;
        
        formContainer.style.flex = `0 0 ${newFormWidth}%`;
        previewContainer.style.flex = `0 0 ${100 - newFormWidth}%`;
      });

      document.addEventListener('mouseup', () => {
        if (isDragging) {
          isDragging = false;
          document.body.classList.remove('dragging');
          previewResizer.classList.remove('dragging');
          
          // Save width preference if not in responsive stacked layout
          if (window.innerWidth > 1024) {
            const match = formContainer.style.flex.match(/0\s+0\s+([\d.]+)%/);
            if (match && match[1]) {
              localStorage.setItem('echoForm_preview_split_width', match[1]);
            }
          }
        }
      });
    }

    if (previewToggleBtn) {
      previewToggleBtn.addEventListener('click', () => toggleLivePreview());
    }
    if (previewCloseBtn) {
      previewCloseBtn.addEventListener('click', () => toggleLivePreview(false));
    }
    if (previewRefreshBtn) {
      previewRefreshBtn.addEventListener('click', () => updateLivePreview());
    }
    if (previewNewTabBtn) {
      previewNewTabBtn.addEventListener('click', () => {
        const originalAction = form.action;
        const originalTarget = form.target;
        const originalMethod = form.method;
        
        form.action = ECHO_ROUTES.preview;
        form.target = '_blank';
        form.method = 'POST';
        form.submit();
        
        form.action = originalAction;
        form.target = originalTarget;
        form.method = originalMethod;
      });
    }

    let previewTimeout = null;
    function updateLivePreview() {
      if (!appLayout.classList.contains('preview-active')) return;
      
      const originalAction = form.action;
      const originalTarget = form.target;
      const originalMethod = form.method;
      
      form.action = ECHO_ROUTES.preview;
      form.target = 'preview-iframe';
      form.method = 'POST';
      form.submit();
      
      form.action = originalAction;
      form.target = originalTarget;
      form.method = originalMethod;
    }

    function queueLivePreviewUpdate() {
      if (!appLayout.classList.contains('preview-active')) return;
      if (previewTimeout) clearTimeout(previewTimeout);
      previewTimeout = setTimeout(() => {
        updateLivePreview();
      }, 400);
    }

    function setupManualToggle(selectId, inputId, rowId) {
      const select = document.getElementById(selectId);
      const manualInput = document.getElementById(inputId);
      const row = document.getElementById(rowId);
      if (!select || !manualInput || !row) return;

      function updateVisibility() {
        if (select.value === 'Manual') {
          row.style.display = 'flex';
        } else {
          row.style.display = 'none';
          manualInput.value = '';
        }
      }

      select.addEventListener('change', updateVisibility);
      updateVisibility();
    }

    // Auto Summary Generator
    function getSummaryFieldVal(id) {
      const el = document.getElementById(id);
      if (!el) return '';
      if (el.type === 'checkbox') return el.checked;
      let value = (el.value || '').trim();
      // Remove "there is", "there are", "there was", "there were" prefixes
      value = value.replace(/^(there is|there are|there was|there were)\s+/i, '');
      return value;
    }

    function isCleanSummaryVal(v) {
      if (!v) return false;
      const lower = String(v).toLowerCase();
      return lower !== 'none' && lower !== '-1' && lower !== '' && lower !== 'auto';
    }

    // Resolves field values respecting user manual selections while auto-calculating
    // empty or "Auto" fields using the exact same formulas as view_report.php
    function getResolvedSummaryValues() {
      const gender = (getSummaryFieldVal('gender') || '').trim();
      const isMale = gender.toLowerCase() === 'male' || gender === '1';
      const isFemale = gender.toLowerCase() === 'female' || gender === '2';

      const height = parseFloat(getSummaryFieldVal('Height')) || 0;
      const weight = parseFloat(getSummaryFieldVal('Weight')) || 0;
      let bsa = 0;
      if (height > 0 && weight > 0) {
        bsa = Math.sqrt((height * weight) / 3600);
      }

      // 1. LV Size
      const lvSizeRaw = getSummaryFieldVal('lv_size');
      let lvSize = '';
      if (lvSizeRaw === 'Manual') {
        lvSize = getSummaryFieldVal('lv_size_manual');
      } else if (isCleanSummaryVal(lvSizeRaw)) {
        lvSize = lvSizeRaw;
      } else {
        const lvedv = parseFloat(getSummaryFieldVal('lv_LVEDV')) || 0;
        const lvedvi = (lvedv > 0 && bsa > 0) ? (lvedv / bsa) : 0;
        const lvdd = parseFloat(getSummaryFieldVal('lv_LVEDD')) || 0;
        const lveddi = (lvdd > 0 && bsa > 0) ? (lvdd / bsa) : 0;

        let autoLvSize = null;
        if (isMale || isFemale) {
          if (lvedvi > 0) {
            if (isMale) {
              if (lvedvi <= 74) autoLvSize = 'Normal LV size';
              else if (lvedvi <= 89) autoLvSize = 'Mild LV enlargement';
              else if (lvedvi <= 100) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            } else {
              if (lvedvi <= 61) autoLvSize = 'Normal LV size';
              else if (lvedvi <= 70) autoLvSize = 'Mild LV enlargement';
              else if (lvedvi <= 80) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            }
          } else if (lvedv > 0) {
            if (isMale) {
              if (lvedv <= 150) autoLvSize = 'Normal LV size';
              else if (lvedv <= 174) autoLvSize = 'Mild LV enlargement';
              else if (lvedv <= 200) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            } else {
              if (lvedv <= 106) autoLvSize = 'Normal LV size';
              else if (lvedv <= 120) autoLvSize = 'Mild LV enlargement';
              else if (lvedv <= 130) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            }
          } else if (lveddi > 0) {
            if (isMale) {
              if (lveddi <= 30) autoLvSize = 'Normal LV size';
              else if (lveddi <= 33) autoLvSize = 'Mild LV enlargement';
              else if (lveddi <= 36) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            } else {
              if (lveddi <= 31) autoLvSize = 'Normal LV size';
              else if (lveddi <= 34) autoLvSize = 'Mild LV enlargement';
              else if (lveddi <= 37) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            }
          } else if (lvdd > 0) {
            if (isMale) {
              if (lvdd <= 58) autoLvSize = 'Normal LV size';
              else if (lvdd <= 63) autoLvSize = 'Mild LV enlargement';
              else if (lvdd <= 68) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            } else {
              if (lvdd <= 52) autoLvSize = 'Normal LV size';
              else if (lvdd <= 56) autoLvSize = 'Mild LV enlargement';
              else if (lvdd <= 61) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            }
          }
        }
        lvSize = autoLvSize || 'Normal LV size';
      }

      // 2. LV Systolic
      const lvSysRaw = getSummaryFieldVal('lv_Systolic');
      let lvSys = '';
      const ef = parseFloat(getSummaryFieldVal('lv_EF')) || 0;
      if (isCleanSummaryVal(lvSysRaw)) {
        lvSys = lvSysRaw;
      } else {
        let autoSys = null;
        if (ef > 0 && (isMale || isFemale)) {
          if (isMale) {
            if (ef < 30) autoSys = 'With severe systolic dysfunction';
            else if (ef < 41) autoSys = 'With moderate systolic dysfunction';
            else if (ef < 52) autoSys = 'With mild systolic dysfunction';
            else autoSys = 'With normal systolic function';
          } else {
            if (ef < 30) autoSys = 'With severe systolic dysfunction';
            else if (ef < 41) autoSys = 'With moderate systolic dysfunction';
            else if (ef < 54) autoSys = 'With mild systolic dysfunction';
            else autoSys = 'With normal systolic function';
          }
        } else if (ef > 0) {
          if (ef < 30) autoSys = 'With severe systolic dysfunction';
          else if (ef < 41) autoSys = 'With moderate systolic dysfunction';
          else if (ef < 52) autoSys = 'With mild systolic dysfunction';
          else autoSys = 'With normal systolic function';
        }
        lvSys = autoSys || 'With normal systolic function';
      }

      // 3. LV Hypertrophy
      const lvHypRaw = getSummaryFieldVal('lv_Hypertrophy');
      let lvHyp = '';
      if (isCleanSummaryVal(lvHypRaw)) {
        lvHyp = lvHypRaw;
      } else {
        const ivs = parseFloat(getSummaryFieldVal('lv_IVS_thickness')) || 0;
        let autoHyp = null;
        if (ivs > 0 && (isMale || isFemale)) {
          if (isMale) {
            if (ivs < 11) autoHyp = 'No LVH';
            else if (ivs < 14) autoHyp = 'Mild LVH';
            else if (ivs <= 16) autoHyp = 'Moderate LVH';
            else autoHyp = 'Severe LVH';
          } else {
            if (ivs < 10) autoHyp = 'No LVH';
            else if (ivs < 13) autoHyp = 'Mild LVH';
            else if (ivs <= 15) autoHyp = 'Moderate LVH';
            else autoHyp = 'Severe LVH';
          }
        } else if (ivs > 0) {
          if (ivs < 11) autoHyp = 'No LVH';
          else if (ivs < 14) autoHyp = 'Mild LVH';
          else if (ivs <= 16) autoHyp = 'Moderate LVH';
          else autoHyp = 'Severe LVH';
        }
        lvHyp = autoHyp || 'No LVH';
      }

      // 4. LV Diastolic
      const lvDiaRaw = getSummaryFieldVal('lv_Diastolic');
      let lvDia = '';
      if (isCleanSummaryVal(lvDiaRaw)) {
        lvDia = lvDiaRaw;
      } else {
        const septale = parseFloat(getSummaryFieldVal('lv_septale')) || 0;
        const lateral = parseFloat(getSummaryFieldVal('lv_lateral')) || 0;
        const E = parseFloat(getSummaryFieldVal('mv_PE_Velocity')) || 0;
        const A = parseFloat(getSummaryFieldVal('mv_PA_Velocity')) || 0;
        const trg = parseFloat(getSummaryFieldVal('tv_TRG')) || 0;
        let pasp = parseFloat(getSummaryFieldVal('cp_SPAP')) || 0;
        if (pasp <= 0 && trg > 0) {
          const ivcSize = parseFloat(getSummaryFieldVal('cp_ivc_size'));
          const respVar = (getSummaryFieldVal('cp_Respiratory_variation') || '').toLowerCase().trim();
          if (!isNaN(ivcSize) && respVar && respVar !== 'none') {
            const isCollapseOver50 = respVar.includes('>50%') || respVar.includes('> 50%');
            const rap = (ivcSize <= 21) ? (isCollapseOver50 ? 3 : 8) : (isCollapseOver50 ? 8 : 15);
            pasp = Math.round((trg + rap) * 10) / 10;
          }
        }

        const hasAnyDiastolic = (septale > 0 || lateral > 0 || E > 0 || A > 0 || trg > 0 || pasp > 0);
        if (hasAnyDiastolic) {
          let hasReducedE = false;
          if (septale > 0 && septale <= 6) hasReducedE = true;
          if (lateral > 0 && lateral <= 7) hasReducedE = true;
          if (septale > 0 && lateral > 0 && ((septale + lateral) / 2) <= 6.5) hasReducedE = true;

          let hasIncreasedEe = false;
          if (E > 0) {
            if (septale > 0 && (E * 100 / septale) >= 15) hasIncreasedEe = true;
            if (lateral > 0 && (E * 100 / lateral) >= 13) hasIncreasedEe = true;
            if (septale > 0 && lateral > 0 && (E * 100 / ((septale + lateral) / 2)) >= 14) hasIncreasedEe = true;
          }

          const trVel = trg > 0 ? Math.sqrt(trg / 4) : 0;
          const hasIncreasedTrPasp = (trVel >= 2.8 || pasp >= 35);
          const eaRatio = (E > 0 && A > 0) ? (E / A) : null;

          let abnormalCount = 0;
          if (hasReducedE) abnormalCount++;
          if (hasIncreasedEe) abnormalCount++;
          if (hasIncreasedTrPasp) abnormalCount++;

          if (hasReducedE && !hasIncreasedEe && !hasIncreasedTrPasp) {
            if (eaRatio !== null && eaRatio > 0.8) {
              lvDia = 'Indeterminate diastolic function';
            } else {
              lvDia = 'Grade I diastolic dysfunction';
            }
          } else if (hasReducedE && hasIncreasedEe && hasIncreasedTrPasp) {
            if (eaRatio !== null && eaRatio >= 2.0) {
              lvDia = 'Grade III diastolic dysfunction';
            } else {
              lvDia = 'Grade II diastolic dysfunction';
            }
          } else if (abnormalCount > 0) {
            lvDia = 'Indeterminate diastolic function';
          } else {
            lvDia = 'Normal diastolic function';
          }
        } else {
          lvDia = 'Normal diastolic function';
        }
      }

      // 5. LV Clot
      const lvClotRaw = getSummaryFieldVal('lv_clot');
      let lvClot = '';
      if (isCleanSummaryVal(lvClotRaw)) {
        lvClot = lvClotRaw;
      } else {
        lvClot = 'No LV clot';
      }

      // 6. RV Size
      const rvSizeRaw = getSummaryFieldVal('rv_size');
      let rvSize = '';
      if (rvSizeRaw === 'Manual') {
        rvSize = getSummaryFieldVal('rv_size_manual');
      } else if (isCleanSummaryVal(rvSizeRaw)) {
        rvSize = rvSizeRaw;
      } else {
        const rvBasal = parseFloat(getSummaryFieldVal('rv_RV_basal_diameter')) || 0;
        const rvMid = parseFloat(getSummaryFieldVal('rv_RV_mid_diameter')) || 0;
        let autoRvSize = null;
        if (rvBasal > 0) {
          if (rvBasal <= 40) autoRvSize = 'Normal RV size';
          else if (rvBasal <= 44) autoRvSize = 'Mild RV enlargement';
          else if (rvBasal <= 49) autoRvSize = 'Moderate RV enlargement';
          else autoRvSize = 'Severe RV enlargement';
        } else if (rvMid > 0) {
          if (rvMid <= 35) autoRvSize = 'Normal RV size';
          else if (rvMid <= 38) autoRvSize = 'Mild RV enlargement';
          else if (rvMid <= 43) autoRvSize = 'Moderate RV enlargement';
          else autoRvSize = 'Severe RV enlargement';
        }
        rvSize = autoRvSize || 'Normal RV size';
      }

      // 7. RV Systolic
      const rvSysRaw = getSummaryFieldVal('rv_Systolic');
      let rvSys = '';
      const tapse = parseFloat(getSummaryFieldVal('rv_TAPSE')) || 0;
      if (isCleanSummaryVal(rvSysRaw)) {
        rvSys = rvSysRaw;
      } else {
        let autoRvSys = null;
        if (tapse > 0) {
          if (tapse > 17) autoRvSys = 'With normal systolic function';
          else if (tapse >= 14) autoRvSys = 'With mild systolic dysfunction';
          else if (tapse >= 10) autoRvSys = 'With moderate systolic dysfunction';
          else autoRvSys = 'With severe systolic dysfunction';
        }
        rvSys = autoRvSys || 'With normal systolic function';
      }

      // 8. RV Hypertrophy
      const rvHypRaw = getSummaryFieldVal('rv_Hypertrophy');
      let rvHyp = '';
      if (isCleanSummaryVal(rvHypRaw)) {
        rvHyp = rvHypRaw;
      } else {
        rvHyp = 'No RVH';
      }

      // 9. LA Size
      const laSizeRaw = getSummaryFieldVal('la_size');
      let laSize = '';
      if (isCleanSummaryVal(laSizeRaw)) {
        laSize = laSizeRaw;
      } else {
        const laVol = parseFloat(getSummaryFieldVal('la_volume')) || 0;
        const laArea = parseFloat(getSummaryFieldVal('la_area')) || 0;
        const laDiam = parseFloat(getSummaryFieldVal('la_diameter')) || 0;
        let autoLaSize = null;
        if (laVol > 0 && bsa > 0) {
          const lavi = laVol / bsa;
          if (lavi <= 34) autoLaSize = 'Normal LA size';
          else if (lavi <= 41) autoLaSize = 'Mild LA enlargement';
          else if (lavi <= 48) autoLaSize = 'Moderate LA enlargement';
          else autoLaSize = 'Severe LA enlargement';
        } else if (laArea > 0) {
          if (laArea <= 20) autoLaSize = 'Normal LA size';
          else if (laArea <= 30) autoLaSize = 'Mild LA enlargement';
          else if (laArea <= 41) autoLaSize = 'Moderate LA enlargement';
          else autoLaSize = 'Severe LA enlargement';
        } else if (laDiam > 0 && (isMale || isFemale)) {
          if (bsa > 0) {
            const ladi = laDiam / bsa;
            if (ladi <= 23) autoLaSize = 'Normal LA size';
            else autoLaSize = 'There is LA enlargement';
          } else {
            if (isMale) {
              if (laDiam <= 40) autoLaSize = 'Normal LA size';
              else autoLaSize = 'There is LA enlargement';
            } else {
              if (laDiam <= 38) autoLaSize = 'Normal LA size';
              else autoLaSize = 'There is LA enlargement';
            }
          }
        }
        laSize = autoLaSize || 'Normal LA size';
      }

      // 10. RA Size
      const raSizeRaw = getSummaryFieldVal('ra_size');
      let raSize = '';
      if (isCleanSummaryVal(raSizeRaw)) {
        raSize = raSizeRaw;
      } else {
        const raArea = parseFloat(getSummaryFieldVal('ra_area')) || 0;
        let autoRaSize = null;
        if (raArea > 0) {
          if (raArea < 18) autoRaSize = 'Normal RA size';
          else autoRaSize = 'RA enlargement';
        }
        raSize = autoRaSize || 'Normal RA size';
      }

      // Aorta Size Auto-calculation
      const avAortaRaw = getSummaryFieldVal('av_aorta');
      const annulus = parseFloat(getSummaryFieldVal('av_annulus')) || 0;
      const sinusValsalva = parseFloat(getSummaryFieldVal('av_sinus_valsalva')) || 0;
      const stJunction = parseFloat(getSummaryFieldVal('av_st_junction')) || 0;
      const proxAscAorta = parseFloat(getSummaryFieldVal('av_proximal_ascending_aorta')) || 0;

      let dilatedSegments = [];
      let anyAortaMeasured = (annulus > 0 || sinusValsalva > 0 || stJunction > 0 || proxAscAorta > 0);

      // Annulus: abs{male:>29, female:>25}, indexed{male:14, female:14}
      if (annulus > 0) {
        const absLimit = isFemale ? 25 : 29;
        const idxLimit = 14;
        const idxVal = (bsa > 0) ? (annulus / bsa) : 0;
        if (annulus > absLimit || (bsa > 0 && idxVal > idxLimit)) {
          dilatedSegments.push('annulus');
        }
      }

      // Sinuses of Valsalva: abs{male:>37, female:>33}, indexed{male:19, female:20}
      if (sinusValsalva > 0) {
        const absLimit = isFemale ? 33 : 37;
        const idxLimit = isFemale ? 20 : 19;
        const idxVal = (bsa > 0) ? (sinusValsalva / bsa) : 0;
        if (sinusValsalva > absLimit || (bsa > 0 && idxVal > idxLimit)) {
          dilatedSegments.push('sinuses of valsalva');
        }
      }

      // Sinotubular junction: abs{male:>32, female:>29}, indexed{male:17, female:17}
      if (stJunction > 0) {
        const absLimit = isFemale ? 29 : 32;
        const idxLimit = 17;
        const idxVal = (bsa > 0) ? (stJunction / bsa) : 0;
        if (stJunction > absLimit || (bsa > 0 && idxVal > idxLimit)) {
          dilatedSegments.push('sinotubular junction');
        }
      }

      // Proximal ascending aorta: abs{male:>34, female:>31}, indexed{male:17, female:19}
      if (proxAscAorta > 0) {
        const absLimit = isFemale ? 31 : 34;
        const idxLimit = isFemale ? 19 : 17;
        const idxVal = (bsa > 0) ? (proxAscAorta / bsa) : 0;
        if (proxAscAorta > absLimit || (bsa > 0 && idxVal > idxLimit)) {
          dilatedSegments.push('proximal ascending aorta');
        }
      }

      let resolvedAorta = '';
      if (avAortaRaw === 'Manual') {
        resolvedAorta = getSummaryFieldVal('av_aorta_manual');
      } else if (avAortaRaw === 'None') {
        resolvedAorta = '';
      } else if (avAortaRaw === 'Normal aortic size') {
        resolvedAorta = 'There is normal aortic size';
      } else if (avAortaRaw === 'Dilated aortic size') {
        if (dilatedSegments.length > 0) {
          resolvedAorta = `There is dilated aortic size (${dilatedSegments.join(', ')})`;
        } else {
          resolvedAorta = 'There is dilated aortic size';
        }
      } else { // Auto or empty
        if (dilatedSegments.length > 0) {
          resolvedAorta = `There is dilated aortic size (${dilatedSegments.join(', ')})`;
        } else if (anyAortaMeasured) {
          resolvedAorta = 'There is normal aortic size';
        }
      }

      return {
        bsa,
        isMale,
        isFemale,
        lvSize,
        lvSys,
        lvHyp,
        lvDia,
        lvClot,
        rvSize,
        rvSys,
        rvHyp,
        laSize,
        raSize,
        resolvedAorta,
        dilatedSegments,
        avAortaRaw
      };
    }

    function generateAutoSummaryText() {
      const bullets = [];
      const resolved = getResolvedSummaryValues();

      // 1. LV size
      const lvSizeText = resolved.lvSize;
      if (lvSizeText) {
        bullets.push(`• ${lvSizeText}`);
      }

      // LVH - moved to its own bullet line (no parentheses)
      const lvHyp = resolved.lvHyp;
      const lvOtherHyp = getSummaryFieldVal('lv_other_hyper');
      const lvhText = (lvHyp === 'Others' || lvHyp === 'Other') && lvOtherHyp ? lvOtherHyp : lvHyp;
      if (lvhText && lvhText.toLowerCase() !== 'no lvh' && lvhText.toLowerCase() !== 'none') {
        bullets.push(`• ${lvhText}`);
      }

      // LV clot if present
      const lvClot = resolved.lvClot;
      const lvOtherClot = getSummaryFieldVal('lv_other_clot');
      const clotVal = (lvClot === 'Other' && lvOtherClot ? lvOtherClot : lvClot);
      if (clotVal && clotVal.toLowerCase() !== 'no lv clot' && clotVal.toLowerCase() !== 'none') {
        bullets.push(`• ${clotVal}`);
      }

      // LV other description if present
      const lvOtherDes = getSummaryFieldVal('other_des_lv');
      if (lvOtherDes) {
        bullets.push(`• ${lvOtherDes}`);
      }

      // 2. LV EF / Systolic (EF remains in parentheses per user requirement)
      const ef = getSummaryFieldVal('lv_EF');
      let lvSys = resolved.lvSys;
      if (lvSys.toLowerCase().startsWith('with ')) {
        lvSys = lvSys.substring(5).trim();
      }
      if (lvSys) {
        lvSys = lvSys.charAt(0).toUpperCase() + lvSys.slice(1);
      }

      let lvEfText = '';
      if (ef && lvSys) {
        lvEfText = `${lvSys} (EF: ${ef}%)`;
      } else if (ef) {
        lvEfText = `EF: ${ef}%`;
      } else if (lvSys) {
        lvEfText = lvSys;
      } else {
        lvEfText = 'Normal systolic function';
      }
      bullets.push(`• ${lvEfText}`);

      // 3. LV diastolic
      bullets.push(`• ${resolved.lvDia}`);

      // 4. LV Wall Motion
      const rwmaChecked = !!getSummaryFieldVal('lv_rwma_checked');
      const globalHypoChecked = !!getSummaryFieldVal('lv_global_hypokinesia');
      let rwmaFindings = [];

      if ((rwmaChecked || globalHypoChecked) && typeof RWMA_TERRITORIES !== 'undefined') {
        const territoryMap = {
          'LAD': [],
          'RCA': [],
          'LCX': []
        };

        RWMA_TERRITORIES.forEach(group => {
          group.segments.forEach(seg => {
            const val = getSummaryFieldVal(seg.name);
            if (val && val.toLowerCase() !== 'none' && val.toLowerCase() !== 'normal') {
              let t = 'LAD';
              if (seg.label.includes('RCA')) t = 'RCA';
              else if (seg.label.includes('LCX')) t = 'LCX';
              
              const cleanSegLabel = seg.label.replace(/\s*\([A-Z]+\)\s*/, '').trim();
              territoryMap[t].push(`${val.toLowerCase()} of ${cleanSegLabel}`);
            }
          });
        });

        Object.keys(territoryMap).forEach(t => {
          if (territoryMap[t].length > 0) {
            rwmaFindings.push(`${territoryMap[t].join(', ')} in ${t} territory`);
          }
        });
      }

      const lvWith = getSummaryFieldVal('lv_with_preserved');
      let withText = '';
      if (lvWith === '1') withText = ', with preserved tissue thickness';
      else if (lvWith === '2') withText = ', with thinning and loss of tissue';

      if (globalHypoChecked && rwmaFindings.length > 0) {
        bullets.push(`• Global hypokinesia, with ${rwmaFindings.join(', ')}${withText}`);
      } else if (globalHypoChecked) {
        bullets.push(`• Global hypokinesia${withText}`);
      } else if (rwmaChecked && rwmaFindings.length > 0) {
        const rwmaText = rwmaFindings.join(', ');
        bullets.push(`• ${rwmaText.charAt(0).toUpperCase() + rwmaText.slice(1)}${withText}`);
      } else {
        bullets.push('• No regional wall motion abnormality');
      }

      // 5. RV size
      const rvSize = resolved.rvSize;
      bullets.push(`• ${rvSize}`);

      const rvHyp = resolved.rvHyp;
      const rvOtherHyp = getSummaryFieldVal('rv_other_hyper');
      const rvhText = (rvHyp === 'Other' && rvOtherHyp ? rvOtherHyp : rvHyp);
      if (rvhText && rvhText.toLowerCase() !== 'no rvh' && rvhText.toLowerCase() !== 'none') {
        bullets.push(`• ${rvhText}`);
      }

      const rvOtherDes = getSummaryFieldVal('rv_other_des');
      if (rvOtherDes) {
        bullets.push(`• ${rvOtherDes}`);
      }

      // 6. RV function
      let rvSys = resolved.rvSys;
      if (rvSys.toLowerCase().startsWith('with ')) {
        rvSys = rvSys.substring(5).trim();
      }
      if (rvSys) {
        if (!rvSys.toLowerCase().includes('rv')) {
          rvSys = rvSys.replace(/systolic/i, 'RV systolic');
        }
        rvSys = rvSys.charAt(0).toUpperCase() + rvSys.slice(1);
      }

      let rvFuncText = rvSys || 'Normal RV systolic function';
      bullets.push(`• ${rvFuncText}`);

      // 7. LA and RA enlargement if present
      const laSize = resolved.laSize;
      const laArea = parseFloat(getSummaryFieldVal('la_area'));
      const isLaEnlarged = (laSize && !laSize.toLowerCase().startsWith('normal')) || (!isNaN(laArea) && laArea > 20);

      if (isLaEnlarged) {
        const laText = (laSize && !laSize.toLowerCase().startsWith('normal')) ? laSize : 'LA enlargement';
        bullets.push(`• ${laText}`);
      }

      const laSmoky = getSummaryFieldVal('la_smoky');
      const laClot = getSummaryFieldVal('la_clot');
      const laOtherDes = getSummaryFieldVal('la_other_des');
      let laOtherNotes = [];
      if (laSmoky === '1') laOtherNotes.push('Smoky appearance');
      if (laClot === '1') {
        const laOtherClot = getSummaryFieldVal('la_other_clot');
        laOtherNotes.push(laOtherClot ? `Clot: ${laOtherClot}` : 'Clot present');
      }
      if (laOtherDes) laOtherNotes.push(laOtherDes);
      if (laOtherNotes.length > 0 && !isLaEnlarged) {
        bullets.push(`• LA: ${laOtherNotes.join(', ')}`);
      }

      const raSize = resolved.raSize;
      const raArea = parseFloat(getSummaryFieldVal('ra_area'));
      const isRaEnlarged = (raSize && !raSize.toLowerCase().startsWith('normal')) || (!isNaN(raArea) && raArea >= 18);

      if (isRaEnlarged) {
        const raText = (raSize && !raSize.toLowerCase().startsWith('normal')) ? raSize : 'RA enlargement';
        bullets.push(`• ${raText}`);
      }

      const raOtherDes = getSummaryFieldVal('ra_other_des');
      if (raOtherDes && !isRaEnlarged) {
        bullets.push(`• RA: ${raOtherDes}`);
      }

      // 8. Valves abnormalities if present
      const valves = [
        { code: 'MV', title: 'Mitral valve', prefix: 'mv', name: 'mvls', sten: 'mv_stenosis1', reg: 'mv_regurgitant', abDesc: 'mv_abnormality_description', other: 'mv_other_des' },
        { code: 'AV', title: 'Aortic valve', prefix: 'av', name: 'Aortic_valve', sten: 'av_stenosis1', reg: 'av_regurgitant', abDesc: 'av_abnormality_description', other: 'av_other_des' },
        { code: 'TV', title: 'Tricuspid valve', prefix: 'tv', name: 'Tricuspid_valve', sten: 'tv_stenosis1', reg: 'tv_regurgitant', abDesc: 'tv_abnormality_description', other: 'tv_other_des' },
        { code: 'PV', title: 'Pulmonary valve', prefix: 'pv', name: 'Pulmonary_valve', sten: 'pv_stenosis1', reg: 'pv_regurgitant', abDesc: 'pv_abnormality_description', other: 'pv_other_des' }
      ];

      let valveAbs = [];
      valves.forEach(v => {
        let vDetails = [];
        const isAbnormalVal = getSummaryFieldVal(`${v.prefix}_${v.name}`) === 'Abnormal';
        const abDesc = getSummaryFieldVal(v.abDesc);
        if (isAbnormalVal && abDesc) {
          vDetails.push(abDesc);
        }

        const sten = getSummaryFieldVal(v.sten);
        if (isCleanSummaryVal(sten) && !sten.toLowerCase().startsWith('no ')) {
          vDetails.push(sten);
        }

        const reg = getSummaryFieldVal(v.reg);
        if (isCleanSummaryVal(reg) && !reg.toLowerCase().startsWith('no ')) {
          vDetails.push(reg);
        }

        const oth = getSummaryFieldVal(v.other);
        if (oth) vDetails.push(oth);

        if (vDetails.length > 0) {
          valveAbs.push(vDetails.join(', '));
        }
      });

      if (valveAbs.length > 0) {
        bullets.push(`• ${valveAbs.join('; ')}`);
      }

      // Aorta if dilated or manual
      if (resolved.resolvedAorta) {
        if (resolved.avAortaRaw === 'Manual') {
          bullets.push(`• ${resolved.resolvedAorta}`);
        } else if (resolved.resolvedAorta.toLowerCase().includes('dilated')) {
          bullets.push(`• ${resolved.resolvedAorta}`);
        }
      }

      // 9. IVC if dilated
      const ivc = getSummaryFieldVal('cp_ivc1');
      const ivcSize = parseFloat(getSummaryFieldVal('cp_ivc_size'));
      const isIvcDilated = (ivc && ivc.toLowerCase() === 'dilated') || (!isNaN(ivcSize) && ivcSize > 21);

      if (isIvcDilated) {
        bullets.push('• Dilated IVC');
      }

      // 10. Pericardium if not normal
      const periMorph = getSummaryFieldVal('pre_Morphology');
      const isPeriMorphAbnormal = isCleanSummaryVal(periMorph) && !periMorph.toLowerCase().startsWith('normal');

      const pe = getSummaryFieldVal('pre_Pericardial_effusion');
      const isPeAbnormal = isCleanSummaryVal(pe) && !pe.toLowerCase().startsWith('no ');

      const periOther = getSummaryFieldVal('pre_Pericardium_others');

      if (isPeriMorphAbnormal || isPeAbnormal || periOther) {
        let periFindings = [];
        if (isPeriMorphAbnormal) {
          periFindings.push(periMorph);
        }
        if (isPeAbnormal) {
          const peQty = getSummaryFieldVal('pre_quantity_PE');
          let peText = pe;
          if (peQty && isCleanSummaryVal(peQty)) {
            peText = `${peQty} ${pe.toLowerCase()}`;
          }
          const peHemo = getSummaryFieldVal('pre_Hemodynamic');
          if (peHemo === 'with hemodynamic effect') {
            peText += ', with hemodynamic effect';
          }
          periFindings.push(peText);
        }
        if (periOther) {
          periFindings.push(periOther);
        }
        bullets.push(`• ${periFindings.join(', ')}`);
      }

      // Additional findings or Elevated SPAP if present
      let spap = getSummaryFieldVal('cp_SPAP');
      let spapNum = parseFloat(spap);
      const trg = parseFloat(getSummaryFieldVal('tv_TRG')) || 0;
      if (isNaN(spapNum) && trg > 0) {
        const ivcSize = parseFloat(getSummaryFieldVal('cp_ivc_size'));
        const respVar = (getSummaryFieldVal('cp_Respiratory_variation') || '').toLowerCase().trim();
        if (!isNaN(ivcSize) && respVar && respVar !== 'none') {
          const isCollapseOver50 = respVar.includes('>50%') || respVar.includes('> 50%');
          const rap = (ivcSize <= 21) ? (isCollapseOver50 ? 3 : 8) : (isCollapseOver50 ? 8 : 15);
          spapNum = Math.round((trg + rap) * 10) / 10;
          spap = String(spapNum);
        }
      }

      let cpQty = getSummaryFieldVal('cp_quantity');
      if (cpQty === 'Auto' || !cpQty) {
        if (!isNaN(spapNum)) {
          if (spapNum < 35) cpQty = 'Normal PAP';
          else if (spapNum <= 44) cpQty = 'Mild PH';
          else if (spapNum <= 59) cpQty = 'Moderate PH';
          else cpQty = 'Severe PH';
        }
      }

      if (spap || (cpQty && cpQty.includes('PH'))) {
        let phText = '';
        if (cpQty && cpQty !== 'Normal PAP' && cpQty !== 'Normal' && cpQty !== 'Top normal PAP' && cpQty !== 'Auto') {
          phText = cpQty;
        } else if (!isNaN(spapNum) && spapNum >= 35) {
          if (spapNum <= 44) phText = 'Mild PH';
          else if (spapNum <= 59) phText = 'Moderate PH';
          else phText = 'Severe PH';
        }

        if (phText) {
          bullets.push(`• ${phText}`);
        } else if (spap && !isNaN(spapNum) && spapNum >= 35) {
          bullets.push('• Pulmonary hypertension');
        }
      }

      const finding = getSummaryFieldVal('finding');
      if (finding) {
        bullets.push(`• ${finding}`);
      }

      return bullets.join('\n');
    }

    function applyAutoSummary(force = true) {
      const summaryEl = document.getElementById('summary');
      if (!summaryEl) return;

      const generated = generateAutoSummaryText();
      summaryEl.value = generated;
      window._dirty = true;
      saveToLocal();
      updateNavButtonStates();
      queueLivePreviewUpdate();

      const btn = document.getElementById('generateSummaryBtn');
      if (btn) {
        const origHtml = btn.innerHTML;
        btn.innerHTML = `<span>✓ Generated</span>`;
        btn.style.backgroundColor = '#2ecc71';
        setTimeout(() => {
          btn.innerHTML = origHtml;
          btn.style.backgroundColor = '#3182ce';
        }, 1200);
      }
    }

    function clearSummary() {
      const summaryEl = document.getElementById('summary');
      if (!summaryEl) return;
      summaryEl.value = '';
      window._dirty = true;
      saveToLocal();
      updateNavButtonStates();
      queueLivePreviewUpdate();
    }

    // Load last entered doctors for autocompletion
    async function loadDoctors() {
      try {
        const response = await fetch(ECHO_ROUTES.doctors, { headers: { 'Accept': 'application/json' } });
        if (response.ok) {
          const doctors = await response.json();
          const datalist = document.getElementById('doctors_list');
          if (datalist && Array.isArray(doctors)) {
            datalist.innerHTML = '';
            doctors.forEach(doc => {
              const option = document.createElement('option');
              option.value = doc;
              datalist.appendChild(option);
            });
          }
        }
      } catch (error) {
        console.error('Error fetching doctors:', error);
      }
    }

    window.addEventListener('load', () => {
      initSectionNav();
      buildRwmaFields();
      loadSaved();
      setupManualToggle('lv_size', 'lv_size_manual', 'lv_manual_row');
      setupManualToggle('rv_size', 'rv_size_manual', 'rv_manual_row');
      setupManualToggle('av_aorta', 'av_aorta_manual', 'av_aorta_manual_row');
      updateValveAbnormalityToggles();
      updateNavButtonStates();
      loadDoctors();

      const genBtn = document.getElementById('generateSummaryBtn');
      if (genBtn) {
        genBtn.addEventListener('click', () => applyAutoSummary(true));
      }
      const clrBtn = document.getElementById('clearSummaryBtn');
      if (clrBtn) {
        clrBtn.addEventListener('click', clearSummary);
      }

      // Initialize any date fields to today if empty
      const today = new Date().toISOString().split('T')[0];
      const dateInputs = document.querySelectorAll('input[type="date"]');
      dateInputs.forEach(input => {
        if (!input.value) {
          input.value = today;
        }
      });

      // Restore preview state on load
      const savedPreviewState = localStorage.getItem('echoForm_live_preview');
      if (savedPreviewState === 'true') {
        setTimeout(() => {
          toggleLivePreview(true);
        }, 300);
      }
    });
  </script>
</body>

</html>
