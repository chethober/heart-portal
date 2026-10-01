@php($twoFactor = app(\App\Support\TwoFactor::class))
<section id="two-factor">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Two-factor authentication') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Protect patient data with a code from an authenticator app (Google Authenticator, Microsoft Authenticator, etc.) at every login.') }}</p>
    </header>

    @if (session('status') === 'two-factor-required')
        <div class="mt-4 p-3 rounded-md bg-amber-50 border border-amber-300 text-amber-900 text-sm">{{ __('Your organisation requires two-factor authentication. Set it up to continue.') }}</div>
    @endif

    @if ($user->hasEnabledTwoFactor())
        <p class="mt-4 text-sm font-medium text-green-700">{{ __('Enabled.') }}</p>

        @if (session('showRecoveryCodes'))
            <div class="mt-4">
                <p class="text-sm text-gray-600">{{ __('Store these recovery codes somewhere safe. Each can be used once if you lose your device.') }}</p>
                <div class="mt-2 grid grid-cols-2 gap-1 font-mono text-sm bg-gray-50 p-3 rounded-md">
                    @foreach ($user->two_factor_recovery_codes as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-4 flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <x-input-label for="tf_password_codes" :value="__('Current password')" />
                <x-text-input id="tf_password_codes" name="password" type="password" class="mt-1 block" autocomplete="current-password" required />
            </div>
            <x-secondary-button type="submit">{{ __('New recovery codes') }}</x-secondary-button>
            <x-danger-button type="submit" formaction="{{ route('two-factor.disable') }}" name="_method" value="DELETE">{{ __('Disable') }}</x-danger-button>
        </form>
        <x-input-error :messages="$errors->twoFactor->get('password')" class="mt-2" />
    @elseif ($user->two_factor_secret)
        <div class="mt-4 space-y-3">
            <p class="text-sm text-gray-600">{{ __('Scan this QR code with your authenticator app, then enter the 6-digit code it shows.') }}</p>
            <div class="inline-block p-2 bg-white border rounded">{!! $twoFactor->qrCodeSvg($user) !!}</div>
            <p class="text-xs text-gray-500">{{ __('Setup key:') }} <span class="font-mono">{{ $user->two_factor_secret }}</span></p>
            <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex gap-3 items-end">
                @csrf
                <div>
                    <x-input-label for="tf_code" :value="__('Code')" />
                    <x-text-input id="tf_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="mt-1 block" required autofocus />
                </div>
                <x-primary-button>{{ __('Confirm') }}</x-primary-button>
            </form>
            <x-input-error :messages="$errors->twoFactor->get('code')" class="mt-2" />
        </div>
    @else
        <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-4 flex gap-3 items-end">
            @csrf
            <div>
                <x-input-label for="tf_password" :value="__('Current password')" />
                <x-text-input id="tf_password" name="password" type="password" class="mt-1 block" autocomplete="current-password" required />
            </div>
            <x-primary-button>{{ __('Set up') }}</x-primary-button>
        </form>
        <x-input-error :messages="$errors->twoFactor->get('password')" class="mt-2" />
    @endif
</section>
