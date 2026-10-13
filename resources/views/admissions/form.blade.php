@php
    use App\Models\Admission;

    $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $value = fn (string $name) => old($name, $admission->getAttribute($name));
    $editing = $admission->exists;
    $title = $editing ? __('Edit admission') : __('New admission');
@endphp

<x-dashboard-layout :title="$title">
    <form method="POST" action="{{ $editing ? route('admissions.update', [$patient, $admission]) : route('admissions.store', $patient) }}" class="space-y-6"
          x-data="{ arrival: @js((string) $value('arrival_mode')) }">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold">{{ $title }}</h2>
            <a href="{{ route('patients.show', $patient) }}" class="text-sm text-indigo-600 underline hover:text-indigo-500">{{ $patient->first_name }} {{ $patient->last_name }}</a>
        </div>

        <fieldset class="rounded-lg border border-gray-200 p-4">
            <legend class="px-1 font-semibold">{{ __('Admission') }}</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Ward') }}</span>
                    <select name="ward_id" class="{{ $input }}">
                        <option value=""></option>
                        @foreach ($wards as $ward)
                            <option value="{{ $ward->id }}" @selected((string) $value('ward_id') === (string) $ward->id)>{{ $ward->name }}</option>
                        @endforeach
                    </select>
                    @if ($wards->isEmpty())
                        <span class="text-xs text-gray-500">{{ __('Your hospital admin adds wards on the Wards page.') }}</span>
                    @endif
                    <x-input-error :messages="$errors->get('ward_id')" class="mt-1" />
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Admission date (YYYY/MM/DD)') }}</span>
                    <input type="text" name="admitted_on" value="{{ $value('admitted_on') }}" placeholder="1405/07/01" required class="{{ $input }}">
                    <x-input-error :messages="$errors->get('admitted_on')" class="mt-1" />
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Admission time (HH:MM)') }}</span>
                    <input type="text" name="admitted_at" value="{{ $value('admitted_at') }}" placeholder="08:30" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('admitted_at')" class="mt-1" />
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Admission type') }}</span>
                    <select name="admission_type" class="{{ $input }}">
                        <option value=""></option>
                        @foreach (Admission::admissionTypes() as $code => $label)
                            <option value="{{ $code }}" @selected($value('admission_type') !== null && (string) $value('admission_type') === (string) $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('admission_type')" class="mt-1" />
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Arrival') }}</span>
                    <select name="arrival_mode" x-model="arrival" class="{{ $input }}">
                        <option value=""></option>
                        @foreach (Admission::arrivalModes() as $code => $label)
                            <option value="{{ $code }}" @selected((string) $value('arrival_mode') === (string) $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('arrival_mode')" class="mt-1" />
                </label>
                <label class="block text-sm" x-show="arrival === '3'" x-cloak>
                    <span class="font-medium text-gray-700">{{ __('Referring hospital') }}</span>
                    <input type="text" name="referred_from" value="{{ $value('referred_from') }}" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('referred_from')" class="mt-1" />
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Attending physician') }}</span>
                    <input type="text" name="physician" value="{{ $value('physician') }}" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('physician')" class="mt-1" />
                </label>
            </div>
        </fieldset>

        <fieldset class="rounded-lg border border-gray-200 p-4">
            <legend class="px-1 font-semibold">{{ __('Discharge') }}</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Discharge date (YYYY/MM/DD)') }}</span>
                    <input type="text" name="discharged_on" value="{{ $value('discharged_on') }}" placeholder="1405/07/05" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('discharged_on')" class="mt-1" />
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Discharge time (HH:MM)') }}</span>
                    <input type="text" name="discharged_at" value="{{ $value('discharged_at') }}" placeholder="14:00" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('discharged_at')" class="mt-1" />
                </label>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ __('Leave empty while the patient is in hospital. The discharge outcome is recorded on the Discharge Summary form.') }}</p>
        </fieldset>

        <fieldset class="rounded-lg border border-gray-200 p-4">
            <legend class="px-1 font-semibold">{{ __('Final diagnosis (ICD-10)') }}</legend>
            @include('clinical.partials.diagnosis-picker', ['name' => 'icd_codes', 'selected' => old('icd_codes', $admission->icd_codes ?? [])])
            <x-input-error :messages="collect($errors->get('icd_codes*'))->flatten()->all()" class="mt-1" />
        </fieldset>

        <details class="rounded-lg border border-gray-200" @if ($errors->has('his_*') || filled($value('his_id')) || filled($value('his_codes'))) open @endif>
            <summary class="cursor-pointer select-none px-4 py-2 font-semibold">{{ __('Hospital information system') }}</summary>
            <div class="grid gap-4 border-t border-gray-100 p-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('HIS admission number') }}</span>
                    <input type="text" name="his_id" value="{{ $value('his_id') }}" dir="ltr" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('his_id')" class="mt-1" />
                </label>
                @foreach (Admission::HIS_CODES as $code)
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700" dir="ltr">{{ $code }}</span>
                        <input type="text" name="his_codes[{{ $code }}]" value="{{ old("his_codes.{$code}", $admission->his_codes[$code] ?? null) }}" dir="ltr" class="{{ $input }}">
                        <x-input-error :messages="$errors->get("his_codes.{$code}")" class="mt-1" />
                    </label>
                @endforeach
            </div>
        </details>

        <div class="flex gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            <a href="{{ $editing ? route('admissions.show', [$patient, $admission]) : route('patients.show', $patient) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-dashboard-layout>
