@php
    use App\Support\JalaliDate;
@endphp

<x-dashboard-layout :title="__('Patient echos')">
    {{-- Search by national ID, HIS admission number or name; the box is also in the dashboard header. --}}
    <form method="GET" action="{{ route('patients.index') }}" role="search" class="mb-6 flex flex-wrap gap-2 lg:hidden">
        <input name="nationalID" type="search" value="{{ $search }}" required placeholder="{{ __('National ID, admission number or name') }}" aria-label="{{ __('Search patients') }}"
               class="no-voice block w-72 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <x-primary-button>{{ __('Search') }}</x-primary-button>
    </form>
    @if ($fromHis && $patient)
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-900" role="status">{{ __('Not on record yet: found in the hospital information system and imported.') }}</div>
    @endif
    @if ($patient)
        <p class="mb-4 text-sm"><a href="{{ route('patients.show', $patient) }}" class="font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('Patient record and admissions') }}</a></p>
    @endif
    @if ($results !== null)
        @if ($kind === 'too_short')
            <p class="py-8 text-center text-gray-500">{{ __('Enter at least two characters.') }}</p>
        @elseif ($results->isEmpty())
            <p class="py-8 text-center text-gray-500">{{ __('No patients found.') }}</p>
        @else
            <h3 class="mb-2 text-lg font-semibold">{{ trans_choice(':count patient found|:count patients found', $results->count(), ['count' => $results->count()]) }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-3 py-2 text-start">{{ __('Name') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('National ID') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('Date of birth') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('Latest admission') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($results as $match)
                            @php $latest = $match->admissions->first(); @endphp
                            <tr>
                                <td class="px-3 py-2"><a href="{{ route('patients.index', ['nationalID' => $match->national_id]) }}" class="font-medium text-indigo-600 underline hover:text-indigo-500">{{ $match->first_name }} {{ $match->last_name }}</a></td>
                                <td class="px-3 py-2" dir="ltr">{{ $match->national_id }}</td>
                                <td class="px-3 py-2">{{ $match->dob }}</td>
                                <td class="px-3 py-2">
                                    @if ($latest)
                                        {{ $latest->summary() }}
                                        @unless ($latest->isDischarged())
                                            <span class="ms-1 rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">{{ __('Admitted') }}</span>
                                        @endunless
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @elseif ($nationalID === '')
        <p class="py-8 text-center text-gray-500">{{ __('Search by national ID, HIS admission number or name to see a patient\'s studies.') }}</p>
    @elseif ($echoReports->isEmpty())
        <div class="py-8 text-center text-gray-500">
            <p>{{ __('No echo reports found for this National ID.') }}</p>
            @can('echo.create')
                <a href="{{ route('studies.create') }}" class="mt-3 inline-block text-sm font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('Record a new echo study') }}</a>
            @endcan
        </div>
    @else
        <h3 class="text-lg font-semibold">{{ __('Echocardiogram reports for :name', ['name' => $patient->first_name.' '.$patient->last_name]) }}</h3>
        <p class="mb-4 text-sm text-gray-500">{{ __('National ID') }}: <strong>{{ $nationalID }}</strong></p>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-3 py-2 text-start">{{ __('Date') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Status') }}</th>
                        <th class="px-3 py-2 text-start">{{ __('Summary') }}</th>
                        <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($echoReports as $study)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-2">{{ JalaliDate::format($study->created_at, 'yyyy/MM/dd') }}</td>
                            <td class="px-3 py-2">@include('dashboard.status-badge', ['status' => $study->status])</td>
                            <td class="px-3 py-2 text-gray-700">{{ $study->summary ? Str::limit($study->summary, 120) : __('No summary available') }}</td>
                            <td class="px-3 py-2">@include('dashboard.study-actions', ['study' => $study, 'tab' => 'patients'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($nationalID !== '')
        {{-- Procedures ported from the heart system (angiography, ...). --}}
        <div class="mt-8 space-y-6">
            @foreach ($procedures as $key => $studies)
                <section>
                    <div class="mb-2 flex flex-wrap items-center gap-3">
                        <h3 class="text-lg font-semibold">{{ App\Support\Procedures::label($key) }}</h3>
                        @can("{$key}.create")
                            <a href="{{ route('procedures.create', [$key, 'nationalID' => $nationalID]) }}" class="text-sm font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('Record a new study') }}</a>
                        @endcan
                    </div>
                    @if ($studies->isNotEmpty())
                        <ul class="divide-y divide-gray-100 text-sm">
                            @foreach ($studies as $study)
                                <li class="flex flex-wrap items-center gap-3 py-2">
                                    <span class="whitespace-nowrap">{{ $study->performedOn() ?? JalaliDate::format($study->created_at, 'yyyy/MM/dd') }}</span>
                                    @include('dashboard.status-badge', ['status' => $study->status])
                                    <a href="{{ route('procedures.show', [$key, $study]) }}" class="ms-auto font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('View report') }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            @includeWhen($patient, 'prescriptions.partials.patient', ['patient' => $patient])
        </div>
    @endif
</x-dashboard-layout>
