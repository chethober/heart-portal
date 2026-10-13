@php
    use App\Models\Admission;
    use App\Support\JalaliDate;
    use App\Support\Procedures;

    $stay = $admission->lengthOfStay();
    $details = [
        __('Ward') => $admission->wardName(),
        __('Admitted') => trim($admission->admitted_on.' '.$admission->admitted_at),
        __('Admission type') => $admission->admission_type !== null ? (Admission::admissionTypes()[$admission->admission_type] ?? null) : null,
        __('Arrival') => $admission->arrival_mode !== null ? (Admission::arrivalModes()[$admission->arrival_mode] ?? null) : null,
        __('Referring hospital') => $admission->referred_from,
        __('Attending physician') => $admission->physician,
        __('Discharged') => trim($admission->discharged_on.' '.$admission->discharged_at),
        __('Length of stay') => $stay !== null ? __(':count days', ['count' => $stay]) : null,
        __('HIS admission number') => $admission->his_id,
    ];
    foreach ($admission->his_codes ?? [] as $code => $hisValue) {
        $details[$code] = $hisValue;
    }
@endphp

<x-dashboard-layout :title="__('Admission')">
    <div class="space-y-8">
        <section>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-xl font-semibold">{{ __('Admission') }}</h2>
                <a href="{{ route('patients.show', $patient) }}" class="text-sm font-medium text-indigo-600 underline hover:text-indigo-500">{{ $patient->first_name }} {{ $patient->last_name }}</a>
                @if (! $admission->isDischarged())
                    <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">{{ __('Admitted') }}</span>
                @endif
                <div class="ms-auto flex flex-wrap items-center gap-2">
                    @can('record-studies')
                        @include('admissions.record-links', ['admission' => $admission])
                    @endcan
                    @can('admission.edit')
                        <a href="{{ route('admissions.edit', [$patient, $admission]) }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ __('Edit') }}</a>
                    @endcan
                </div>
            </div>
            <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($details as $label => $detail)
                    @continue(blank($detail) && in_array($label, Admission::HIS_CODES, true))
                    <div>
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="font-medium text-gray-900">{{ filled($detail) ? $detail : '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            @if (! $admission->isDischarged())
                @can('admission.edit')
                    <form method="POST" action="{{ route('admissions.discharge', [$patient, $admission]) }}" class="mt-6 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 p-4">
                        @csrf
                        <div>
                            <x-input-label for="discharged_on" :value="__('Discharge date (YYYY/MM/DD)')" />
                            <x-text-input id="discharged_on" name="discharged_on" type="text" class="mt-1 block w-40" :value="old('discharged_on', App\Support\JalaliCalendar::today())" required />
                        </div>
                        <div>
                            <x-input-label for="discharged_at" :value="__('Discharge time (HH:MM)')" />
                            <x-text-input id="discharged_at" name="discharged_at" type="text" class="mt-1 block w-28" :value="old('discharged_at')" placeholder="14:00" />
                        </div>
                        <x-primary-button>{{ __('Discharge patient') }}</x-primary-button>
                        <x-input-error :messages="array_merge($errors->get('discharged_on'), $errors->get('discharged_at'))" class="w-full" />
                    </form>
                @endcan
            @endif
        </section>

        {{-- The studies recorded during this stay (heart's per-admission form lists). --}}
        <section class="space-y-6">
            <h3 class="text-lg font-semibold">{{ __('Studies during this admission') }}</h3>

            @if ($echoStudies !== null)
            <div>
                <h4 class="mb-1 font-semibold">{{ __('Echocardiography') }}</h4>
                @forelse ($echoStudies as $study)
                    <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 py-2 text-sm">
                        <span class="whitespace-nowrap">{{ JalaliDate::format($study->created_at, 'yyyy/MM/dd') }}</span>
                        @include('dashboard.status-badge', ['status' => $study->status])
                        <a href="{{ route('studies.show', $study) }}" class="ms-auto font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('View report') }}</a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('None recorded.') }}</p>
                @endforelse
            </div>
            @endif

            @foreach ($procedures as $key => $studies)
                <div>
                    <div class="mb-1 flex flex-wrap items-center gap-3">
                        <h4 class="font-semibold">{{ __(Procedures::label($key)) }}</h4>
                        @can("{$key}.create")
                            <a href="{{ route('procedures.create', [$key, 'nationalID' => $patient->national_id, 'admission' => $admission->id]) }}" class="text-sm font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('Record for this admission') }}</a>
                        @endcan
                    </div>
                    @forelse ($studies as $study)
                        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 py-2 text-sm">
                            <span class="whitespace-nowrap">{{ $study->performedOn() ?? JalaliDate::format($study->created_at, 'yyyy/MM/dd') }}</span>
                            @include('dashboard.status-badge', ['status' => $study->status])
                            <a href="{{ route('procedures.show', [$key, $study]) }}" class="ms-auto font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('View report') }}</a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('None recorded.') }}</p>
                    @endforelse
                </div>
            @endforeach
        </section>
    </div>
</x-dashboard-layout>
