@php
    /**
     * Create or edit a procedure study, built from config/procedures/{procedure}.php. Laid out like
     * the echo form (studies/form.blade.php) and styled by its stylesheet, which is scoped to
     * #echo-app: patient details, a section bar showing which sections are filled in, one section
     * at a time, and a live report preview beside the form. Like the echo form it stays in its
     * clinical language whatever the interface language. Fields with "show_if" are shown only while
     * the field they depend on has that value; the server clears them otherwise.
     *
     * Choice fields are drawn as toggles, choiceboxes or dropdowns by public/echo-form/choice-inputs.js.
     * Small fields shown only for another field's value sit inside that field's cell (partials/field).
     * Runs of yes/no fields in one group don't exclude each other, so they are shown together as
     * one group of checkboxes (partials/checkbox-group), still saved as their own columns.
     *
     * @var string $procedure
     * @var \App\Models\ProcedureStudy|null $study
     * @var array<string, mixed> $values  Saved values when editing.
     */
    use App\Support\Procedures;

    // Retired fields are kept in the table but no longer shown; "top" sections join the patient row.
    $sections = collect(Procedures::sections($procedure))
        ->map(fn (array $section) => [...$section, 'fields' => array_filter($section['fields'], fn (array $field) => ! ($field['retired'] ?? false))])
        ->filter(fn (array $section) => $section['fields'] !== []);
    $top = array_merge([], ...$sections->filter(fn (array $section) => $section['top'] ?? false)->pluck('fields')->all());
    $sections = $sections->reject(fn (array $section) => $section['top'] ?? false)->values()->all();
    $fields = Procedures::formFields($procedure);
    $value = fn (string $name) => old($name, $values[$name] ?? null);
    $rtl = Procedures::dir($procedure) === 'rtl';

    // Only the fields others depend on need to be tracked in the browser.
    $watched = collect($fields)->pluck('show_if.0')->filter()->unique()
        ->mapWithKeys(fn (string $name) => [$name => (string) ($value($name) ?? '')]);
    $visible = fn (array $field) => isset($field['show_if']) ? "v['{$field['show_if'][0]}'] === '{$field['show_if'][1]}'" : null;
    $model = fn (string $name) => $watched->has($name) ? "x-model=\"v['{$name}']\"" : '';

    // The section to open first: the first with an error, else the first.
    $firstError = collect($sections)->search(fn (array $section) => collect($section['fields'])->keys()->contains(fn ($name) => $errors->has($name)));
    $active = $firstError === false ? 0 : $firstError;
    $title = Procedures::label($procedure);
    $amending = $study?->isSigned() ?? false;
@endphp

<x-dashboard-layout :title="$title">
    <link rel="stylesheet" href="/echo-form/persianDatepicker/css/persianDatepicker-default.css">
    <script src="/echo-form/persianDatepicker/js/jquery-1.10.1.min.js"></script>
    <script src="/echo-form/persianDatepicker/js/persianDatepicker.min.js"></script>
    <script src="/echo-form/choice-inputs.js" defer></script>
    <script src="/echo-form/form-layout.js" defer></script>
    <link rel="stylesheet" href="/echo-form/voice-input.css">
    <script src="/echo-form/voice-input.js" defer></script>
    @include('studies.partials.form-styles')

    <div id="echo-app" dir="{{ $rtl ? 'rtl' : 'ltr' }}" lang="{{ $rtl ? 'fa' : 'en' }}"
         x-data="procedureForm({
             watched: @js($watched),
             section: {{ $active }},
             previewUrl: @js(route('procedures.preview', $procedure)),
             // Unsaved drafts are kept per patient tab in this browser, like the echo form's.
             storageKey: @js($study ? null : 'procedureForm_patients_v1:'.$procedure.':'.auth()->user()->hospital_id.':'.auth()->id()),
             // A form arriving with values (a patient link, or failed validation) opens them in a new tab.
             arrivedWithValues: @js(! $study && (filled($value('nationalID')) || $errors->any())),
         })">
    <div id="app-layout" :class="{ 'preview-active': preview }">
    <div id="form-container">
        @unless ($study)
            {{-- Multi-patient tabs: one unsaved draft per patient, saved in this browser as you type. --}}
            <div class="patient-bar-wrapper">
                <div id="patientTabsContainer" class="patient-tabs-container">
                    <template x-for="(tab, index) in tabs" :key="tab.id">
                        <div class="patient-tab" :class="{ 'active': tab.id === activeTab }">
                            <span class="patient-tab-title" x-text="tabTitle(tab, index)" x-on:click="switchTab(tab.id)"></span>
                            <button type="button" class="patient-tab-close" title="Remove patient draft" x-show="tabs.length > 1" x-on:click.stop="removeTab(tab.id)">&times;</button>
                        </div>
                    </template>
                </div>
                <button type="button" class="add-patient-btn" x-on:click="addTab()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>New Patient</span>
                </button>
            </div>
        @endunless
        <form method="POST" id="procedureForm" x-ref="form" action="{{ $study ? route('procedures.update', [$procedure, $study]) : route('procedures.store', $procedure) }}"
              autocomplete="off" x-on:input.debounce.400ms="refresh(); autosave()" x-on:change="refresh(); autosave(); mark()" x-on:submit="submitted()">
            @csrf
            @if ($study)
                <input type="hidden" name="_method" value="PUT" x-ref="method">
            @endif

            @if ($study)
                <div class="edit-banner {{ $amending ? 'is-amending' : '' }}">
                    <div class="edit-banner-head">
                        <p class="edit-banner-title">
                            {{ $amending ? 'Amending signed report' : 'Editing draft' }} #{{ $study->id }}
                            — {{ $study->patient->first_name.' '.$study->patient->last_name }}
                        </p>
                        <a href="{{ route('procedures.show', [$procedure, $study]) }}" class="ef-btn ef-btn-secondary">{{ __('Back to report') }}</a>
                    </div>
                    @if ($amending)
                        <div class="field">
                            <label for="amendment_reason">Reason for amendment <span class="req">*</span></label>
                            <textarea id="amendment_reason" name="amendment_reason" required style="min-height:40px">{{ old('amendment_reason') }}</textarea>
                            @error('amendment_reason') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="edit-banner is-amending" role="alert">
                    <p class="edit-banner-title">Please correct the {{ $errors->count() }} highlighted {{ Str::plural('field', $errors->count()) }}.</p>
                </div>
            @endif

            <div class="form-section-header" style="border:0;margin-bottom:4px">{{ $title }}</div>
            <div class="row">
                @include('clinical.partials.patient-fields', ['values' => $values, 'admissions' => $admissions ?? collect(), 'admission' => $value('admission_id') ?? request('admission')])
                @foreach ($top as $name => $field)
                    @include('procedures.partials.field', ['sub' => false, 'children' => []])
                @endforeach
            </div>

            {{-- One chip per section, green once something in it is filled in, red with an error. --}}
            <div class="section-nav-wrapper">
                {{-- Sections with short names ("abbr") fit on one line; the full title shows on hover. --}}
                @php $abbreviated = collect($sections)->every(fn (array $section) => isset($section['abbr'])); @endphp
                <div class="section-nav-container {{ $abbreviated ? 'is-one-line' : '' }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
                    @foreach ($sections as $index => $section)
                        @php $hasErrors = collect($section['fields'])->keys()->contains(fn ($name) => $errors->has($name)); @endphp
                        <button type="button" class="section-nav-btn" title="{{ $section['title'] }}"
                                :class="{ 'active': section === {{ $index }}, 'status-filled': filled[{{ $index }}] && ! {{ $hasErrors ? 'true' : 'false' }}, 'status-unfilled': {{ $hasErrors ? 'true' : 'false' }} }"
                                x-on:click="section = {{ $index }}">
                            <span class="btn-abbr">{{ $section['abbr'] ?? Str::limit($section['title'], 28) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach ($sections as $index => $section)
                <div id="sec-{{ $index }}" class="form-section {{ $index === $active ? 'active' : '' }}" data-section="{{ $index }}" :class="{ 'active': section === {{ $index }} }">
                    <div class="form-section-header">{{ $section['title'] }}</div>
                    <div class="row">
                        @php
                            // Small fields shown only for a value of another field in this section are drawn
                            // inside that field's cell (partials/field), so revealing them moves nothing,
                            // unless the field says "nest" => false (it switches a whole run of fields).
                            $children = [];
                            foreach ($section['fields'] as $name => $field) {
                                $parent = $field['show_if'][0] ?? null;
                                if ($parent !== null && isset($section['fields'][$parent]) && $section['fields'][$parent]['type'] !== 'boolean'
                                    && ($section['fields'][$parent]['nest'] ?? true)
                                    && in_array($field['type'], ['text', 'number', 'choice', 'date', 'time'], true)) {
                                    $children[$parent][] = $name;
                                }
                            }
                            $nested = array_merge([], ...array_values($children));

                            // Consecutive yes/no fields of one group become a single checkbox group.
                            $items = [];
                            foreach (array_diff_key($section['fields'], array_flip($nested)) as $name => $field) {
                                $last = array_key_last($items);
                                if ($field['type'] === 'boolean' && $last !== null && $items[$last]['booleans'] && $items[$last]['group'] === ($field['group'] ?? null)) {
                                    $items[$last]['fields'][$name] = $field;
                                } else {
                                    $items[] = ['booleans' => $field['type'] === 'boolean', 'group' => $field['group'] ?? null, 'fields' => [$name => $field]];
                                }
                            }
                        @endphp
                        @foreach ($items as $item)
                            @if ($item['group'] !== null && $item['group'] !== ($previousGroup ?? null))
                                <div class="subsection-title" style="flex: 1 1 100%; margin: 8px 0 0">{{ $item['group'] }}</div>
                            @endif
                            @php $previousGroup = $item['group']; @endphp

                            @if (count($item['fields']) > 1)
                                @include('procedures.partials.checkbox-group', ['fields' => $item['fields']])
                                @continue
                            @endif
                            @php $name = array_key_first($item['fields']); $field = $item['fields'][$name]; @endphp

                            @include('procedures.partials.field', ['sub' => false])
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="controls">
                <button type="button" class="ef-btn ef-btn-secondary preview-toggle" :class="{ 'active': preview }" :aria-pressed="preview" x-on:click="toggle()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span>Live Preview</span>
                </button>
                @if ($study)
                    <a href="{{ route('procedures.show', [$procedure, $study]) }}" class="ef-btn ef-btn-secondary">Cancel</a>
                @else
                    <button type="button" class="ef-btn ef-btn-secondary" x-on:click="clearForm()">Reset</button>
                    <button type="button" class="ef-btn ef-btn-secondary" x-on:click="autosave(true)" x-text="savedFlash ? 'Saved' : 'Save Locally'">Save Locally</button>
                @endif
                <button type="submit" class="ef-btn ef-btn-primary">{{ $amending ? 'Save Amendment' : ($study ? 'Save Changes' : 'Save Draft') }}</button>
            </div>
        </form>
    </div>

    <div id="preview-container" x-show="preview" x-cloak>
        <div class="preview-header no-print">
            <div class="preview-title-group">
                <svg class="preview-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <span class="preview-title">Live Report Preview</span>
                <span class="preview-status-pill"><span class="preview-status-dot"></span> Live Sync</span>
            </div>
            <div class="preview-actions">
                <button type="button" class="preview-btn" title="Force Refresh" x-on:click="refresh(true)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                </button>
                <button type="button" class="preview-btn" title="Open Report in New Tab" x-on:click="submitPreview('_blank')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/>
                    </svg>
                </button>
                <button type="button" class="preview-btn close-btn" title="Close Preview" x-on:click="toggle(false)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
        <iframe id="preview-iframe" name="procedure-preview" title="Live Report Preview"></iframe>
    </div>
    </div>
    </div>

    @include('clinical.partials.patient-lookup')
    <script>
        /**
         * The procedure form's behaviour, matching the echo form: show_if values (v), the open
         * section and which sections are filled in, multi-patient tabs whose drafts are saved in
         * this browser as you type (new studies only), and the live preview, which posts the form
         * to the preview route into the iframe without the PUT override an edit form carries.
         */
        function procedureForm(options) {
            return {
                v: options.watched,
                section: options.section,
                filled: [],
                preview: false,
                tabs: [],
                activeTab: null,
                savedFlash: false,
                init() {
                    this.loadTabs();
                    this.mark();
                    this.datepickers();
                    try { this.toggle(localStorage.getItem('procedureForm_live_preview') === 'true'); } catch (e) {}
                },
                get form() { return this.$refs.form; },

                // --- Multi-patient drafts -------------------------------------------------
                store() {
                    try { return JSON.parse(localStorage.getItem(options.storageKey)) || { tabs: [], active: null }; } catch (e) { return { tabs: [], active: null }; }
                },
                persist() {
                    try { localStorage.setItem(options.storageKey, JSON.stringify({ tabs: this.tabs, active: this.activeTab })); } catch (e) {}
                },
                newTab(data = {}) {
                    return { id: 'p_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6), data };
                },
                loadTabs() {
                    if (! options.storageKey) return;
                    const saved = this.store();
                    this.tabs = saved.tabs.length ? saved.tabs : [this.newTab()];
                    this.activeTab = this.tabs.some((tab) => tab.id === saved.active) ? saved.active : this.tabs[0].id;

                    if (options.arrivedWithValues) {
                        const current = this.tabs.find((tab) => tab.id === this.activeTab);
                        if (Object.values(current.data).some((value) => value !== '')) {
                            const tab = this.newTab();
                            this.tabs.push(tab);
                            this.activeTab = tab.id;
                        }
                        this.autosave();
                    } else {
                        this.fill(this.tabs.find((tab) => tab.id === this.activeTab).data);
                    }
                    this.persist();
                },
                tabTitle(tab, index) {
                    const name = [tab.data.first_name, tab.data.last_name].filter(Boolean).join(' ');
                    return name || 'Patient ' + (index + 1);
                },
                values() {
                    const data = {};
                    new FormData(this.form).forEach((value, key) => {
                        if (['_token', '_method', 'amendment_reason'].includes(key)) return;
                        // Multi-value fields (e.g. diagnoses[]) are kept as lists.
                        if (key.endsWith('[]')) (data[key] ??= []).push(value); else data[key] = value;
                    });
                    return data;
                },
                autosave(flash = false) {
                    if (! options.storageKey) return;
                    const tab = this.tabs.find((tab) => tab.id === this.activeTab);
                    if (! tab) return;
                    tab.data = this.values();
                    this.persist();
                    if (flash === true) { this.savedFlash = true; setTimeout(() => this.savedFlash = false, 900); }
                },
                switchTab(id) {
                    if (id === this.activeTab) return;
                    this.autosave();
                    this.activeTab = id;
                    this.fill(this.tabs.find((tab) => tab.id === id).data);
                    this.persist();
                },
                addTab() {
                    this.autosave();
                    const tab = this.newTab();
                    this.tabs.push(tab);
                    this.activeTab = tab.id;
                    this.fill({});
                    this.persist();
                },
                removeTab(id) {
                    if (this.tabs.length <= 1) return;
                    this.tabs = this.tabs.filter((tab) => tab.id !== id);
                    if (this.activeTab === id) {
                        this.activeTab = this.tabs[0].id;
                        this.fill(this.tabs[0].data);
                    }
                    this.persist();
                },
                clearForm() {
                    this.fill({});
                    this.autosave();
                },
                // A saved study's draft is done with: the next visit starts from the remaining tabs.
                submitted() {
                    if (! options.storageKey) return;
                    this.tabs = this.tabs.filter((tab) => tab.id !== this.activeTab);
                    if (! this.tabs.length) this.tabs = [this.newTab()];
                    this.activeTab = this.tabs[0].id;
                    this.persist();
                },
                fill(data) {
                    Array.from(this.form.elements).forEach((el) => {
                        if (! el.name || ['_token', '_method', 'amendment_reason'].includes(el.name) || el.type === 'button' || el.type === 'submit') return;
                        if (el.type === 'checkbox' && el.name.endsWith('[]')) {
                            el.checked = (data[el.name] || []).includes(el.value);
                            el.dispatchEvent(new Event('change'));
                            return;
                        }
                        if (el.type === 'checkbox') { el.checked = !! data[el.name]; return; }
                        if (el.tagName === 'SELECT' && el.name === 'admission_id') { el.dataset.selected = data[el.name] || ''; }
                        el.value = data[el.name] ?? '';
                    });
                    Object.keys(this.v).forEach((name) => {
                        const el = this.form.elements[name];
                        this.v[name] = el ? (el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value) : '';
                    });
                    this.section = 0;
                    this.mark();
                    this.refresh();
                    // Checkbox groups recount what is ticked; their boxes were set without events.
                    window.dispatchEvent(new CustomEvent('choices-sync'));
                    window.patientLookup?.check(false, data.admission_id);
                },

                // --- Sections, dates and preview ------------------------------------------
                mark() {
                    this.filled = Array.from(this.form.querySelectorAll('[data-section]')).map((el) =>
                        Array.from(el.querySelectorAll('input, select, textarea')).some((input) =>
                            input.type === 'checkbox' ? input.checked : input.value.trim() !== ''));
                },
                datepickers() {
                    if (! window.jQuery || ! jQuery.fn.persianDatepicker) return;
                    jQuery('#dob, [data-jalali]').each(function () {
                        const el = this;
                        jQuery(el).persianDatepicker({
                            observer: true,
                            format: 'YYYY/MM/DD',
                            initialValue: false,
                            onSelect: () => ['input', 'change'].forEach((type) => el.dispatchEvent(new Event(type, { bubbles: true }))),
                        });
                    });
                },
                toggle(on = ! this.preview) {
                    this.preview = on;
                    try { localStorage.setItem('procedureForm_live_preview', on ? 'true' : 'false'); } catch (e) {}
                    if (on) this.$nextTick(() => this.refresh(true));
                },
                refresh(force = false) {
                    if (this.preview || force === true) this.submitPreview('procedure-preview');
                },
                submitPreview(target) {
                    const form = this.form;
                    const action = form.action;
                    if (this.$refs.method) this.$refs.method.disabled = true;
                    form.action = options.previewUrl;
                    form.target = target;
                    form.noValidate = true;
                    form.submit();
                    form.action = action;
                    form.target = '';
                    form.noValidate = false;
                    if (this.$refs.method) this.$refs.method.disabled = false;
                },
            };
        }

        /** A dropdown of checkboxes (partials/checkbox-group), summarising what is ticked. */
        function checkboxGroup() {
            return {
                open: false,
                picked: [],
                init() { this.count(); },
                count() {
                    this.picked = Array.from(this.$refs.panel.querySelectorAll('input:checked')).map((input) => input.nextElementSibling.textContent.trim());
                },
                summary() {
                    if (! this.picked.length) return '—';
                    return this.picked.length <= 2 ? this.picked.join(', ') : this.picked.length + ' selected';
                },
                close(focus = false) {
                    if (! this.open) return;
                    this.open = false;
                    if (focus) this.$refs.trigger.focus();
                },
            };
        }
    </script>
</x-dashboard-layout>
