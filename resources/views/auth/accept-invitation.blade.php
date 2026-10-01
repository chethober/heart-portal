<x-guest-layout>
    <h2 class="text-lg font-semibold text-gray-900">{{ __('Join :hospital', ['hospital' => $invitation->hospital->name]) }}</h2>
    <p class="mt-1 text-sm text-gray-600">{{ __('You were invited as :role with :email.', ['role' => $invitation->role->label(), 'email' => $invitation->email]) }}</p>

    <form method="POST" action="{{ route('invitations.accept', $invitation->token) }}" class="mt-4">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Full name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="medical_license_no" :value="__('Medical license number (optional)')" />
            <x-text-input id="medical_license_no" class="block mt-1 w-full" type="text" name="medical_license_no" :value="old('medical_license_no')" />
            <x-input-error :messages="$errors->get('medical_license_no')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>{{ __('Create account') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
