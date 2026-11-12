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
     * Runs of yes/no fields (and "checkbox" choices) in one group don't exclude each other, so they
     * are shown together as one group of checkboxes (partials/checkbox-group), still saved as their
     * own columns.
     *
     * @var string $procedure
     * @var \App\Models\ProcedureStudy|null $study
     * @var array<string, mixed> $values  Saved values when editing.
     */
    use App\Support\Procedures;

    // Retired fields are kept in the table but no longer shown; "top" sections join the patient row.
    $sharedDateField = config('procedures.'.$procedure.'.date_field');
    $sections = collect(Procedures::sections($procedure))
        ->map(fn (array $section) => [...$section, 'fields' => array_filter($section['fields'], fn (array $field, string $name) => ! ($field['retired'] ?? false) && $name !== $sharedDateField, ARRAY_FILTER_USE_BOTH)])
        ->filter(fn (array $section) => $section['fields'] !== []);
    $top = array_merge([], ...$sections->filter(fn (array $section) => $section['top'] ?? false)->pluck('fields')->all());
    $sections = $sections->reject(fn (array $section) => $section['top'] ?? false)->values()->all();
    // Keep the clinical field definitions and report vessel names; combine grafts in the editor.
    if ($procedure === 'angiography') {
        $tabs = [];
        $grafts = null;
        foreach ($sections as $section) {
            if (str_contains($section['title'], ' on ')) {
                $grafts ??= count($tabs);
                $tabs[$grafts] ??= ['title' => 'Grafts', 'collapse' => true, 'fields' => []];
                foreach ($section['fields'] as $name => $field) {
                    $tabs[$grafts]['fields'][$name] = [...$field, 'panel' => $section['title']];
                }
            } else {
                $tabs[] = $section;
            }
        }
        $sections = $tabs;
    }
    $fields = Procedures::formFields($procedure);
    $value = fn (string $name) => old($name, $values[$name] ?? null);
    $rtl = Procedures::dir($procedure) === 'rtl';

    // Only the fields others depend on need to be tracked in the browser.
    $watched = collect($fields)->pluck('show_if.0')->filter()->unique()
        ->mapWithKeys(fn (string $name) => [$name => (string) ($value($name) ?? '')]);
    // A field is shown only while the field it depends on is shown too.
    $visible = function (array $field) use (&$visible, $fields): ?string {
        if (! isset($field['show_if'])) {
            return null;
        }
        [$parent, $code] = $field['show_if'];
        $condition = "v['{$parent}'] === '{$code}'";
        $outer = isset($fields[$parent]) ? $visible($fields[$parent]) : null;

        return $outer === null ? $condition : "{$outer} && {$condition}";
    };
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
             storageKey: @js($study ? null : 'procedureForm_patients_v1:'.$procedure.':'.auth()->user()->hospital_id.':'.auth()->id().':'.($patientContext['patient_id'] ?? 'legacy')),
             // A form arriving with values (a patient link, or failed validation) opens them in a new tab.
             arrivedWithValues: @js($errors->any()),
             sharedValues: @js(collect($values)->only([...config('echo.patient_fields'), 'admission_id', $sharedDateField])->all()),
         })">
    <div id="app-layout" :class="{ 'preview-active': preview }">
    <div id="form-container">
        @unless ($study)
            @include('clinical.partials.patient-taskbar')
        @endunless
        <form method="POST" id="procedureForm" x-ref="form" action="{{ $study ? route('procedures.update', [$procedure, $study]) : route('procedures.store', [$procedure, ...array_filter(['context' => request('context')])]) }}"
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
            @include('clinical.partials.patient-summary', ['sharedValues' => $values, 'sharedDateField' => $sharedDateField])
            <div class="row">
                @foreach ($top as $name => $field)
                    @include('procedures.partials.field', ['sub' => false, 'children' => []])
                @endforeach
            </div>

            {{-- One chip per section, green once something in it is filled in, red with an error. --}}
            <div class="section-nav-wrapper is-scrollable" data-scroll-tabs>
                <button type="button" class="section-scroll-arrow" data-scroll-direction="-1" aria-label="Scroll tabs left" disabled>&lsaquo;</button>
                <div class="section-nav-container is-one-line" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
                    @foreach ($sections as $index => $section)
                        @php $hasErrors = collect($section['fields'])->keys()->contains(fn ($name) => $errors->has($name)); @endphp
                        <button type="button" class="section-nav-btn" title="{{ $section['title'] }}"
                                :class="{ 'active': section === {{ $index }}, 'status-filled': filled[{{ $index }}] && ! {{ $hasErrors ? 'true' : 'false' }}, 'status-unfilled': {{ $hasErrors ? 'true' : 'false' }} }"
                                x-on:click="section = {{ $index }}">
                            <span class="btn-abbr">{{ $section['abbr'] ?? Str::limit($section['title'], 28) }}</span>
                        </button>
                    @endforeach
                </div>
                <button type="button" class="section-scroll-arrow" data-scroll-direction="1" aria-label="Scroll tabs right" disabled>&rsaquo;</button>
            </div>

            @foreach ($sections as $index => $section)
                @php
                    // Small fields shown only for a value of another field in this section are drawn
                    // inside that field's cell (partials/field), so revealing them moves nothing,
                    // unless the field says "nest" => false (it switches a whole run of fields).
                    $children = [];
                    foreach ($section['fields'] as $name => $field) {
                        $parent = $field['show_if'][0] ?? null;
                        if ($parent !== null && isset($section['fields'][$parent]) && $section['fields'][$parent]['type'] !== 'boolean'
                            && ($section['fields'][$parent]['widget'] ?? null) !== 'checkbox' && ($section['fields'][$parent]['nest'] ?? true)
                            && in_array($field['type'], ['text', 'number', 'choice', 'date', 'time'], true)) {
                            $children[$parent][] = $name;
                        }
                    }
                    $nested = array_merge([], ...array_values($children));

                    // Consecutive yes/no fields of one group become a single checkbox group. Fields
                    // shown only while a checkbox here is ticked don't break its run: they follow it,
                    // captioned with that checkbox's label when they are checkboxes themselves.
                    $isBox = fn (array $field) => $field['type'] === 'boolean' || ($field['widget'] ?? null) === 'checkbox';
                    $items = [];
                    $dependents = [];
                    $runOf = [];
                    foreach (array_diff_key($section['fields'], array_flip($nested)) as $name => $field) {
                        $box = $isBox($field);
                        $parent = $field['show_if'][0] ?? null;
                        if ($parent !== null && isset($runOf[$parent])) {
                            $list = &$dependents[$runOf[$parent]];
                            $last = $list === null ? null : array_key_last($list);
                            if ($box && $last !== null && $list[$last]['booleans'] && $list[$last]['parent'] === $parent) {
                                $list[$last]['fields'][$name] = $field;
                            } else {
                                $list[] = ['booleans' => $box, 'group' => $field['panel'] ?? $field['group'] ?? null, 'parent' => $parent,
                                    'caption' => $box ? $section['fields'][$parent]['label'] : null, 'fields' => [$name => $field]];
                            }
                            unset($list);
                            if ($box) {
                                $runOf[$name] = $runOf[$parent];
                            }
                            continue;
                        }
                        $last = array_key_last($items);
                        if ($box && $last !== null && $items[$last]['booleans'] && $items[$last]['group'] === ($field['panel'] ?? $field['group'] ?? null)) {
                            $items[$last]['fields'][$name] = $field;
                        } else {
                            $items[] = ['booleans' => $box, 'group' => $field['panel'] ?? $field['group'] ?? null, 'fields' => [$name => $field]];
                            $last = array_key_last($items);
                        }
                        if ($box) {
                            $runOf[$name] = $last;
                        }
                    }
                    $items = collect($items)->flatMap(fn (array $item, int $index) => [$item, ...($dependents[$index] ?? [])])->all();

                    // Each group under its heading; a "collapse" section folds the groups, one open
                    // at a time (the first with an error opens).
                    $groups = collect($items)->chunkWhile(fn (array $item, int $key, $chunk) => $item['group'] === $chunk->last()['group'])->values();
                    $collapse = $section['collapse'] ?? false;
                    $openGroup = $groups->search(fn ($chunk) => $chunk->contains(fn (array $item) => collect($item['fields'])->keys()->contains(fn ($name) => $errors->has($name))));
                    if ($collapse && $openGroup === false) {
                        $errorPanel = collect($section['fields'])->filter(fn (array $field, string $name) => $errors->has($name))->first()['panel'] ?? null;
                        if ($errorPanel !== null) {
                            $openGroup = $groups->search(fn ($chunk) => $chunk->first()['group'] === $errorPanel);
                        }
                    }
                @endphp
                <div id="sec-{{ $index }}" class="form-section {{ $index === $active ? 'active' : '' }}" data-section="{{ $index }}" :class="{ 'active': section === {{ $index }} }"
                     @if ($section['collapse'] ?? false) x-data="{ part: @js($openGroup === false ? null : $openGroup) }" @endif>
                    <div class="form-section-header">{{ $section['title'] }}</div>
                    <div class="row">
                        @foreach ($groups as $part => $chunk)
                            @php $group = $chunk->first()['group']; @endphp
                            @if ($collapse && $group !== null)
                                <div class="collapse-part" :class="{ 'is-open': part === {{ $part }} }">
                                    <button type="button" class="collapse-trigger" :aria-expanded="part === {{ $part }}" x-on:click="part = part === {{ $part }} ? null : {{ $part }}">{{ $group }}</button>
                                    <div class="row" x-show="part === {{ $part }}" @if ($openGroup !== $part) x-cloak @endif>
                                        @include('procedures.partials.group-items', ['items' => $chunk])
                                    </div>
                                </div>
                            @else
                                @if ($group !== null)
                                    <div class="subsection-title" style="flex: 1 1 100%; margin: 8px 0 0">{{ $group }}</div>
                                @endif
                                @include('procedures.partials.group-items', ['items' => $chunk])
                            @endif
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
                    this.tabs = saved.tabs.length ? saved.tabs : [this.newTab(this.values())];
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
                    data = { ...data, ...options.sharedValues };
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
                    // Only posted inputs count, not search boxes.
                    this.filled = Array.from(this.form.querySelectorAll('[data-section]')).map((el) =>
                        Array.from(el.querySelectorAll('input[name], select[name], textarea[name]')).some((input) =>
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

        /**
         * Drug catalog search for a text field (partials/drug-insert): a picked drug is added to
         * the field as a line, followed by its suggested dose and usage directions.
         */
        function drugInsert(searchUrl, target) {
            return {
                q: '',
                results: [],
                active: 0,
                async search() {
                    const q = this.q.trim();
                    if (q.length < 2) {
                        this.results = [];
                        return;
                    }
                    const response = await fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                    if (! response.ok || this.q.trim() !== q) return;
                    this.results = (await response.json()).data;
                    this.active = 0;
                },
                directions(drug) {
                    const routes = @js(\App\Support\Prescribing::ROUTES);
                    const frequencies = @js(\App\Support\Prescribing::FREQUENCIES);
                    const dose = [drug.dose, drug.dose ? drug.unit : null].filter(Boolean).join(' ');
                    return [dose, routes[drug.route] ?? drug.route, frequencies[drug.frequency] ?? drug.frequency,
                        drug.duration ? 'for ' + drug.duration : null, drug.instructions].filter(Boolean).join(' — ');
                },
                pick(drug) {
                    const directions = this.directions(drug);
                    const line = drug.label + (directions ? ' — ' + directions : '');
                    const field = document.getElementById(target);
                    field.value = field.value.replace(/\s+$/, '') + (field.value.trim() ? '\n' : '') + line;
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    this.q = '';
                    this.results = [];
                },
            };
        }

        /**
         * "Extract" for a text field (partials/procedure-extract): asks for the summaries of all the
         * patient's studies of the chosen procedure and adds them to the field, oldest first, each
         * headed by the procedure and its date. The patient is the form's national ID.
         */
        function procedureExtract(urls, target) {
            return {
                source: Object.keys(urls)[0],
                busy: false,
                message: '',
                failed: false,
                labels: @js(['echo' => 'Echo', ...collect(\App\Support\Procedures::MODELS)->keys()->mapWithKeys(fn (string $key) => [$key => \App\Support\Procedures::label($key)])->all()]),
                say(message, failed = false) {
                    this.message = message;
                    this.failed = failed;
                },
                async extract() {
                    const nationalID = (document.getElementById('nationalID')?.value || '').trim();
                    if (! nationalID) return this.say('Choose the patient first.', true);
                    const label = this.labels[this.source];
                    this.busy = true;
                    this.say('');
                    try {
                        const response = await fetch(urls[this.source] + '?nationalID=' + encodeURIComponent(nationalID), { headers: { Accept: 'application/json' } });
                        if (! response.ok) return this.say('Could not fetch the procedures.', true);
                        const studies = (await response.json()).studies;
                        if (! studies.length) return this.say('No ' + label + ' with a result found for this patient.', true);
                        const field = document.getElementById(target);
                        const text = studies.map((study) => label + ' (' + study.date + (study.status === 'draft' ? ', draft' : '') + '):\n' + study.summary).join('\n\n');
                        field.value = field.value.replace(/\s+$/, '') + (field.value.trim() ? '\n\n' : '') + text;
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                        this.say('Added ' + studies.length + ' ' + label + (studies.length === 1 ? '' : ' studies') + ': ' + studies.map((study) => study.date).join(', ') + '.');
                    } catch (e) {
                        this.say('Could not fetch the procedures.', true);
                    } finally {
                        this.busy = false;
                    }
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
