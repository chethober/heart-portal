<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <h2 class="text-lg font-semibold text-gray-900">{{ __('Register your hospital') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Your hospital will be reviewed before staff can record echo studies.') }}</p>

        <div class="mt-4">
            <x-input-label for="hospital_name" :value="__('Hospital name')" />
            <x-text-input id="hospital_name" class="block mt-1 w-full" type="text" name="hospital_name" :value="old('hospital_name')" required autofocus />
            <x-input-error :messages="$errors->get('hospital_name')" class="mt-2" />
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="hospital_city" :value="__('City')" />
                <x-text-input id="hospital_city" class="block mt-1 w-full" type="text" name="hospital_city" :value="old('hospital_city')" required />
                <x-input-error :messages="$errors->get('hospital_city')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="hospital_phone" :value="__('Phone')" />
                <x-text-input id="hospital_phone" class="block mt-1 w-full" type="tel" name="hospital_phone" :value="old('hospital_phone')" required />
                <x-input-error :messages="$errors->get('hospital_phone')" class="mt-2" />
            </div>
        </div>

        <h3 class="mt-6 text-sm font-semibold text-gray-900">{{ __('Administrator account') }}</h3>

        <!-- Name -->
        <div class="mt-2">
            <x-input-label for="name" :value="__('Your name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
