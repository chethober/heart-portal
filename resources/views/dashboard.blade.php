@php
    use App\Enums\StudyStatus;
    use App\Support\JalaliDate;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }} — {{ $hospital->name }}
            </h2>
            <div class="flex flex-wrap gap-3">
                @can('studies.create')
                    <a href="{{ route('studies.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('New echo study') }}</a>
                @endcan
                <a href="{{ route('help') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">{{ __('Help') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @cannot('patients.view')
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-900">
                    {{ __('Signed in as :role.', ['role' => Auth::user()->primaryRole()?->label() ?? __('member')]) }}
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <nav class="flex gap-6 border-b border-gray-200 px-6" aria-label="{{ __('Dashboard sections') }}">
                        @foreach (['studies' => __('Studies'), 'patients' => __('Patient echos')] as $key => $label)
                            <a href="{{ route('dashboard', ['tab' => $key]) }}"
                               @if ($tab === $key) aria-current="page" @endif
                               class="-mb-px border-b-2 py-4 text-sm font-medium {{ $tab === $key ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="p-6 text-gray-900">
                        @if ($tab === 'studies')
                            <form method="GET" action="{{ route('dashboard') }}" class="mb-4 flex flex-wrap items-end gap-3" role="search">
                                <input type="hidden" name="tab" value="studies">
                                <div>
                                    <x-input-label for="search" :value="__('Patient name')" />
                                    <x-text-input id="search" name="search" type="search" class="mt-1 block w-56" :value="$search" />
                                </div>
                                <div>
                                    <x-input-label for="status" :value="__('Status')" />
                                    <select id="status" name="status" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">{{ __('All') }}</option>
                                        @foreach (StudyStatus::cases() as $case)
                                            <option value="{{ $case->value }}" @selected($status === $case->value)>{{ __($case->label()) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
                            </form>

                            @if ($studies->isEmpty())
                                <p class="py-8 text-center text-gray-500">{{ __('No studies found.') }}</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead>
                                            <tr class="text-start text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                <th class="px-3 py-2 text-start">{{ __('Date') }}</th>
                                                <th class="px-3 py-2 text-start">{{ __('Patient') }}</th>
                                                <th class="px-3 py-2 text-start">{{ __('Status') }}</th>
                                                <th class="px-3 py-2 text-start">{{ __('Recorded by') }}</th>
                                                <th class="px-3 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($studies as $study)
                                                <tr>
                                                    <td class="whitespace-nowrap px-3 py-2">{{ JalaliDate::format($study->created_at, 'yyyy/MM/dd') }}</td>
                                                    <td class="px-3 py-2">{{ $study->patient?->first_name }} {{ $study->patient?->last_name }}</td>
                                                    <td class="px-3 py-2">@include('dashboard.status-badge', ['status' => $study->status])</td>
                                                    <td class="px-3 py-2">{{ $study->author?->name }}</td>
                                                    <td class="px-3 py-2">@include('dashboard.study-actions', ['study' => $study, 'tab' => 'studies'])</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4">{{ $studies->links() }}</div>
                            @endif
                        @else
                            <form method="GET" action="{{ route('dashboard') }}" class="mb-4 flex flex-wrap items-end gap-3" role="search">
                                <input type="hidden" name="tab" value="patients">
                                <div>
                                    <x-input-label for="nationalID" :value="__('National ID')" />
                                    <x-text-input id="nationalID" name="nationalID" class="mt-1 block w-56" :value="$nationalID" required autofocus />
                                </div>
                                <x-primary-button>{{ __('Search') }}</x-primary-button>
                            </form>

                            @if ($nationalID === '')
                                <p class="py-8 text-center text-gray-500">{{ __("Enter the patient's National ID to see their recorded echocardiograms.") }}</p>
                            @elseif ($echoReports->isEmpty())
                                <p class="py-8 text-center text-gray-500">{{ __('No echo reports found for this National ID.') }}</p>
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
                        @endif
                    </div>
                </div>
            @endcannot
        </div>
    </div>
</x-app-layout>
