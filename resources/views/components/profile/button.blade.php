@props(['variant' => 'primary', 'size' => 'md'])
@php
    // The profile page's buttons: sentence case, the app's indigo, and a slight press so a click feels heard.
    $base = 'inline-flex items-center justify-center gap-1.5 rounded-md font-semibold whitespace-nowrap transition-[transform,background-color,box-shadow] duration-150 ease-out active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-40';
    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        // As tall as x-text-input, so a button beside a field lines up with it.
        'md' => 'min-h-[2.625rem] px-4 py-2 text-sm',
    ];
    $variants = [
        'primary' => 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500',
        'secondary' => 'bg-white text-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50',
        'ghost' => 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
        'danger' => 'bg-white text-red-700 shadow-sm ring-1 ring-inset ring-red-200 hover:bg-red-50',
    ];
@endphp
<button {{ $attributes->merge(['type' => 'submit', 'class' => $base.' '.$sizes[$size].' '.$variants[$variant]]) }}>
    {{ $slot }}
</button>
