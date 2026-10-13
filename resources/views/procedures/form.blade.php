@php
    /**
     * Create or edit a procedure study, built from config/procedures/{procedure}.php. Laid out like
     * the echo form (studies/form.blade.php) and styled by its stylesheet, which is scoped to
     * #echo-app: patient details, a section bar showing which sections are filled in, one section
     * at a time, and a live report preview beside the form. Like the echo form it stays in its
     * clinical language whatever the interface language. Fields with "show_if" are shown only while
     * the field they depend on has that value; the server clears them otherwise.
     *
     * @var string $procedure
     * @var \App\Models\ProcedureStudy|null $study
     * @var array<string, mixed> $values  Saved values when editing.
     */
    use App\Support\Procedures;

    $sections = array_values(Procedures::sections($procedure));
    $fields = Procedures::fields($procedure);
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
    @include('studies.partials.form-styles')

    <div id="echo-app" dir="{{ $rtl ? 'rtl' : 'ltr' }}" lang="{{ $rtl ? 'fa' : 'en' }}"
         x-data="procedureForm(@js($watched), {{ $active }}, @js(route('procedures.preview', $procedure)))">
    <div id="app-layout" :class="{ 'preview-active': preview }">
    <div id="form-container">
        <form method="POST" id="procedureForm" x-ref="form" action="{{ $study ? route('procedures.update', [$procedure, $study]) : route('procedures.store', $procedure) }}"
              autocomplete="off" x-on:input.debounce.400ms="refresh()" x-on:change="refresh(); mark()">
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
                @foreach (['first_name' => 'First Name', 'last_name' => 'Last Name', 'nationalID' => 'National ID', 'dob' => 'Date of Birth', 'phone_number' => 'Phone Number'] as $name => $label)
                    @php $required = in_array($name, ['nationalID', 'first_name', 'last_name'], true); @endphp
                    <div class="field">
                        <label for="{{ $name }}">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
                        <input id="{{ $name }}" name="{{ $name }}" type="text" value="{{ $value($name) }}" @required($required) @if ($name === 'dob') placeholder="1370/01/01" @endif>
                        @error($name) <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
                    </div>
                @endforeach
                <div class="field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select gender</option>
                        @foreach (['Male', 'Female'] as $gender)
                            <option value="{{ $gender }}" @selected($value('gender') === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                </div>
                @php $admission = $value('admission_id') ?? request('admission'); @endphp
                @if (($admissions ?? collect())->isNotEmpty())
                    {{-- The patient's admissions (heart links each form to an admit record). --}}
                    <div class="field">
                        <label for="admission_id">Admission</label>
                        <select id="admission_id" name="admission_id">
                            <option value="">Not linked to an admission</option>
                            @foreach ($admissions as $option)
                                <option value="{{ $option->id }}" @selected((string) $admission === (string) $option->id)>{{ $option->summary() }}{{ $option->isDischarged() ? ' (discharged '.$option->discharged_on.')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('admission_id') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
                    </div>
                @elseif ($admission)
                    <input type="hidden" name="admission_id" value="{{ $admission }}">
                @endif
            </div>

            {{-- One chip per section, green once something in it is filled in, red with an error. --}}
            <div class="section-nav-wrapper">
                <div class="section-nav-container" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
                    @foreach ($sections as $index => $section)
                        @php $hasErrors = collect($section['fields'])->keys()->contains(fn ($name) => $errors->has($name)); @endphp
                        <button type="button" class="section-nav-btn" title="{{ $section['title'] }}"
                                :class="{ 'active': section === {{ $index }}, 'status-filled': filled[{{ $index }}] && ! {{ $hasErrors ? 'true' : 'false' }}, 'status-unfilled': {{ $hasErrors ? 'true' : 'false' }} }"
                                x-on:click="section = {{ $index }}">
                            <span class="btn-abbr">{{ Str::limit($section['title'], 28) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach ($sections as $index => $section)
                <div id="sec-{{ $index }}" class="form-section {{ $index === $active ? 'active' : '' }}" data-section="{{ $index }}" :class="{ 'active': section === {{ $index }} }">
                    <div class="form-section-header">{{ $section['title'] }}</div>
                    <div class="row">
                        @foreach ($section['fields'] as $name => $field)
                            @if (($field['group'] ?? null) !== null && ($field['group'] !== ($previousGroup ?? null)))
                                <div class="subsection-title" style="flex: 1 1 100%; margin: 8px 0 0">{{ $field['group'] }}</div>
                            @endif
                            @php $previousGroup = $field['group'] ?? null; @endphp

                            <div class="field" @if ($visible($field)) x-show="{{ $visible($field) }}" x-cloak @endif
                                 @if ($field['type'] === 'multiline') style="flex: 1 1 100%" @endif>
                                @if ($field['type'] === 'boolean')
                                    <label style="display:flex;align-items:center;gap:8px;margin-top:22px">
                                        <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value($name))
                                               @if ($watched->has($name)) x-on:change="v['{{ $name }}'] = $event.target.checked ? '1' : ''" @endif>
                                        <span>{{ $field['label'] }}</span>
                                    </label>
                                @else
                                    <label for="f-{{ $name }}">{{ $field['label'] }}</label>
                                    @switch($field['type'])
                                        @case('choice')
                                            <select id="f-{{ $name }}" name="{{ $name }}" {!! $model($name) !!}>
                                                <option value=""></option>
                                                @foreach ($field['options'] as $code => $label)
                                                    <option value="{{ $code }}" @selected((string) $value($name) === (string) $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @break
                                        @case('multiline')
                                            <textarea id="f-{{ $name }}" name="{{ $name }}" style="min-height: 60px">{{ $value($name) }}</textarea>
                                            @break
                                        @case('number')
                                            <input id="f-{{ $name }}" type="number" step="any" name="{{ $name }}" value="{{ $value($name) }}" {!! $model($name) !!}>
                                            @break
                                        @case('date')
                                            <input id="f-{{ $name }}" type="text" name="{{ $name }}" value="{{ $value($name) }}" placeholder="1405/01/01" {!! $model($name) !!}>
                                            @break
                                        @case('time')
                                            <input id="f-{{ $name }}" type="text" name="{{ $name }}" value="{{ $value($name) }}" placeholder="HH:MM" {!! $model($name) !!}>
                                            @break
                                        @default
                                            <input id="f-{{ $name }}" type="text" name="{{ $name }}" value="{{ $value($name) }}" {!! $model($name) !!}>
                                    @endswitch
                                @endif
                                @error($name) <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
                            </div>
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
         * The procedure form's behaviour: show_if values (v), the open section, which sections are
         * filled in, and the live preview, which posts the form to the preview route into the iframe
         * (as the echo form does), without the PUT override an edit form carries.
         */
        function procedureForm(watched, firstSection, previewUrl) {
            return {
                v: watched,
                section: firstSection,
                filled: [],
                preview: false,
                init() {
                    this.mark();
                    try { this.toggle(localStorage.getItem('procedureForm_live_preview') === 'true'); } catch (e) {}
                },
                mark() {
                    this.filled = Array.from(this.$refs.form.querySelectorAll('[data-section]')).map((el) =>
                        Array.from(el.querySelectorAll('input, select, textarea')).some((input) =>
                            input.type === 'checkbox' ? input.checked : input.value.trim() !== ''));
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
                    const form = this.$refs.form;
                    const action = form.action;
                    if (this.$refs.method) this.$refs.method.disabled = true;
                    form.action = previewUrl;
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
    </script>
</x-dashboard-layout>
