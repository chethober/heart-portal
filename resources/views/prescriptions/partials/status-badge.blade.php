@php
    /** @var \App\Enums\PrescriptionStatus $status */
    [$colors, $label] = match ($status) {
        \App\Enums\PrescriptionStatus::Draft => ['bg-red-50 text-red-700 ring-red-600/30', __('Draft')],
        \App\Enums\PrescriptionStatus::Signed => ['bg-green-50 text-green-700 ring-green-600/30', __('Signed')],
        \App\Enums\PrescriptionStatus::Cancelled => ['bg-gray-100 text-gray-700 ring-gray-500/30', __('Cancelled')],
    };
@endphp
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $colors }}">{{ $label }}</span>
