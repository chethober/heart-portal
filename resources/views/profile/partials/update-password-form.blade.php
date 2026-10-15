<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    {{-- Forgotten the current password: the same reset link as the login page's "Forgot your password?". --}}
    <div class="mt-6 border-t border-gray-100 pt-4">
        <p class="text-sm text-gray-600">{{ __("Don't remember your current password? We'll email a reset link to :email.", ['email' => $user->email]) }}</p>
        <form method="post" action="{{ route('profile.password-reset') }}" class="mt-3 flex flex-wrap items-center gap-3">
            @csrf
            <x-secondary-button type="submit">{{ __('Email me a reset link') }}</x-secondary-button>
            @if (session('status') === 'password-reset-link-sent')
                <p class="text-sm text-green-700">{{ __('Reset link sent. Check your inbox; the link expires in :count minutes.', ['count' => config('auth.passwords.users.expire')]) }}</p>
            @endif
        </form>
        <x-input-error :messages="$errors->passwordReset->get('email')" class="mt-2" />
    </div>
</section>
