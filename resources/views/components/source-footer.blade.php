<footer {{ $attributes->merge(['class' => 'text-xs text-gray-500']) }}>
    {!! __(':app is free software under the :license.', [
        'app' => e(config('app.name')),
        'license' => '<a href="'.e(config('app.source_url')).'/blob/main/LICENSE" class="underline hover:text-gray-700" rel="license">MIT License</a>',
    ]) !!}
    <a href="{{ config('app.source_url') }}" class="underline hover:text-gray-700">{{ __('Source code') }}</a>
</footer>
