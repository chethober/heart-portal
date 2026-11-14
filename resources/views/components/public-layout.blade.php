@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), config('app.rtl_locales'), true) ? 'rtl' : 'ltr' }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title.' — '.config('app.name') : config('app.name').' — '.__('Echocardiography reporting for hospitals') }}</title>
        <x-favicons />
        <meta name="description" content="{{ $description ?? __('Open-source echocardiography reporting platform: structured echo entry, automatic grading, signed reports and audit trails for every hospital.') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @if (in_array(app()->getLocale(), config('app.rtl_locales'), true))
            <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700&display=swap" rel="stylesheet" />
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-white">
        <header x-data="{ open: false }" class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-2 font-semibold">
                    <x-application-logo class="h-7 w-7 text-rose-600" />
                    <span>{{ config('app.name') }}</span>
                </a>

                <nav class="hidden md:flex items-center gap-6 text-sm text-gray-600" aria-label="{{ __('Main') }}">
                    <a href="{{ url('/') }}#features" class="hover:text-gray-900">{{ __('Features') }}</a>
                    <a href="{{ url('/') }}#how-it-works" class="hover:text-gray-900">{{ __('How it works') }}</a>
                    <a href="{{ url('/') }}#security" class="hover:text-gray-900">{{ __('Security') }}</a>
                    <a href="{{ route('help') }}" class="hover:text-gray-900">{{ __('Help') }}</a>
                    <a href="{{ config('app.source_url') }}" class="hover:text-gray-900">GitHub</a>
                </nav>

                <div class="hidden md:flex items-center gap-3 text-sm">
                    <x-language-switcher />
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-700">{{ __('Open dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-gray-700 hover:text-gray-900">{{ __('Log in') }}</a>
                        <a href="{{ route('register') }}" class="rounded-md bg-rose-600 px-4 py-2 font-medium text-white hover:bg-rose-700">{{ __('Register your hospital') }}</a>
                    @endauth
                </div>

                <button type="button" class="md:hidden p-2 text-gray-600" x-on:click="open = ! open" :aria-expanded="open" aria-controls="public-menu" aria-label="{{ __('Menu') }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>

            <div id="public-menu" x-cloak x-show="open" class="md:hidden border-t border-gray-100 px-4 py-3 space-y-2 text-sm">
                <a href="{{ url('/') }}#features" class="block py-1">{{ __('Features') }}</a>
                <a href="{{ url('/') }}#how-it-works" class="block py-1">{{ __('How it works') }}</a>
                <a href="{{ url('/') }}#security" class="block py-1">{{ __('Security') }}</a>
                <a href="{{ route('help') }}" class="block py-1">{{ __('Help') }}</a>
                <a href="{{ config('app.source_url') }}" class="block py-1">GitHub</a>
                <x-language-switcher align="left" class="-ms-3" />
                @auth
                    <a href="{{ route('dashboard') }}" class="block py-1 font-medium">{{ __('Open dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="block py-1 font-medium">{{ __('Log in') }}</a>
                    <a href="{{ route('register') }}" class="block py-1 font-medium text-rose-700">{{ __('Register your hospital') }}</a>
                @endauth
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="border-t border-gray-100 bg-gray-50">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 grid gap-8 sm:grid-cols-3 text-sm text-gray-600">
                <div>
                    <div class="flex items-center gap-2 font-semibold text-gray-900">
                        <x-application-logo class="h-5 w-5 text-rose-600" />
                        {{ config('app.name') }}
                    </div>
                    <p class="mt-2">{{ __('Structured echocardiography reporting for hospitals and clinics.') }}</p>
                </div>
                <div class="space-y-1">
                    <div class="font-semibold text-gray-900">{{ __('Product') }}</div>
                    <a href="{{ url('/') }}#features" class="block hover:text-gray-900">{{ __('Features') }}</a>
                    <a href="{{ route('help') }}" class="block hover:text-gray-900">{{ __('Help center') }}</a>
                    <a href="{{ route('register') }}" class="block hover:text-gray-900">{{ __('Register a hospital') }}</a>
                </div>
                <div class="space-y-1">
                    <div class="font-semibold text-gray-900">{{ __('Open source') }}</div>
                    <a href="{{ config('app.source_url') }}" class="block hover:text-gray-900">{{ __('Source code') }}</a>
                    <a href="{{ config('app.source_url') }}/blob/main/SECURITY.md" class="block hover:text-gray-900">{{ __('Report a vulnerability') }}</a>
                    <a href="{{ config('app.source_url') }}/blob/main/LICENSE" class="block hover:text-gray-900" rel="license">{{ __('License: MIT') }}</a>
                </div>
            </div>
        </footer>
    </body>
</html>
