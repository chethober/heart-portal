<section id="signature">
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Signature') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Shown on reports you sign. PNG or JPG, ideally on a white or transparent background, up to 1 MB.') }}</p>
    </header>

    @if ($user->signature_path)
        <img src="{{ route('users.signature', $user) }}" alt="{{ __('Your signature') }}" class="mt-4 max-h-20 border rounded p-1 bg-white">
    @endif

    <form method="POST" action="{{ route('profile.signature') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap gap-3 items-end">
        @csrf
        <input type="file" name="signature" accept="image/png,image/jpeg" required class="text-sm">
        <x-primary-button>{{ $user->signature_path ? __('Replace') : __('Upload') }}</x-primary-button>
        @if (session('status') === 'signature-updated')
            <span class="text-sm text-gray-600">{{ __('Saved.') }}</span>
        @endif
    </form>
    <x-input-error :messages="$errors->signature->get('signature')" class="mt-2" />
</section>
