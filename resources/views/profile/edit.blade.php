@php
    $canSign = $user->hospital_id && $user->can('sign-reports');
    $initials = collect(preg_split('/\s+/u', trim($user->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $badge = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-medium ring-1 ring-inset';
    $ok = $badge.' bg-green-50 text-green-700 ring-green-600/20';
    $todo = $badge.' bg-amber-50 text-amber-800 ring-amber-600/20 transition-colors hover:bg-amber-100';
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Who is signed in, and the state of their account at a glance; what still needs doing links to its section. --}}
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl px-4 py-5 sm:px-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex min-w-0 flex-1 items-center gap-4">
                    <div class="h-12 w-12 shrink-0 rounded-full bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/10 flex items-center justify-center text-base font-semibold" aria-hidden="true">{{ $initials }}</div>
                    <div class="min-w-0">
                        <p class="text-base font-semibold text-gray-900 truncate">{{ $user->name }}</p>
                        <p class="text-sm text-gray-500 truncate">
                            {{ $user->primaryRole()?->label() }}@if ($user->hospital) · {{ $user->hospital->name }}@endif
                        </p>
                    </div>
                </div>
                <ul class="flex flex-wrap gap-2 text-xs">
                    <li>
                        @if ($user->hasVerifiedEmail())
                            <span class="{{ $ok }}"><span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>{{ __('Email verified') }}</span>
                        @else
                            <a href="#account" class="{{ $todo }}"><span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>{{ __('Email not verified') }}</a>
                        @endif
                    </li>
                    <li>
                        @if ($user->hasEnabledTwoFactor())
                            <span class="{{ $ok }}"><span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>{{ __('Two-factor on') }}</span>
                        @else
                            <a href="#two-factor" class="{{ $todo }}"><span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>{{ __('Two-factor off') }}</a>
                        @endif
                    </li>
                    @if ($canSign)
                        <li>
                            @if ($user->signature_path)
                                <span class="{{ $ok }}"><span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>{{ __('Signature on file') }}</span>
                            @else
                                <a href="#signature" class="{{ $todo }}"><span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>{{ __('No signature yet') }}</a>
                            @endif
                        </li>
                    @endif
                </ul>
            </div>

            @include('profile.partials.update-profile-information-form', ['canSign' => $canSign])
            @include('profile.partials.update-password-form')
            @include('profile.partials.two-factor-form')
            @if ($canSign)
                @include('profile.partials.signature-form')
            @endif
        </div>
    </div>
</x-app-layout>
