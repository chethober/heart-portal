<?php
/*
 * Styles for the echo study form (studies/form.blade.php), scoped to #echo-app so they never
 * leak into the dashboard around it. Colours follow the dashboard's Tailwind palette
 * (gray for structure, indigo for selection, green/red for section status).
 */
?>
<style>
  #echo-app {
    --gap: 12px;
    color: #111827;
    line-height: 1.4;
  }

  #echo-app *,
  #echo-app *::before,
  #echo-app *::after {
    box-sizing: border-box;
  }

  #echo-app form#echoForm {
    width: 100%;
    margin: 0;
  }

  #echo-app .row {
    display: flex;
    flex-wrap: wrap;
    gap: var(--gap);
    margin-bottom: 12px;
    width: 100%;
  }

  #echo-app .field {
    display: flex;
    flex-direction: column;
    flex: 1 1 100%;
    min-width: 0;
  }

  @media (min-width: 640px) {
    #echo-app .field {
      flex: 1 1 calc(50% - var(--gap));
    }
  }

  @media (min-width: 1024px) {
    #echo-app .field {
      flex: 1 1 calc(25% - var(--gap));
      min-width: 200px;
    }
  }

  #echo-app label {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 4px;
    color: #374151;
  }

  #echo-app .req {
    color: #dc2626;
  }

  #echo-app input[type="text"],
  #echo-app input[type="number"],
  #echo-app input[type="time"],
  #echo-app input[type="date"],
  #echo-app textarea,
  #echo-app select {
    width: 100%;
    min-height: 42px;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background-color: #fff;
    font-size: 0.875rem;
    color: #111827;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    transition: border-color 0.15s, box-shadow 0.15s;
  }

  #echo-app select {
    padding-right: 2.5rem;
  }

  @media (max-width: 640px) {
    #echo-app input[type="text"],
    #echo-app input[type="number"],
    #echo-app input[type="time"],
    #echo-app input[type="date"],
    #echo-app textarea,
    #echo-app select {
      font-size: 16px; /* Prevents auto-zoom on iOS */
    }
  }

  #echo-app input[type="text"]:focus,
  #echo-app input[type="number"]:focus,
  #echo-app input[type="time"]:focus,
  #echo-app input[type="date"]:focus,
  #echo-app textarea:focus,
  #echo-app select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 1px #6366f1;
  }

  #echo-app input[type="checkbox"] {
    border-radius: 0.25rem;
    border-color: #d1d5db;
    color: #4f46e5;
  }

  #echo-app textarea {
    min-height: 90px;
    resize: vertical;
    line-height: 1.5;
  }

  #echo-app details {
    margin: 12px 0;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
    padding: 4px 14px;
  }

  #echo-app details[open] {
    padding-bottom: 14px;
  }

  #echo-app summary {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    padding: 8px 0;
    min-height: 42px;
    display: flex;
    align-items: center;
    gap: 8px;
    user-select: none;
    list-style: none;
  }

  #echo-app summary::-webkit-details-marker {
    display: none;
  }

  #echo-app summary::before {
    content: "";
    width: 0.45rem;
    height: 0.45rem;
    border-right: 2px solid #9ca3af;
    border-bottom: 2px solid #9ca3af;
    transform: rotate(-45deg);
    transition: transform 0.15s;
  }

  #echo-app details[open] > summary::before {
    transform: rotate(45deg);
  }

  #echo-app .small {
    font-size: 0.75rem;
    color: #6b7280;
  }

  #echo-app .hidden {
    display: none !important;
  }

  /* Buttons, matching the dashboard's primary and secondary buttons. */
  #echo-app .ef-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 8px 16px;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background-color 0.15s, border-color 0.15s, color 0.15s;
  }

  #echo-app .ef-btn-primary {
    background: #1f2937;
    border: 1px solid transparent;
    color: #fff;
  }

  #echo-app .ef-btn-primary:hover {
    background: #374151;
  }

  #echo-app .ef-btn-secondary {
    background: #fff;
    border: 1px solid #d1d5db;
    color: #374151;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  }

  #echo-app .ef-btn-secondary:hover {
    background: #f9fafb;
  }

  #echo-app .ef-btn:focus-visible,
  #echo-app .section-nav-btn:focus-visible,
  #echo-app .add-patient-btn:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: 2px;
  }

  #echo-app .preview-toggle.active {
    background: #eef2ff;
    border-color: #6366f1;
    color: #4338ca;
  }

  #echo-app .ef-btn-mini {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
  }

  #echo-app .ef-btn-mini:hover {
    background: #f9fafb;
  }

  #echo-app .ef-btn-mini-accent {
    border-color: #c7d2fe;
    background: #eef2ff;
    color: #4338ca;
  }

  #echo-app .ef-btn-mini-accent:hover {
    background: #e0e7ff;
  }

  #echo-app .ef-btn-mini.is-done {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #15803d;
  }

  #echo-app .controls {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
    width: 100%;
  }

  @media (max-width: 640px) {
    #echo-app .controls {
      flex-direction: column-reverse;
    }

    #echo-app .controls .ef-btn {
      width: 100%;
    }
  }

  /* Editing / amending banner */
  #echo-app .edit-banner {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 16px;
    padding: 12px 16px;
    border: 1px solid #c7d2fe;
    border-radius: 0.5rem;
    background: #eef2ff;
  }

  #echo-app .edit-banner.is-amending {
    border-color: #fde68a;
    background: #fffbeb;
  }

  #echo-app .edit-banner-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }

  #echo-app .edit-banner-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
  }

  /* Section navigation: one chip per section, showing whether it has been filled in. */
  #echo-app .section-nav-wrapper {
    margin: 20px 0 4px;
    width: 100%;
  }

  #echo-app .section-nav-container {
    display: flex;
    gap: 8px;
    width: 100%;
    overflow-x: auto;
    padding: 2px 2px 8px;
    scrollbar-width: thin;
    scrollbar-color: #d1d5db transparent;
    -webkit-overflow-scrolling: touch;
  }

  #echo-app .section-nav-btn {
    position: relative;
    flex: 1 0 auto;
    min-width: 64px;
    min-height: 40px;
    padding: 6px 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 9999px;
    color: #4b5563;
    cursor: pointer;
    transition: background-color 0.15s, border-color 0.15s, color 0.15s;
    user-select: none;
  }

  #echo-app .section-nav-btn::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 9999px;
    background: #d1d5db;
    flex-shrink: 0;
  }

  #echo-app .section-nav-btn:hover {
    background: #f9fafb;
  }

  #echo-app .section-nav-btn.status-unfilled::before {
    background: #f87171;
  }

  #echo-app .section-nav-btn.status-filled {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #15803d;
  }

  #echo-app .section-nav-btn.status-filled::before {
    background: #22c55e;
  }

  #echo-app .section-nav-btn.active {
    background: #eef2ff !important;
    border-color: #6366f1 !important;
    color: #4338ca !important;
    box-shadow: 0 0 0 1px #6366f1;
  }

  #echo-app .section-nav-btn .btn-abbr {
    font-size: 0.8125rem;
    font-weight: 600;
  }

  /* Persian Datepicker Mobile Viewport Optimization */
  .datepicker-plot-area {
    max-width: calc(100vw - 20px) !important;
    font-family: inherit !important;
    z-index: 100000 !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    border-radius: 8px !important;
  }

  /* Multi-patient tabs */
  #echo-app .patient-bar-wrapper {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 8px;
    margin: 0 0 16px;
    width: 100%;
  }

  #echo-app .patient-tabs-container {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    flex: 1 1 auto;
    min-width: 0;
    padding-bottom: 2px;
    -webkit-overflow-scrolling: touch;
  }

  #echo-app .patient-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 6px 12px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.15s, color 0.15s;
    user-select: none;
    min-height: 38px;
  }

  #echo-app .patient-tab:hover {
    background: #f3f4f6;
    color: #1f2937;
  }

  #echo-app .patient-tab.active {
    background: #eef2ff;
    border-color: #6366f1;
    color: #4338ca;
    font-weight: 600;
  }

  #echo-app .patient-tab-close {
    background: transparent;
    border: 0;
    color: currentColor;
    font-size: 16px;
    line-height: 1;
    padding: 2px 4px;
    cursor: pointer;
    opacity: 0.6;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    min-height: 20px;
  }

  #echo-app .patient-tab-close:hover {
    opacity: 1;
    background: rgba(0, 0, 0, 0.08);
  }

  #echo-app .add-patient-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #1f2937;
    color: #fff;
    border: 0;
    border-radius: 0.375rem;
    padding: 8px 14px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
    white-space: nowrap;
    min-height: 38px;
    flex-shrink: 0;
  }

  #echo-app .add-patient-btn:hover {
    background: #374151;
  }

  /* Form sections (one visible at a time) */
  #echo-app .form-section {
    display: none;
    margin: 12px 0;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f9fafb;
    padding: 16px;
    animation: tabFadeIn 0.25s ease-in-out;
  }

  @media (max-width: 640px) {
    #echo-app .form-section {
      padding: 12px 10px;
    }
  }

  #echo-app .form-section.active {
    display: block;
  }

  #echo-app .form-section-header {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 12px;
    color: #1f2937;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 8px;
  }

  #echo-app .subsection {
    margin-top: 14px;
    border-top: 1px solid #e5e7eb;
    padding-top: 12px;
    width: 100%;
  }

  #echo-app .subsection-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 0.875rem;
  }

  @keyframes tabFadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
  }

  #echo-app .valve-status-field {
    flex: 1 1 calc(33.333% - var(--gap));
    min-width: 240px;
  }

  @media (max-width: 768px) {
    #echo-app .valve-status-field {
      flex: 1 1 100%;
      min-width: 100%;
    }
  }

  /* Alert dialog (its messages are in Persian, hence right-to-left) */
  #echo-app .custom-modal {
    border: none;
    border-radius: 0.75rem;
    padding: 0;
    max-width: min(480px, calc(100vw - 32px));
    width: calc(100vw - 32px);
    max-height: 88vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    background: #fff;
    direction: rtl;
    text-align: right;
    font-family: 'Vazirmatn', 'Figtree', ui-sans-serif, system-ui, sans-serif;
    opacity: 0;
    transform: scale(0.95);
    transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    margin: auto;
  }

  #echo-app .custom-modal[open] {
    opacity: 1;
    transform: scale(1);
  }

  #echo-app .custom-modal::backdrop {
    background: rgba(107, 114, 128, 0.75);
  }

  #echo-app .modal-content {
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
  }

  #echo-app .modal-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    font-weight: bold;
  }

  #echo-app .modal-icon.success { background-color: #dcfce7; color: #16a34a; }
  #echo-app .modal-icon.success::after { content: "✓"; }
  #echo-app .modal-icon.error { background-color: #fee2e2; color: #dc2626; }
  #echo-app .modal-icon.error::after { content: "✕"; }
  #echo-app .modal-icon.warning { background-color: #fef3c7; color: #d97706; }
  #echo-app .modal-icon.warning::after { content: "!"; }

  #echo-app .modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
    text-align: center;
    width: 100%;
  }

  #echo-app .modal-message {
    font-size: 0.875rem;
    color: #4b5563;
    line-height: 1.6;
    text-align: center;
    margin: 0;
    width: 100%;
  }

  #echo-app .modal-message ul {
    text-align: right;
    margin: 8px 0 0;
    padding-right: 24px;
    list-style-type: disc;
  }

  #echo-app .modal-message li {
    margin-bottom: 4px;
  }

  #echo-app .modal-actions {
    display: flex;
    justify-content: center;
    width: 100%;
    margin-top: 8px;
  }

  #echo-app .modal-btn {
    background-color: #1f2937;
    color: #fff;
    border: none;
    border-radius: 0.375rem;
    padding: 10px 24px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    text-align: center;
    min-height: 42px;
  }

  #echo-app .modal-btn:hover { background-color: #374151; }
  #echo-app .modal-btn.success-btn { background-color: #16a34a; }
  #echo-app .modal-btn.success-btn:hover { background-color: #15803d; }
  #echo-app .modal-btn.error-btn { background-color: #dc2626; }
  #echo-app .modal-btn.error-btn:hover { background-color: #b91c1c; }
  #echo-app .modal-btn.warning-btn { background-color: #d97706; }
  #echo-app .modal-btn.warning-btn:hover { background-color: #b45309; }

  /* Live preview: the form and the report side by side inside the dashboard card. */
  body.dragging iframe {
    pointer-events: none !important;
  }

  body.dragging #form-container,
  body.dragging #preview-container {
    transition: none !important;
  }

  #echo-app #app-layout {
    display: flex;
    width: 100%;
    gap: 0;
  }

  #echo-app #form-container {
    flex: 1;
    width: 100%;
    min-width: 0;
  }

  /* The split widths set from script use "0 0 N%"; let both panes shrink around the resizer. */
  #echo-app #app-layout.preview-active #form-container,
  #echo-app #app-layout.preview-active #preview-container {
    flex-shrink: 1 !important;
    min-width: 0;
  }

  #echo-app #app-layout.preview-active #form-container {
    flex: 0 0 50%;
    padding-right: 16px;
  }

  #echo-app #preview-resizer {
    width: 8px;
    flex: 0 0 8px;
    margin-right: 16px;
    cursor: col-resize;
    background: #f3f4f6;
    border-radius: 9999px;
    position: sticky;
    top: 1rem;
    height: calc(100vh - 2rem);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
    user-select: none;
  }

  #echo-app #preview-resizer:hover,
  #echo-app #preview-resizer.dragging {
    background: #e5e7eb;
  }

  #echo-app #preview-resizer::after {
    content: "";
    width: 2px;
    height: 32px;
    border-radius: 9999px;
    background: #9ca3af;
  }

  #echo-app #preview-container {
    flex: 0 0 50%;
    position: sticky;
    top: 1rem;
    align-self: flex-start;
    height: calc(100vh - 2rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f9fafb;
  }

  #echo-app #preview-iframe {
    flex: 1;
    width: 100%;
    height: 100%;
    border: 0;
    background: #fff;
  }

  #echo-app .preview-header {
    background: #f9fafb;
    color: #1f2937;
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5e7eb;
    user-select: none;
  }

  #echo-app .preview-title-group {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  #echo-app .preview-icon {
    color: #6366f1;
  }

  #echo-app .preview-title {
    font-size: 0.875rem;
    font-weight: 600;
  }

  #echo-app .preview-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #dcfce7;
    color: #15803d;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
  }

  #echo-app .preview-status-dot {
    width: 6px;
    height: 6px;
    background-color: #22c55e;
    border-radius: 50%;
    display: inline-block;
    animation: pulse-dot 1.8s infinite ease-in-out;
  }

  @keyframes pulse-dot {
    0% { transform: scale(0.85); opacity: 0.5; }
    50% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(0.85); opacity: 0.5; }
  }

  #echo-app .preview-actions {
    display: flex;
    gap: 4px;
  }

  #echo-app .preview-btn {
    background: transparent;
    border: 0;
    color: #6b7280;
    padding: 6px;
    border-radius: 0.375rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.15s, color 0.15s;
  }

  #echo-app .preview-btn:hover {
    background: #e5e7eb;
    color: #111827;
  }

  #echo-app .preview-btn.close-btn:hover {
    background: #fee2e2;
    color: #b91c1c;
  }

  @media print {
    #echo-app #preview-container,
    #echo-app #preview-resizer {
      display: none !important;
    }

    #echo-app #app-layout.preview-active #form-container {
      flex: 0 0 100% !important;
    }
  }

  /* Tablets and phones: stack the preview under the form. */
  @media (max-width: 1024px) {
    #echo-app #app-layout.preview-active {
      flex-direction: column;
    }

    #echo-app #app-layout.preview-active #form-container {
      flex: 0 0 auto !important;
      padding-right: 0;
    }

    #echo-app #preview-resizer {
      display: none !important;
    }

    #echo-app #app-layout.preview-active #preview-container {
      flex: 0 0 auto !important;
      width: 100% !important;
      height: 70vh;
      margin-top: 16px;
      position: relative;
      top: 0;
    }
  }
</style>
