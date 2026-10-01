<form method="POST" action="{{ route('locale.update') }}" {{ $attributes }}>
    @csrf
    <select name="locale" aria-label="{{ __('Language') }}" onchange="this.form.submit()" class="border-gray-300 rounded-md text-sm py-1 ps-2 pe-8 focus:border-rose-500 focus:ring-rose-500">
        @foreach (config('app.available_locales') as $code => $name)
            <option value="{{ $code }}" lang="{{ $code }}" @selected($code === app()->getLocale())>{{ $name }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="text-sm underline">{{ __('Change') }}</button></noscript>
</form>
