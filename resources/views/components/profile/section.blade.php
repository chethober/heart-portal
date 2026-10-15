@props(['id', 'title', 'description' => null])
{{-- One settings card: what the section is for on the left, its form on the right (stacked on small screens). --}}
<section id="{{ $id }}" {{ $attributes->merge(['class' => 'scroll-mt-24 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl']) }}>
    <div class="grid gap-x-8 gap-y-5 px-4 py-6 sm:px-6 lg:grid-cols-3 lg:py-8">
        <header>
            <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-sm leading-6 text-gray-500">{{ $description }}</p>
            @endif
        </header>
        <div class="min-w-0 lg:col-span-2">
            {{ $slot }}
        </div>
    </div>
</section>
