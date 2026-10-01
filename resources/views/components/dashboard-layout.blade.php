{{-- Shared shell for every dashboard section: header with patient search and help, and flash messages.
     The sections themselves are linked from the top navigation. --}}
@props(['title'])

@php
    $user = Auth::user();
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
            <div class="flex flex-wrap items-center gap-3">
                {{ $actions ?? '' }}
                @can('patients.view')
                    <form method="GET" action="{{ route('patients.index') }}" class="flex items-center gap-2" role="search">
                        <label for="header-national-id" class="sr-only">{{ __('National ID') }}</label>
                        @php($onPatients = request()->routeIs('patients.index'))
                        <input id="header-national-id" name="nationalID" type="search" required
                               value="{{ $onPatients ? request()->query('nationalID') : '' }}"
                               @if ($onPatients && blank(request()->query('nationalID'))) autofocus @endif
                               placeholder="{{ __('National ID') }}"
                               class="no-voice block w-48 rounded-md border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('Search') }}</button>
                    </form>
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
                <div class="p-6 text-gray-900">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
