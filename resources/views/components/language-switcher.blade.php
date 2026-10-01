@props(['align' => 'right'])

@php
    $locales = config('app.available_locales');
    $current = app()->getLocale();
@endphp

<form method="POST" action="{{ route('locale.update') }}" {{ $attributes }}>
    @csrf
    <x-dropdown :align="$align" width="48">
        <x-slot name="trigger">
            <button type="button" aria-label="{{ __('Language') }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                <svg class="me-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM4.332 8.027a6.012 6.012 0 0 1 1.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 0 1 9 7.5V8a2 2 0 0 0 4 0 2 2 0 0 1 1.523-1.943A5.977 5.977 0 0 1 16 10c0 .34-.028.675-.083 1H15a2 2 0 0 0-2 2v2.197A5.973 5.973 0 0 1 10 16v-2a2 2 0 0 0-2-2 2 2 0 0 1-2-2 2 2 0 0 0-1.668-1.973Z" clip-rule="evenodd" />
                </svg>
                <div lang="{{ $current }}">{{ $locales[$current] ?? $current }}</div>

                <div class="ms-1">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>
        </x-slot>

        <x-slot name="content">
            @foreach ($locales as $code => $name)
                <button type="submit" name="locale" value="{{ $code }}" lang="{{ $code }}" @if ($code === $current) aria-current="true" @endif
                        class="block w-full px-4 py-2 text-start text-sm leading-5 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out {{ $code === $current ? 'font-semibold text-gray-900' : 'text-gray-700' }}">
                    {{ $name }}
                </button>
            @endforeach
        </x-slot>
    </x-dropdown>
</form>
