<x-guest-layout>
    <div x-data="{ recovery: {{ $errors->has('recovery_code') ? 'true' : 'false' }} }">
        <p class="mb-4 text-sm text-gray-600" x-show="! recovery">{{ __('Enter the 6-digit code from your authenticator app.') }}</p>
        <p class="mb-4 text-sm text-gray-600" x-cloak x-show="recovery">{{ __('Enter one of your emergency recovery codes.') }}</p>

        <form method="POST" action="{{ route('two-factor.challenge') }}">
            @csrf

            <div x-show="! recovery">
                <x-input-label for="code" :value="__('Code')" />
                <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div x-cloak x-show="recovery">
                <x-input-label for="recovery_code" :value="__('Recovery code')" />
                <x-text-input id="recovery_code" class="block mt-1 w-full" type="text" name="recovery_code" autocomplete="off" />
                <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <button type="button" class="text-sm text-gray-600 underline" x-on:click="recovery = ! recovery; $nextTick(() => document.getElementById(recovery ? 'recovery_code' : 'code').focus())">
                    <span x-show="! recovery">{{ __('Use a recovery code') }}</span>
                    <span x-cloak x-show="recovery">{{ __('Use an authenticator code') }}</span>
                </button>
                <x-primary-button>{{ __('Log in') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
