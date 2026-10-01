@php
    $navUser = Auth::user();
    // Members of a pending or suspended hospital have no sections to open yet.
    $navLinks = $navUser->isPlatformAdmin() || $navUser->hospital?->isActive()
        ? \App\Support\NavigationLinks::for($navUser)
        : [];
    $navSearch = $navLinks !== [] && $navUser->can('patients.view');
    $navNationalID = request()->routeIs('patients.index') ? (string) request()->query('nationalID', '') : '';
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 print:hidden">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-8 w-auto text-rose-600" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 rtl:space-x-reverse lg:-my-px lg:ms-8 lg:flex">
                    @foreach ($navLinks as $link)
                        <x-nav-link :href="$link['url']" :active="$link['active']" class="gap-2 whitespace-nowrap" :aria-current="$link['active'] ? 'page' : null">
                            {{ $link['label'] }}
                            @if ($link['count'])
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $link['count'] }}</span>
                            @endif
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <!-- Patient search, settings and help -->
            <div class="hidden lg:flex lg:items-center lg:ms-6 gap-4">
                @if ($navSearch)
                    <form method="GET" action="{{ route('patients.index') }}" class="relative" role="search">
                        <label for="nav-national-id" class="sr-only">{{ __('National ID') }}</label>
                        <svg class="pointer-events-none absolute inset-y-0 start-0 ms-3 h-full w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                        </svg>
                        <input id="nav-national-id" name="nationalID" type="search" required
                               value="{{ $navNationalID }}"
                               @if (request()->routeIs('patients.index') && $navNationalID === '') autofocus @endif
                               placeholder="{{ __('National ID') }}"
                               class="no-voice block w-44 rounded-md border-gray-300 py-1.5 ps-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="submit" class="sr-only">{{ __('Search') }}</button>
                    </form>
                @endif
                <x-language-switcher />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ $navUser->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if ($navUser->hospital)
                            <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-100">{{ $navUser->hospital->name }}</div>
                        @endif
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @if ($navUser->hospital_id)
                    <a href="{{ route('help') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0ZM8.94 6.94a.75.75 0 1 1-1.061-1.061 3 3 0 1 1 2.871 5.026v.345a.75.75 0 0 1-1.5 0v-.5c0-.72.57-1.172 1.081-1.287A1.5 1.5 0 1 0 8.94 6.94ZM10 15a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                        {{ __('Help') }}
                    </a>
                @endif
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" aria-label="{{ __('Toggle navigation menu') }}">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden">
        @if ($navSearch)
            <form method="GET" action="{{ route('patients.index') }}" class="flex gap-2 px-4 pt-3" role="search">
                <label for="nav-national-id-mobile" class="sr-only">{{ __('National ID') }}</label>
                <input id="nav-national-id-mobile" name="nationalID" type="search" required
                       value="{{ $navNationalID }}"
                       placeholder="{{ __('National ID') }}"
                       class="no-voice block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('Search') }}</button>
            </form>
        @endif

        <div class="pt-2 pb-3 space-y-1">
            @foreach ($navLinks as $link)
                <x-responsive-nav-link :href="$link['url']" :active="$link['active']">
                    {{ $link['label'] }}
                    @if ($link['count'])
                        <span class="ms-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $link['count'] }}</span>
                    @endif
                </x-responsive-nav-link>
            @endforeach
            @if ($navUser->hospital_id)
                <x-responsive-nav-link :href="route('help')" :active="false">
                    {{ __('Help') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ $navUser->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $navUser->email }}</div>
                @if ($navUser->hospital)
                    <div class="text-sm text-gray-500">{{ $navUser->hospital->name }}</div>
                @endif
            </div>

            <div class="mt-3 space-y-1">
                <x-language-switcher align="left" class="px-1" />
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
