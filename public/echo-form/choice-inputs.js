/**
 * Choice inputs for the clinical forms (#echo-app): upgrades each <select> in place by how many
 * options it has, keeping the select itself (visually hidden) as the field's value so form
 * submission, autosave and the forms' own scripts work unchanged.
 *
 *   1–2 options  → a toggle (segmented switch)
 *   3–4 options  → a choicebox (one chip per option)
 *   5+ options   → a dropdown listing the options with check marks
 *
 * A select[multiple] gets checkboxes instead: chips up to four options, a dropdown of checkboxes
 * from five. data-widget="dropdown" asks for a dropdown whatever the count. Selects marked
 * data-native, and those with no real options (only a blank), are left alone. Clicking the chosen option again clears it when the select has a blank ("") option.
 *
 * Scripts set selects' values directly (el.value = ..., option.selected = ..., form.reset()), which
 * fires no event, so those setters are wrapped per element to repaint the control.
 */
(function () {
    'use strict';

    const ROOT = '#echo-app';
    const CHIP_LIMIT = 4;
    const SEARCH_FROM = 10;
    const proto = (ctor, prop) => Object.getOwnPropertyDescriptor(ctor.prototype, prop);
    const selectValue = proto(HTMLSelectElement, 'value');
    const selectIndex = proto(HTMLSelectElement, 'selectedIndex');
    const optionSelected = proto(HTMLOptionElement, 'selected');
    let uid = 0;

    function choices(select) {
        return Array.from(select.options).filter((option) => option.value !== '' && ! option.hidden);
    }

    function kind(select) {
        if (select.dataset.widget === 'dropdown') return 'dropdown';
        const count = choices(select).length;
        if (select.multiple) return count > CHIP_LIMIT ? 'dropdown' : 'chips';
        if (count <= 2) return 'toggle';
        return count <= CHIP_LIMIT ? 'chips' : 'dropdown';
    }

    function emit(select) {
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function choose(select, option) {
        if (select.disabled || option.disabled) return;
        const blank = Array.from(select.options).find((o) => o.value === '');
        if (select.multiple) {
            optionSelected.set.call(option, ! optionSelected.get.call(option));
        } else if (optionSelected.get.call(option) && blank) {
            selectValue.set.call(select, '');
        } else {
            selectValue.set.call(select, option.value);
        }
        emit(select);
        select._choice.paint();
    }

    function button(className, option, select) {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = className;
        el.textContent = option.textContent.trim();
        el.setAttribute('role', select.multiple ? 'checkbox' : 'radio');
        el.addEventListener('click', () => choose(select, option));
        el._option = option;
        return el;
    }

    /** Toggle and choicebox: one button per option, all visible. */
    function buildButtons(select, ui, type) {
        ui.className = type === 'toggle' ? 'cx-toggle' : 'cx-chips';
        ui.setAttribute('role', select.multiple ? 'group' : 'radiogroup');
        const items = choices(select).map((option) => ui.appendChild(button(type === 'toggle' ? 'cx-toggle-btn' : 'cx-chip', option, select)));

        return () => items.forEach((el) => {
            const on = optionSelected.get.call(el._option);
            el.classList.toggle('is-on', on);
            el.setAttribute('aria-checked', on ? 'true' : 'false');
            el.disabled = select.disabled || el._option.disabled;
        });
    }

    /** Dropdown: a button showing the choice and a list of options with check marks or checkboxes. */
    function buildDropdown(select, ui) {
        ui.className = 'cx-dropdown';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'cx-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        const text = trigger.appendChild(document.createElement('span'));
        text.className = 'cx-trigger-text';

        const panel = document.createElement('div');
        panel.className = 'cx-panel';
        panel.hidden = true;
        panel.setAttribute('role', 'listbox');
        if (select.multiple) panel.setAttribute('aria-multiselectable', 'true');

        let search = null;
        if (choices(select).length >= SEARCH_FROM) {
            search = panel.appendChild(document.createElement('input'));
            search.type = 'text';
            search.className = 'cx-search no-voice';
            search.placeholder = 'Search…';
            search.addEventListener('input', (event) => {
                event.stopPropagation();
                const term = search.value.trim().toLowerCase();
                items.forEach((el) => { el.hidden = term !== '' && ! el.textContent.toLowerCase().includes(term); });
            });
            search.addEventListener('change', (event) => event.stopPropagation());
        }

        const items = choices(select).map((option) => {
            const el = button('cx-option', option, select);
            el.setAttribute('role', 'option');
            const mark = document.createElement('span');
            mark.className = select.multiple ? 'cx-box' : 'cx-check';
            mark.setAttribute('aria-hidden', 'true');
            el.prepend(mark);
            // Keep focus where it is on press, so the list is not closed by focusout before the click.
            el.addEventListener('mousedown', (event) => event.preventDefault());
            el.addEventListener('click', () => { if (! select.multiple) close(true); });
            return panel.appendChild(el);
        });

        ui.append(trigger, panel);

        const visible = () => items.filter((el) => ! el.hidden && ! el.disabled);
        function open() {
            document.querySelectorAll('.cx-dropdown.is-open').forEach((other) => other !== ui && other._close());
            panel.hidden = false;
            ui.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            // Open upwards when there is no room below.
            const below = window.innerHeight - trigger.getBoundingClientRect().bottom;
            ui.classList.toggle('is-up', below < Math.min(panel.scrollHeight, 280) + 12 && trigger.getBoundingClientRect().top > below);
            (search || items.find((el) => el.classList.contains('is-on')) || visible()[0])?.focus();
        }
        function close(focus = false) {
            if (panel.hidden) return;
            panel.hidden = true;
            ui.classList.remove('is-open', 'is-up');
            trigger.setAttribute('aria-expanded', 'false');
            if (search) { search.value = ''; items.forEach((el) => { el.hidden = false; }); }
            if (focus) trigger.focus();
        }
        ui._close = close;

        trigger.addEventListener('click', () => (panel.hidden ? open() : close()));
        trigger.addEventListener('keydown', (event) => {
            if (['ArrowDown', 'ArrowUp'].includes(event.key)) { event.preventDefault(); open(); }
            else if (event.key === 'Escape') close();
        });
        panel.addEventListener('keydown', (event) => {
            const list = visible();
            const at = list.indexOf(document.activeElement);
            if (event.key === 'Escape') { event.preventDefault(); close(true); }
            else if (event.key === 'ArrowDown') { event.preventDefault(); list[Math.min(at + 1, list.length - 1)]?.focus(); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); (at <= 0 && search ? search : list[Math.max(at - 1, 0)])?.focus(); }
            else if (event.key === 'Enter' && document.activeElement === search) { event.preventDefault(); list[0]?.click(); }
            else if (event.key === 'Tab') close();
        });
        ui.addEventListener('focusout', (event) => { if (! ui.contains(event.relatedTarget)) close(); });

        return () => {
            const chosen = Array.from(select.selectedOptions).filter((option) => option.value !== '');
            text.textContent = chosen.length === 0 ? '—'
                : chosen.length <= 2 ? chosen.map((option) => option.textContent.trim()).join(', ')
                : chosen.length + ' selected';
            trigger.classList.toggle('is-empty', chosen.length === 0);
            trigger.disabled = select.disabled;
            items.forEach((el) => {
                const on = optionSelected.get.call(el._option);
                el.classList.toggle('is-on', on);
                el.setAttribute('aria-selected', on ? 'true' : 'false');
                el.disabled = el._option.disabled;
            });
        };
    }

    // Repaints are batched: several setters often run back to back while a draft is restored.
    function schedule(select) {
        const state = select._choice;
        if (! state || state.queued) return;
        state.queued = true;
        queueMicrotask(() => { state.queued = false; state.paint(); });
    }

    function watchSetters(select) {
        const repaint = () => schedule(select);
        Object.defineProperty(select, 'value', { configurable: true, get() { return selectValue.get.call(this); }, set(v) { selectValue.set.call(this, v); repaint(); } });
        Object.defineProperty(select, 'selectedIndex', { configurable: true, get() { return selectIndex.get.call(this); }, set(v) { selectIndex.set.call(this, v); repaint(); } });
        Array.from(select.options).forEach((option) => watchOption(option, select));
    }

    function watchOption(option, select) {
        if (option._choiceWatched) return;
        option._choiceWatched = true;
        Object.defineProperty(option, 'selected', { configurable: true, get() { return optionSelected.get.call(this); }, set(v) { optionSelected.set.call(this, v); schedule(select); } });
    }

    function enhance(select) {
        if (select._choice || select.dataset.native !== undefined || choices(select).length === 0) return;

        const ui = document.createElement('div');
        const type = kind(select);
        ui.id = 'cx-' + (++uid);
        const label = select.id && select.closest('form, ' + ROOT)?.querySelector('label[for="' + CSS.escape(select.id) + '"]');
        if (label) {
            if (! label.id) label.id = ui.id + '-label';
            ui.setAttribute('aria-labelledby', label.id);
            // The select is hidden, so its label points at the visible control instead.
            if (! label._choiceBound) {
                label._choiceBound = true;
                label.addEventListener('click', (event) => { event.preventDefault(); select._choice?.ui.querySelector('button:not(:disabled)')?.focus(); });
            }
        }

        const paint = type === 'dropdown' ? buildDropdown(select, ui) : buildButtons(select, ui, type);
        select._choice = {
            ui,
            type,
            queued: false,
            paint() {
                paint();
                ui.hidden = select.hidden || select.style.display === 'none';
            },
        };
        select.classList.add('cx-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');
        select.after(ui);
        watchSetters(select);
        select._choice.paint();

        if (select._choiceObserved) return;
        select._choiceObserved = true;
        select.addEventListener('change', () => schedule(select));
        // Options added or removed later (the admission lookup, "Manual" values) rebuild the control.
        new MutationObserver(() => rebuild(select)).observe(select, { childList: true });
        new MutationObserver(() => schedule(select)).observe(select, { attributes: true, attributeFilter: ['disabled', 'hidden', 'style'] });
    }

    function rebuild(select) {
        if (! select._choice) return;
        select._choice.ui.remove();
        select._choice = null;
        select.classList.remove('cx-native');
        select.removeAttribute('aria-hidden');
        select.removeAttribute('tabindex');
        enhance(select);
    }

    function scan(root) {
        if (root.matches?.('select')) enhance(root);
        root.querySelectorAll?.('select').forEach(enhance);
    }

    function start() {
        const app = document.querySelector(ROOT);
        if (! app) return;
        scan(app);
        // Selects created by the forms' scripts (e.g. the echo form's RWMA segments).
        new MutationObserver((records) => records.forEach((record) => record.addedNodes.forEach((node) => {
            if (node.nodeType === 1 && ! node.classList.contains('cx-toggle') && ! node.classList.contains('cx-chips') && ! node.classList.contains('cx-dropdown')) scan(node);
        }))).observe(app, { childList: true, subtree: true });
        // form.reset() restores defaults without events.
        app.addEventListener('reset', (event) => setTimeout(() => event.target.querySelectorAll('select').forEach((select) => select._choice && select._choice.paint())));
        document.addEventListener('click', (event) => {
            document.querySelectorAll('.cx-dropdown.is-open').forEach((ui) => { if (! ui.contains(event.target)) ui._close(); });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
