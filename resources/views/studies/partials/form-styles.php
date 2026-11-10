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
    background: #fff;
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

  /* Fields are laid out by public/echo-form/form-layout.js, which gives each run of fields an
   * even column count (--cols); fields on a shorter last line grow to fill it. Text areas,
   * checkbox groups and anything that isn't a field take the whole line. */
  #echo-app .row {
    align-items: flex-start;
  }

  #echo-app .row > * {
    flex: 1 1 100%;
    min-width: 0;
  }

  #echo-app .field {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  /* A field shown only for some value of another sits in that field's cell, under it. */
  #echo-app .field.is-sub {
    margin-top: 10px;
    padding-inline-start: 10px;
    border-inline-start: 2px solid #c7d2fe;
  }

  #echo-app .row > .field:not(.is-wide) {
    --cell: calc((100% - (var(--cols, 1) - 1) * var(--gap)) / var(--cols, 1));
    flex: 1 1 calc(var(--cell) - 0.5px);
    max-width: var(--cell);
  }

  @media (min-width: 640px) {
    #echo-app .row > .field:not(.is-wide) {
      --cols: 2;
    }
  }

  @media (min-width: 1024px) {
    #echo-app .row > .field:not(.is-wide) {
      --cols: 4;
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
    background: #4f46e5;
    border: 1px solid transparent;
    color: #fff;
  }

  #echo-app .ef-btn-primary:hover {
    background: #4338ca;
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

  /* Wraps; form-layout.js shares the buttons out evenly over the lines (--cols). A bar marked
     .is-one-line (the echo and procedure forms) stays on one line and scrolls sideways instead. */
  #echo-app .section-nav-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    width: 100%;
    padding: 2px 2px 8px;
  }

  #echo-app .section-nav-container.is-balanced > .section-nav-btn {
    --cell: calc((100% - (var(--cols, 1) - 1) * 8px) / var(--cols, 1));
    flex: 1 1 calc(var(--cell) - 0.5px);
    max-width: var(--cell);
  }

  /* Short section names: one line, buttons sharing the width, scrolling sideways if too narrow. */
  #echo-app .section-nav-container.is-one-line {
    flex-wrap: nowrap;
    gap: 4px;
    overflow-x: auto;
  }

  #echo-app .section-nav-container.is-one-line > .section-nav-btn {
    flex: 1 0 auto;
    gap: 4px;
    padding: 6px 8px;
  }

  #echo-app .section-nav-wrapper.is-scrollable {
    display: flex;
    align-items: center;
    gap: 6px;
    direction: ltr;
  }

  #echo-app .is-scrollable > .section-nav-container {
    flex: 1;
    min-width: 0;
    overscroll-behavior-x: contain;
    scrollbar-width: thin;
    --fade-left: #000;
    --fade-right: #000;
    -webkit-mask-image: linear-gradient(to right, var(--fade-left), #000 24px, #000 calc(100% - 24px), var(--fade-right));
    mask-image: linear-gradient(to right, var(--fade-left), #000 24px, #000 calc(100% - 24px), var(--fade-right));
  }

  #echo-app .more-left > .section-nav-container {
    --fade-left: transparent;
  }

  #echo-app .more-right > .section-nav-container {
    --fade-right: transparent;
  }

  #echo-app .section-scroll-arrow {
    display: none;
    flex: 0 0 44px;
    height: 44px;
    border: 1px solid #d1d5db;
    border-radius: 9999px;
    background: #fff;
    color: #4f46e5;
    font-size: 26px;
    touch-action: manipulation;
  }

  #echo-app .has-overflow > .section-scroll-arrow {
    display: block;
  }

  #echo-app .section-scroll-arrow:disabled {
    opacity: 0.25;
    cursor: default;
  }

  #echo-app .section-scroll-arrow:focus-visible {
    outline: 2px solid #4f46e5;
    outline-offset: 2px;
  }

  #echo-app .section-nav-container.is-measuring {
    flex-wrap: nowrap;
    width: max-content;
  }

  #echo-app .is-measuring > .section-nav-btn {
    flex: none;
  }

  #echo-app .section-nav-btn {
    position: relative;
    flex: 1 0 auto;
    min-width: 0;
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
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
    background: #4f46e5;
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
    background: #4338ca;
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

  /* A "collapse" section's groups: one folded part per heading, one open at a time. */
  #echo-app .collapse-part {
    flex: 1 1 100%;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    background: #fff;
  }

  #echo-app .collapse-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 10px 12px;
    border: 0;
    background: none;
    color: #374151;
    font: inherit;
    font-size: 0.875rem;
    font-weight: 600;
    text-align: start;
    cursor: pointer;
  }

  #echo-app .collapse-trigger::after {
    content: "";
    width: 1.25em;
    height: 1.25em;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 8l4 4 4-4'/%3E%3C/svg%3E") center / contain no-repeat;
    transition: transform 0.15s ease;
  }

  #echo-app .collapse-part.is-open .collapse-trigger::after {
    transform: rotate(180deg);
  }

  #echo-app .collapse-trigger:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: -2px;
  }

  #echo-app .collapse-part > .row {
    padding: 0 12px 12px;
  }

  @keyframes tabFadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
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
    background-color: #4f46e5;
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

  #echo-app .modal-btn:hover { background-color: #4338ca; }
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
  /* --- Choice inputs (public/echo-form/choice-inputs.js) ----------------------------------
   * The select stays in the form, visually hidden, as the field's value. */
  #echo-app select.cx-native {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    min-height: 0 !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden;
    clip: rect(0 0 0 0);
    border: 0 !important;
    opacity: 0;
    pointer-events: none;
  }

  #echo-app .cx-toggle,
  #echo-app .cx-chips,
  #echo-app .cx-dropdown {
    font-size: 0.875rem;
  }

  #echo-app .cx-toggle[hidden],
  #echo-app .cx-chips[hidden],
  #echo-app .cx-dropdown[hidden] {
    display: none;
  }

  /* Toggle: a segmented switch, for one or two options. */
  #echo-app .cx-toggle {
    display: flex;
    width: 100%;
    min-height: 42px;
    padding: 3px;
    gap: 2px;
    border: 1px solid #d1d5db;
    border-radius: 9999px;
    background: #f3f4f6;
  }

  #echo-app .cx-toggle-btn {
    flex: 1 1 0;
    min-width: 0;
    padding: 6px 16px;
    border: 0;
    border-radius: 9999px;
    background: transparent;
    color: #4b5563;
    font: inherit;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background-color 0.15s, color 0.15s, box-shadow 0.15s;
  }

  #echo-app .cx-toggle-btn:hover:not(:disabled):not(.is-on) {
    color: #111827;
  }

  #echo-app .cx-toggle-btn.is-on {
    background: #4f46e5;
    color: #fff;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.15);
  }

  /* Choicebox: one chip per option. */
  #echo-app .cx-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 42px;
    align-items: center;
  }

  /* Balanced by form-layout.js into equal columns (--chip-cols); measured on one line first. */
  #echo-app .cx-chips.is-balanced,
  #echo-app .cx-group.is-balanced {
    display: grid;
    grid-template-columns: repeat(var(--chip-cols), minmax(0, 1fr));
  }

  #echo-app .cx-chips.is-measuring,
  #echo-app .cx-group.is-measuring {
    flex-wrap: nowrap;
    width: max-content;
  }

  #echo-app .is-measuring > .cx-chip {
    flex: none;
  }

  #echo-app .cx-chip {
    display: inline-flex;
    flex: 1 1 auto;
    min-width: 0;
    justify-content: center;
    align-items: center;
    gap: 6px;
    min-height: 34px;
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: #fff;
    color: #374151;
    font: inherit;
    cursor: pointer;
    text-align: start;
    transition: background-color 0.15s, border-color 0.15s, color 0.15s;
  }

  #echo-app .cx-chip:hover:not(:disabled):not(.is-on) {
    border-color: #9ca3af;
    background: #f9fafb;
  }

  #echo-app .cx-chip.is-on {
    border-color: #4f46e5;
    background: #eef2ff;
    color: #3730a3;
    font-weight: 500;
  }

  #echo-app .cx-chip[role="checkbox"]::before {
    content: "";
    flex: none;
    width: 14px;
    height: 14px;
    border: 1px solid #9ca3af;
    border-radius: 3px;
    background: #fff;
  }

  #echo-app .cx-chip[role="checkbox"].is-on::before {
    border-color: #4f46e5;
    background: #4f46e5 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E") center / 12px no-repeat;
  }

  #echo-app .cx-toggle-btn:focus-visible,
  #echo-app .cx-chip:focus-visible,
  #echo-app .cx-trigger:focus-visible,
  #echo-app .cx-option:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: 1px;
  }

  #echo-app .cx-toggle-btn:disabled,
  #echo-app .cx-chip:disabled,
  #echo-app .cx-trigger:disabled,
  #echo-app .cx-option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  /* Dropdown: for six options or more. */
  #echo-app .cx-dropdown {
    position: relative;
    width: 100%;
  }

  #echo-app .cx-trigger {
    display: flex;
    align-items: center;
    width: 100%;
    min-height: 42px;
    padding: 8px 2.5rem 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 8l4 4 4-4'/%3E%3C/svg%3E") right 0.75rem center / 1.25em no-repeat;
    color: #111827;
    font: inherit;
    text-align: start;
    cursor: pointer;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  }

  #echo-app [dir="rtl"] .cx-trigger,
  #echo-app[dir="rtl"] .cx-trigger {
    padding: 8px 12px 8px 2.5rem;
    background-position: left 0.75rem center;
  }

  #echo-app .cx-dropdown.is-open .cx-trigger {
    border-color: #6366f1;
    box-shadow: 0 0 0 1px #6366f1;
  }

  #echo-app .cx-trigger-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  #echo-app .cx-trigger.is-empty .cx-trigger-text {
    color: #9ca3af;
  }

  #echo-app .cx-panel {
    position: absolute;
    z-index: 40;
    inset-inline: 0;
    top: calc(100% + 4px);
    min-width: 220px;
    max-height: 280px;
    overflow-y: auto;
    padding: 4px;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  }

  #echo-app .cx-dropdown.is-up .cx-panel {
    top: auto;
    bottom: calc(100% + 4px);
  }

  #echo-app .cx-panel[hidden] {
    display: none;
  }

  #echo-app .cx-search {
    width: 100%;
    min-height: 36px !important;
    margin-bottom: 4px;
  }

  #echo-app .cx-option {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 7px 10px;
    border: 0;
    border-radius: 0.375rem;
    background: transparent;
    color: #374151;
    font: inherit;
    text-align: start;
    cursor: pointer;
  }

  #echo-app .cx-option[hidden] {
    display: none;
  }

  #echo-app .cx-option:hover:not(:disabled),
  #echo-app .cx-option:focus-visible {
    background: #f3f4f6;
  }

  #echo-app .cx-option.is-on {
    color: #3730a3;
    font-weight: 500;
  }

  #echo-app .cx-check,
  #echo-app .cx-box {
    flex: none;
    width: 16px;
    height: 16px;
  }

  #echo-app .cx-box {
    border: 1px solid #9ca3af;
    border-radius: 3px;
    background: #fff;
  }

  #echo-app .cx-option.is-on .cx-check {
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%234f46e5' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E") center / 14px no-repeat;
  }

  #echo-app .cx-option.is-on .cx-box {
    border-color: #4f46e5;
    background: #4f46e5 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3.5 8.5l3 3 6-7'/%3E%3C/svg%3E") center / 12px no-repeat;
  }

  /* A run of yes/no fields that don't exclude each other, shown as one group of checkboxes. */
  #echo-app .cx-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }

  #echo-app .cx-group > label.cx-chip {
    margin: 0;
    font-size: 0.875rem;
  }

  /* Chips sized to their text rather than sharing the line (the echo form's wall-motion boxes). */
  #echo-app .cx-group.is-compact > label.cx-chip {
    flex: none;
  }

  #echo-app .cx-group > label.cx-chip input[type="checkbox"] {
    margin: 0;
  }

  #echo-app .cx-group > label.cx-chip:has(input:checked) {
    border-color: #4f46e5;
    background: #eef2ff;
    color: #3730a3;
    font-weight: 500;
  }

  #echo-app .cx-group-dropdown .cx-panel label.cx-option {
    margin: 0;
  }

  #echo-app .cx-group-dropdown .cx-panel label.cx-option input[type="checkbox"] {
    margin: 0;
  }

  #echo-app .cx-group-dropdown .cx-panel label.cx-option:has(input:checked) {
    color: #3730a3;
    font-weight: 500;
  }
</style>
