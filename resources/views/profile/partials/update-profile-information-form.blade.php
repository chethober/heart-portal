<x-profile.section id="account" :title="__('Account')" :description="__('Update your account\'s profile information and email address.')">
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="max-w-xl space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" dir="ltr" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900 ring-1 ring-inset ring-amber-600/20">
                    {{ __('Your email address is unverified.') }}
                    <button form="send-verification" class="font-medium underline underline-offset-2 hover:text-amber-700 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 font-medium text-green-700">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        @if ($canSign ?? false)
            <div>
                <x-input-label for="medical_license_no" :value="__('Medical license number')" />
                <x-text-input id="medical_license_no" name="medical_license_no" type="text" class="mt-1 block w-full" :value="old('medical_license_no', $user->medical_license_no)" dir="ltr" />
                <p class="mt-1 text-xs text-gray-500">{{ __('Printed under your signature on reports.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('medical_license_no')" />
            </div>
        @endif

        <div class="flex items-center gap-4 pt-1">
            <x-profile.button>{{ __('Save') }}</x-profile.button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition.opacity.duration.200ms
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-700"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</x-profile.section>
