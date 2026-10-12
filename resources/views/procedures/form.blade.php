@php
    /**
     * Create or edit a procedure study, built from config/procedures/{procedure}.php. Like the
     * echo form it stays in English whatever the interface language. Fields with "show_if" are
     * shown only while the field they depend on has that value; the server clears them otherwise.
     *
     * @var string $procedure
     * @var \App\Models\ProcedureStudy|null $study
     * @var array<string, mixed> $values  Saved values when editing.
     */
    use App\Support\Procedures;

    $sections = Procedures::sections($procedure);
    $fields = Procedures::fields($procedure);
    $value = fn (string $name) => old($name, $values[$name] ?? null);

    // Only the fields others depend on need to be tracked in the browser.
    $watched = collect($fields)->pluck('show_if.0')->filter()->unique()
        ->mapWithKeys(fn (string $name) => [$name => (string) ($value($name) ?? '')]);
    $visible = fn (array $field) => isset($field['show_if']) ? "v['{$field['show_if'][0]}'] === '{$field['show_if'][1]}'" : null;
    $model = fn (string $name) => $watched->has($name) ? "x-model=\"v['{$name}']\"" : '';

    $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $title = Procedures::label($procedure);
@endphp

<x-dashboard-layout :title="$title">
    <form method="POST" action="{{ $study ? route('procedures.update', [$procedure, $study]) : route('procedures.store', $procedure) }}"
          dir="ltr" lang="en" x-data="{ v: @js($watched) }" class="space-y-4">
        @csrf
        @if ($study)
            @method('PUT')
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold">{{ $title }}</h2>
            @if ($study)
                <span class="text-sm text-gray-500">{{ $study->isSigned() ? 'Amending signed report' : 'Editing draft' }}</span>
                <span class="ms-auto">@include('dashboard.status-badge', ['status' => $study->status])</span>
            @endif
        </div>

        @if ($errors->any())
            <div class="rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
                Please correct the {{ $errors->count() }} highlighted {{ Str::plural('field', $errors->count()) }}.
                <ul class="mt-1 list-disc ps-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <fieldset class="rounded-lg border border-gray-200 p-4">
            <legend class="px-1 font-semibold">Patient</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (['nationalID' => 'National ID', 'first_name' => 'First name', 'last_name' => 'Last name', 'dob' => 'Date of birth (YYYY/MM/DD)', 'phone_number' => 'Phone number'] as $name => $label)
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">{{ $label }}</span>
                        <input type="text" name="{{ $name }}" value="{{ $value($name) }}" class="{{ $input }}" @required(in_array($name, ['nationalID', 'first_name', 'last_name']))>
                        @error($name) <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                @endforeach
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">Gender</span>
                    <select name="gender" class="{{ $input }}">
                        <option value=""></option>
                        @foreach (['Male', 'Female'] as $gender)
                            <option value="{{ $gender }}" @selected($value('gender') === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            @if ($admission = $value('admission_id') ?? request('admission'))
                <input type="hidden" name="admission_id" value="{{ $admission }}">
            @endif
            @error('admission_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        @foreach ($sections as $section)
            @php
                $hasValues = collect($section['fields'])->keys()->contains(fn ($name) => filled($value($name)));
                $hasErrors = collect($section['fields'])->keys()->contains(fn ($name) => $errors->has($name));
            @endphp
            <details class="rounded-lg border border-gray-200" @if ($loop->index < 4 || $hasValues || $hasErrors) open @endif>
                <summary class="cursor-pointer select-none px-4 py-2 font-semibold">{{ $section['title'] }}</summary>
                <div class="grid gap-4 border-t border-gray-100 p-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($section['fields'] as $name => $field)
                        @if (($field['group'] ?? null) !== null && ($field['group'] !== ($previousGroup ?? null)))
                            <h4 class="col-span-full mt-2 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $field['group'] }}</h4>
                        @endif
                        @php $previousGroup = $field['group'] ?? null; @endphp

                        <div @if ($visible($field)) x-show="{{ $visible($field) }}" x-cloak @endif
                             @class(['col-span-full' => $field['type'] === 'multiline'])>
                            @if ($field['type'] === 'boolean')
                                <label class="inline-flex items-center gap-2 pt-6 text-sm">
                                    <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value($name))
                                           @if ($watched->has($name)) x-on:change="v['{{ $name }}'] = $event.target.checked ? '1' : ''" @endif class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="font-medium text-gray-700">{{ $field['label'] }}</span>
                                </label>
                            @else
                                <label class="block text-sm">
                                    <span class="font-medium text-gray-700">{{ $field['label'] }}</span>
                                    @switch($field['type'])
                                        @case('choice')
                                            <select name="{{ $name }}" class="{{ $input }}" {!! $model($name) !!}>
                                                <option value=""></option>
                                                @foreach ($field['options'] as $code => $label)
                                                    <option value="{{ $code }}" @selected((string) $value($name) === (string) $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @break
                                        @case('multiline')
                                            <textarea name="{{ $name }}" rows="3" class="{{ $input }}">{{ $value($name) }}</textarea>
                                            @break
                                        @case('number')
                                            <input type="number" step="any" name="{{ $name }}" value="{{ $value($name) }}" class="{{ $input }}" {!! $model($name) !!}>
                                            @break
                                        @case('date')
                                            <input type="text" name="{{ $name }}" value="{{ $value($name) }}" placeholder="1405/01/01" class="{{ $input }}" {!! $model($name) !!}>
                                            @break
                                        @case('time')
                                            <input type="text" name="{{ $name }}" value="{{ $value($name) }}" placeholder="HH:MM" class="{{ $input }}" {!! $model($name) !!}>
                                            @break
                                        @default
                                            <input type="text" name="{{ $name }}" value="{{ $value($name) }}" class="{{ $input }}" {!! $model($name) !!}>
                                    @endswitch
                                </label>
                            @endif
                            @error($name) <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>
            </details>
        @endforeach

        @if ($study?->isSigned())
            <label class="block text-sm">
                <span class="font-medium text-gray-700">Reason for amendment</span>
                <textarea name="amendment_reason" rows="2" required class="{{ $input }}">{{ old('amendment_reason') }}</textarea>
                @error('amendment_reason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                {{ $study?->isSigned() ? 'Amend and re-sign' : 'Save draft' }}
            </button>
            @if ($study)
                <a href="{{ route('procedures.show', [$procedure, $study]) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">Cancel</a>
            @endif
        </div>
    </form>
</x-dashboard-layout>
