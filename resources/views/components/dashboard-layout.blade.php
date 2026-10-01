{{-- Shared shell for every dashboard section: flash messages and the content card. The sections,
     patient search and help are in the top navigation; the title names the browser tab. --}}
@props(['title'])

<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>

    <div class="py-6 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4 print:max-w-none print:px-0">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 print:hidden" role="status">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg print:shadow-none">
                <div class="p-6 text-gray-900 print:p-0">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
