@php
    $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $value = fn (string $name) => old($name, $patient->getAttribute($name));
@endphp

<x-dashboard-layout :title="__('Edit patient details')">
    <form method="POST" action="{{ route('patients.update', $patient) }}" class="space-y-6"
          x-data="{ nationality: @js((string) $value('nationality')) }">
        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold">{{ __('Edit patient details') }}</h2>
            <span class="text-sm text-gray-500">{{ __('National ID') }}: <strong>{{ $patient->national_id }}</strong></span>
        </div>

        <fieldset class="rounded-lg border border-gray-200 p-4">
            <legend class="px-1 font-semibold">{{ __('Demographics') }}</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (['first_name' => __('First name'), 'last_name' => __('Last name'), 'father_name' => __('Father\'s name'), 'dob' => __('Date of birth (YYYY/MM/DD)'), 'occupation' => __('Occupation')] as $name => $label)
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">{{ $label }}</span>
                        <input type="text" name="{{ $name }}" value="{{ $value($name) }}" class="{{ $input }}" @required(in_array($name, ['first_name', 'last_name'])) @if ($name === 'dob') placeholder="1370/05/21" @endif>
                        <x-input-error :messages="$errors->get($name)" class="mt-1" />
                    </label>
                @endforeach
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Gender') }}</span>
                    <select name="gender" class="{{ $input }}">
                        <option value=""></option>
                        <option value="Male" @selected($value('gender') === 'Male')>{{ __('Male') }}</option>
                        <option value="Female" @selected($value('gender') === 'Female')>{{ __('Female') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Marital status') }}</span>
                    <select name="marital_status" class="{{ $input }}">
                        <option value=""></option>
                        @foreach ($patient::maritalStatuses() as $code => $label)
                            <option value="{{ $code }}" @selected((string) $value('marital_status') === (string) $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('marital_status')" class="mt-1" />
                </label>
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Nationality') }}</span>
                    <select name="nationality" x-model="nationality" class="{{ $input }}">
                        <option value=""></option>
                        @foreach ($patient::nationalities() as $code => $label)
                            <option value="{{ $code }}" @selected((string) $value('nationality') === (string) $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('nationality')" class="mt-1" />
                </label>
                <label class="block text-sm" x-show="nationality === '99999'" x-cloak>
                    <span class="font-medium text-gray-700">{{ __('Other nationality') }}</span>
                    <input type="text" name="nationality_other" value="{{ $value('nationality_other') }}" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('nationality_other')" class="mt-1" />
                </label>
            </div>
        </fieldset>

        <fieldset class="rounded-lg border border-gray-200 p-4">
            <legend class="px-1 font-semibold">{{ __('Contact details') }}</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (['phone_number' => __('Phone number'), 'home_phone' => __('Home phone'), 'emergency_phone' => __('Emergency phone')] as $name => $label)
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">{{ $label }}</span>
                        <input type="tel" name="{{ $name }}" value="{{ $value($name) }}" dir="ltr" class="{{ $input }}">
                        <x-input-error :messages="$errors->get($name)" class="mt-1" />
                    </label>
                @endforeach
                <label class="block text-sm">
                    <span class="font-medium text-gray-700">{{ __('Email') }}</span>
                    <input type="email" name="email" value="{{ $value('email') }}" dir="ltr" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </label>
                <label class="block text-sm sm:col-span-2">
                    <span class="font-medium text-gray-700">{{ __('Address') }}</span>
                    <textarea name="address" rows="2" class="{{ $input }}">{{ $value('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </label>
            </div>
        </fieldset>

        <div class="flex gap-3">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            <a href="{{ route('patients.show', $patient) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-dashboard-layout>
