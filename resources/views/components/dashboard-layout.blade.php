{{-- Shared shell for every dashboard section: header, primary actions, flash messages and section tabs. --}}
@props(['title'])

@php
    $user = Auth::user();
    $tabs = \App\Support\DashboardTabs::for($user);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $title }}</h2>
                @if ($user->hospital)
                    <p class="mt-1 text-sm text-gray-500">{{ $user->hospital->name }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-3">
                {{ $actions ?? '' }}
                @can('studies.create')
                    <a href="{{ route('studies.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('New echo study') }}</a>
                @endcan
                @if ($user->hospital_id)
                    <a href="{{ route('help') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">{{ __('Help') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                @if (count($tabs) > 1)
                    <nav class="flex gap-6 overflow-x-auto whitespace-nowrap border-b border-gray-200 px-6" aria-label="{{ __('Dashboard sections') }}">
                        @foreach ($tabs as $tab)
                            <a href="{{ $tab['url'] }}"
                               @if ($tab['active']) aria-current="page" @endif
                               class="-mb-px inline-flex items-center gap-2 border-b-2 py-4 text-sm font-medium {{ $tab['active'] ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                                {{ $tab['label'] }}
                                @if ($tab['count'])
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $tab['count'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                @endif

                <div class="p-6 text-gray-900">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
