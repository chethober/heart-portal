<footer {{ $attributes->merge(['class' => 'text-xs text-gray-500']) }}>
    {!! __(':app is free software under the :license.', [
        'app' => e(config('app.name')),
        'license' => '<a href="https://www.gnu.org/licenses/agpl-3.0.html" class="underline hover:text-gray-700" rel="license">AGPL-3.0</a>',
    ]) !!}
    <a href="{{ config('app.source_url') }}" class="underline hover:text-gray-700">{{ __('Source code') }}</a>
</footer>
