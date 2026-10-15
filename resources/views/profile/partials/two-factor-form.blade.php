@php($twoFactor = app(\App\Support\TwoFactor::class))
<x-profile.section id="two-factor" :title="__('Two-factor authentication')" :description="__('Protect patient data with a code from an authenticator app (Google Authenticator, Microsoft Authenticator, etc.) at every login.')">
    <div class="max-w-xl">
        @if (session('status') === 'two-factor-required')
            <div class="mb-5 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900 ring-1 ring-inset ring-amber-600/20">{{ __('Your organisation requires two-factor authentication. Set it up to continue.') }}</div>
        @endif

        @if ($user->hasEnabledTwoFactor())
            <p class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>{{ __('Enabled.') }}
            </p>

            @if (session('showRecoveryCodes'))
                <div class="mt-5">
                    <p class="text-sm text-gray-600">{{ __('Store these recovery codes somewhere safe. Each can be used once if you lose your device.') }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 rounded-lg bg-gray-50 p-4 font-mono text-sm ring-1 ring-inset ring-gray-200" dir="ltr">
                        @foreach ($user->two_factor_recovery_codes as $code)
                            <div>{{ $code }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                @csrf
                <div class="sm:w-64">
                    <x-input-label for="tf_password_codes" :value="__('Current password')" />
                    <x-text-input id="tf_password_codes" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" required />
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-profile.button variant="secondary">{{ __('New recovery codes') }}</x-profile.button>
                    <x-profile.button variant="danger" formaction="{{ route('two-factor.disable') }}" name="_method" value="DELETE">{{ __('Disable') }}</x-profile.button>
                </div>
            </form>
            <x-input-error :messages="$errors->twoFactor->get('password')" class="mt-2" />
        @elseif ($user->two_factor_secret)
            <div class="space-y-4">
                <p class="text-sm text-gray-600">{{ __('Scan this QR code with your authenticator app, then enter the 6-digit code it shows.') }}</p>
                <div class="inline-block rounded-lg bg-white p-3 ring-1 ring-gray-200">{!! $twoFactor->qrCodeSvg($user) !!}</div>
                <p class="text-xs text-gray-500">{{ __('Setup key:') }} <span class="select-all font-mono text-gray-700" dir="ltr">{{ $user->two_factor_secret }}</span></p>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="sm:w-48">
                        <x-input-label for="tf_code" :value="__('Code')" />
                        <x-text-input id="tf_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="mt-1 block w-full font-mono tracking-widest" dir="ltr" required autofocus />
                    </div>
                    <x-profile.button class="self-start sm:self-auto">{{ __('Confirm') }}</x-profile.button>
                </form>
                <x-input-error :messages="$errors->twoFactor->get('code')" class="mt-2" />
            </div>
        @else
            <form method="POST" action="{{ route('two-factor.enable') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="sm:w-64">
                    <x-input-label for="tf_password" :value="__('Current password')" />
                    <x-text-input id="tf_password" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" required />
                </div>
                <x-profile.button class="self-start sm:self-auto">{{ __('Set up') }}</x-profile.button>
            </form>
            <x-input-error :messages="$errors->twoFactor->get('password')" class="mt-2" />
        @endif
    </div>
</x-profile.section>
