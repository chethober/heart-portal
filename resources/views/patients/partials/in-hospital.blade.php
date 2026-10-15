{{-- Who is in hospital now, by ward: the patients page when nothing is searched (heart's home page). --}}
<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <h2 class="text-xl font-semibold">
        {{ $ward ? __('Patients in :ward', ['ward' => $ward->name]) : __('Patients in hospital') }}
        <span class="text-base font-normal text-gray-500">({{ $admissions->count() }})</span>
    </h2>

    @if ($wards->isNotEmpty())
        <nav class="flex flex-wrap gap-2" aria-label="{{ __('Ward') }}">
            <a href="{{ route('patients.index') }}" @if (! $wardId) aria-current="true" @endif
               class="rounded-full border px-3 py-1 text-sm {{ ! $wardId ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}">{{ __('All wards') }}</a>
            @foreach ($wards as $option)
                <a href="{{ route('patients.index', ['ward' => $option->id]) }}" @if ($wardId === $option->id) aria-current="true" @endif
                   class="rounded-full border px-3 py-1 text-sm {{ $wardId === $option->id ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}">{{ $option->name }}</a>
            @endforeach
        </nav>
    @endif
</div>

@if ($admissions->isEmpty())
    <p class="py-8 text-center text-gray-500">{{ __('No patients are currently admitted here.') }}</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-3 py-2 text-start">{{ __('Patient') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Ward') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Admitted') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Length of stay') }}</th>
                    <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($admissions as $admission)
                    @php
                        $age = App\Support\JalaliCalendar::age($admission->patient->dob);
                        $stay = $admission->lengthOfStay();
                    @endphp
                    <tr>
                        <td class="px-3 py-2">
                            <a href="{{ route('patients.show', $admission->patient) }}" class="font-medium text-indigo-600 underline hover:text-indigo-500">{{ $admission->patient->first_name }} {{ $admission->patient->last_name }}</a>
                            @if ($age !== null)
                                <span class="text-gray-500">{{ __('Age :age', ['age' => $age]) }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $admission->wardName() ?? '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-2">{{ $admission->admitted_on }} {{ $admission->admitted_at }}</td>
                        <td class="whitespace-nowrap px-3 py-2">{{ $stay !== null ? __(':count days', ['count' => $stay]) : '—' }}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <a href="{{ route('admissions.show', [$admission->patient_id, $admission]) }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">{{ __('Admission') }}</a>
                                @can('record-studies')
                                    @include('admissions.record-links', ['admission' => $admission])
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
