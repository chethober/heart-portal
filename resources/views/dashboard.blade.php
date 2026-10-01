<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }} — {{ $hospital->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <p>{{ __('Signed in as :role.', ['role' => Auth::user()->primaryRole()?->label() ?? __('member')]) }}</p>
                    <div class="flex flex-wrap gap-3">
                        @can('studies.create')
                            <a href="{{ route('studies.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('New echo study') }}</a>
                        @endcan
                        @can('patients.view')
                            <a href="{{ route('patients.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">{{ __('Patient echos') }}</a>
                        @endcan
                        <a href="{{ route('help.form') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">{{ __('Help') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
