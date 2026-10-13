@php
    use App\Support\JalaliCalendar;

    $age = JalaliCalendar::age($patient->dob);
    $details = [
        __('National ID') => $patient->national_id,
        __('Father\'s name') => $patient->father_name,
        __('Date of birth') => $patient->dob ? $patient->dob.($age !== null ? ' ('.__('Age :age', ['age' => $age]).')' : '') : null,
        __('Gender') => $patient->gender ? ($patient->gender === 'Male' ? __('Male') : __('Female')) : null,
        __('Nationality') => $patient->nationalityLabel(),
        __('Marital status') => $patient->marital_status !== null ? ($patient::maritalStatuses()[$patient->marital_status] ?? null) : null,
        __('Occupation') => $patient->occupation,
        __('Phone number') => $patient->phone_number,
        __('Home phone') => $patient->home_phone,
        __('Emergency phone') => $patient->emergency_phone,
        __('Email') => $patient->email,
        __('Address') => $patient->address,
    ];
@endphp

<x-dashboard-layout :title="$patient->first_name.' '.$patient->last_name">
    <div class="space-y-8">
        <section>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold">{{ $patient->first_name }} {{ $patient->last_name }}</h2>
                <a href="{{ route('patients.index', ['nationalID' => $patient->national_id]) }}" class="text-sm font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('Studies') }}</a>
                @can('studies.edit')
                    <a href="{{ route('patients.edit', $patient) }}" class="ms-auto inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">{{ __('Edit details') }}</a>
                @endcan
            </div>
            <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($details as $label => $detail)
                    <div>
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="whitespace-pre-line font-medium text-gray-900">{{ filled($detail) ? $detail : '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section>
            <div class="mb-2 flex flex-wrap items-center gap-3">
                <h3 class="text-lg font-semibold">{{ __('Admissions') }}</h3>
                @can('studies.edit')
                    <a href="{{ route('admissions.create', $patient) }}" class="ms-auto rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">{{ __('New admission') }}</a>
                @endcan
            </div>
            @if ($admissions->isEmpty())
                <p class="py-4 text-sm text-gray-500">{{ __('No admissions recorded.') }}</p>
            @else
                @include('admissions.table', ['admissions' => $admissions])
            @endif
        </section>
    </div>
</x-dashboard-layout>
