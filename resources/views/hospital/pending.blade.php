<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $hospital->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-gray-900">
                @if ($hospital->status === \App\Enums\HospitalStatus::Suspended)
                    <h3 class="text-lg font-semibold">{{ __('This hospital account is suspended') }}</h3>
                    <p class="mt-2 text-gray-600">{{ __('Please contact the platform administrator to restore access.') }}</p>
                @else
                    <h3 class="text-lg font-semibold">{{ __('Awaiting approval') }}</h3>
                    <p class="mt-2 text-gray-600">{{ __('Thanks for registering. A platform administrator will review your hospital shortly; you will be able to invite staff and record echo studies once it is approved.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
