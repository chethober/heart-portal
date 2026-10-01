@php
    use App\Support\JalaliDate;
@endphp

<x-dashboard-layout :title="__('Patient echos')">
    <form method="GET" action="{{ route('patients.index') }}" class="mb-4 flex flex-wrap items-end gap-3" role="search">
        <div>
            <x-input-label for="nationalID" :value="__('National ID')" />
            <x-text-input id="nationalID" name="nationalID" class="mt-1 block w-56" :value="$nationalID" required autofocus />
        </div>
        <x-primary-button>{{ __('Search') }}</x-primary-button>
    </form>

    @if ($nationalID === '')
        <p class="py-8 text-center text-gray-500">{{ __("Enter the patient's National ID to see their recorded echocardiograms.") }}</p>
    @elseif ($echoReports->isEmpty())
        <div class="py-8 text-center text-gray-500">
            <p>{{ __('No echo reports found for this National ID.') }}</p>
            @can('studies.create')
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
</x-dashboard-layout>
