@php
    $colors = match ($status) {
        \App\Enums\StudyStatus::Draft => 'bg-red-50 text-red-700 ring-red-600/30',
        \App\Enums\StudyStatus::Final => 'bg-green-50 text-green-700 ring-green-600/30',
        \App\Enums\StudyStatus::Amended => 'bg-yellow-50 text-yellow-800 ring-yellow-600/30',
    };
@endphp
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $colors }}">{{ __($status->label()) }}</span>
