/**
 * Field layout for the clinical forms (#echo-app). Within each .row, a run of fields between
 * full-width items (text areas, checkbox groups, headings, anything that isn't a .field) is split
 * into as few lines as fit (at most four columns, each at least MIN_WIDTH wide), with the fields
 * shared out evenly between the lines, so six fields make two lines of three rather than four
 * and two. Fields keep their order.
 *
 * Chip groups (choiceboxes, checkbox chips) are balanced the same way: as many equal columns as
 * the widest chip allows, shared out evenly, so four chips that don't fit on one line make two
 * lines of two rather than three and a lone one. The section bar is shared out like fields (28
 * sections make lines of 6, 6, 6, 5 and 5), its buttons capped at NAV_MAX wide so long titles
 * are cut short.
 *
 * A field marked data-follows="<id>" (an "Other" box, a manual value) is shown only for some value
 * of that field, so it is moved into that field's cell, under it: revealing it moves nothing else.
 *
 * The pass re-runs when fields are shown or hidden (show_if, "Other" boxes, sections) and on resize.
 */
(function () {
    'use strict';

    const MAX_COLS = 4;
    const MIN_WIDTH = 210;
    const NAV_MAX = 170;

    function wide(el) {
        return ! el.classList.contains('field')
            || el.classList.contains('cx-group-field')
            || /flex:\s*1 1 100%/.test(el.getAttribute('style') || '')
            || el.querySelector('textarea, .diagnosis-picker') !== null;
    }

    function shown(el) {
        return getComputedStyle(el).display !== 'none';
    }

    // Shares items out over as few lines of at most `fit` as possible, as evenly as possible,
    // setting each item's --cols to the length of its line (but at least `least`).
    function spread(items, fit, least = 1) {
        if (items.length === 0) return;
        const lines = Math.ceil(items.length / fit);
        const base = Math.floor(items.length / lines);
        const longer = items.length % lines;
        let index = 0;
        for (let line = 0; line < lines; line++) {
            const length = base + (line < longer ? 1 : 0);
            const cols = String(Math.max(length, least));
            items.slice(index, index + length).forEach((item) => {
                if (item.style.getPropertyValue('--cols') !== cols) item.style.setProperty('--cols', cols);
            });
            index += length;
        }
    }

    function layoutRow(row, width) {
        const gap = parseFloat(getComputedStyle(row).columnGap) || 12;
        const fit = Math.max(1, Math.min(MAX_COLS, Math.floor((width + gap) / (MIN_WIDTH + gap))));
        let run = [];
        // Spread the run over as few lines as fit, as evenly as possible: 14 fields at four
        // across make lines of 4, 4, 3 and 3 rather than 4, 4, 4 and 2.
        const flush = () => {
            // A field alone (or nearly) keeps at most twice the usual width rather than the whole line.
            spread(run, fit, Math.ceil(fit / 2));
            run = [];
        };

        Array.from(row.children).forEach((el) => {
            const isWide = wide(el);
            if (el.classList.contains('is-wide') !== isWide) el.classList.toggle('is-wide', isWide);
            if (! shown(el)) return;
            if (isWide) flush(); else run.push(el);
        });
        flush();
    }

    // The widest chip's natural width, measured once with the chips on one line.
    function chipWidth(group) {
        if (! group.dataset.chipWidth) {
            group.classList.remove('is-balanced');
            group.classList.add('is-measuring');
            group.dataset.chipWidth = String(Math.max(...Array.from(group.children, (chip) => chip.offsetWidth)));
            group.classList.remove('is-measuring');
        }
        return Number(group.dataset.chipWidth);
    }

    function layoutChips(group, width) {
        const count = group.children.length;
        if (count === 0) return;
        const gap = parseFloat(getComputedStyle(group).columnGap) || 6;
        const nav = group.classList.contains('section-nav-container');
        const widest = nav ? Math.min(chipWidth(group), NAV_MAX) : chipWidth(group);
        const fit = Math.max(1, Math.min(count, Math.floor((width + gap) / (widest + gap))));
        if (nav) {
            spread(Array.from(group.children), fit);
            if (! group.classList.contains('is-balanced')) group.classList.add('is-balanced');
            return;
        }
        const cols = String(Math.ceil(count / Math.ceil(count / fit)));
        if (group.style.getPropertyValue('--chip-cols') !== cols) group.style.setProperty('--chip-cols', cols);
        if (! group.classList.contains('is-balanced')) group.classList.add('is-balanced');
    }

    function layout(app) {
        app.querySelectorAll('.row').forEach((row) => {
            // Rows in closed sections have no width yet; they are laid out when opened.
            const width = row.clientWidth;
            if (width > 0) layoutRow(row, width);
        });
        app.querySelectorAll('.cx-chips, .cx-group, .section-nav-container').forEach((group) => {
            const width = group.clientWidth;
            if (width > 0) layoutChips(group, width);
        });
    }

    function start() {
        const app = document.querySelector('#echo-app');
        if (! app) return;
        app.querySelectorAll('.field[data-follows]').forEach((field) => {
            const cell = document.getElementById(field.dataset.follows)?.closest('.field');
            if (cell && cell !== field) { field.classList.add('is-sub'); cell.append(field); }
        });
        let queued = false;
        const schedule = () => {
            if (queued) return;
            queued = true;
            requestAnimationFrame(() => { queued = false; layout(app); });
        };

        layout(app);
        new MutationObserver(schedule).observe(app, { subtree: true, childList: true, attributes: true, attributeFilter: ['class', 'style', 'hidden', 'open'] });
        new ResizeObserver(schedule).observe(app);
        app.addEventListener('toggle', schedule, true);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
