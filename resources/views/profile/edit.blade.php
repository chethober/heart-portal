@php
    $canSign = $user->hospital_id && $user->can('sign-reports');
    $sections = array_filter([
        'account' => __('Account'),
        'password' => __('Password'),
        'two-factor' => __('Two-factor authentication'),
        'signature' => $canSign ? __('Signature') : null,
    ]);
    $initials = collect(preg_split('/\s+/u', trim($user->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Who is signed in, and the state of their account at a glance. --}}
            <div class="p-4 sm:p-6 bg-white shadow sm:rounded-lg flex flex-wrap items-center gap-4">
                <div class="h-14 w-14 shrink-0 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-semibold" aria-hidden="true">{{ $initials }}</div>
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-semibold text-gray-900 truncate">{{ $user->name }}</p>
                    <p class="text-sm text-gray-600 truncate">
                        {{ $user->primaryRole()?->label() }}@if ($user->hospital) · {{ $user->hospital->name }}@endif
                    </p>
                    <p class="text-sm text-gray-500 truncate" dir="ltr">{{ $user->email }}</p>
                </div>
                <ul class="flex flex-wrap gap-2 text-xs">
                    <li>
                        @if ($user->hasVerifiedEmail())
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 font-medium text-green-700 ring-1 ring-green-200">{{ __('Email verified') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-800 ring-1 ring-amber-200">{{ __('Email not verified') }}</span>
                        @endif
                    </li>
                    <li>
                        @if ($user->hasEnabledTwoFactor())
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 font-medium text-green-700 ring-1 ring-green-200">{{ __('Two-factor on') }}</span>
                        @else
                            <a href="#two-factor" class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100">{{ __('Two-factor off') }}</a>
                        @endif
                    </li>
                    @if ($canSign)
                        <li>
                            @if ($user->signature_path)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 font-medium text-green-700 ring-1 ring-green-200">{{ __('Signature on file') }}</span>
                            @else
                                <a href="#signature" class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 font-medium text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100">{{ __('No signature yet') }}</a>
                            @endif
                        </li>
                    @endif
                </ul>
            </div>

            <div class="lg:grid lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-8 lg:items-start">
                <nav aria-label="{{ __('Profile sections') }}" class="mb-6 lg:mb-0 lg:sticky lg:top-6">
                    <ul class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0 text-sm">
                        @foreach ($sections as $id => $label)
                            <li class="shrink-0">
                                <a href="#{{ $id }}" class="block rounded-md px-3 py-2 bg-white shadow-sm lg:shadow-none lg:bg-transparent text-gray-700 hover:bg-white hover:text-gray-900">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="space-y-6 min-w-0">
                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg scroll-mt-6" id="account">
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form', ['canSign' => $canSign])
                        </div>
                    </div>

                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg scroll-mt-6" id="password">
                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg scroll-mt-6">
                        <div class="max-w-xl">
                            @include('profile.partials.two-factor-form')
                        </div>
                    </div>

                    @if ($canSign)
                        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg scroll-mt-6">
                            @include('profile.partials.signature-form')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
