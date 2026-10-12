@php
    /**
     * Public verification page opened from a printed prescription's QR code. Shows whether the
     * prescription is genuine and still valid, and what it lists, but nothing about the patient.
     *
     * @var \App\Models\Prescription $prescription
     */
    use App\Support\JalaliDate;
@endphp

<x-public-layout :title="__('Prescription verification')">
    <section class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
        <h1 class="text-2xl font-semibold">{{ __('Prescription verification') }}</h1>

        @if ($prescription->isCancelled())
            <div class="mt-6 rounded-md border border-red-300 bg-red-50 p-4 text-red-900" role="alert">
                <p class="font-semibold">{{ __('This prescription was cancelled by the prescriber and must not be dispensed.') }}</p>
            </div>
        @else
            <div class="mt-6 rounded-md border border-green-300 bg-green-50 p-4 text-green-900" role="status">
                <p class="font-semibold">{{ __('This is a genuine prescription, signed and currently valid.') }}</p>
            </div>
        @endif

        <dl class="mt-6 grid gap-2 text-sm sm:grid-cols-2">
            <div><dt class="inline text-gray-500">{{ __('Prescription number') }}:</dt> <dd class="inline font-medium">{{ $prescription->id }}</dd></div>
            <div><dt class="inline text-gray-500">{{ __('Hospital') }}:</dt> <dd class="inline font-medium">{{ $prescription->hospital->name }}</dd></div>
            <div><dt class="inline text-gray-500">{{ __('Prescriber') }}:</dt> <dd class="inline font-medium">{{ $prescription->signer?->name }}@if ($prescription->signer?->medical_license_no) ({{ $prescription->signer->medical_license_no }})@endif</dd></div>
            <div><dt class="inline text-gray-500">{{ __('Signed on') }}:</dt> <dd class="inline font-medium">{{ JalaliDate::format($prescription->signed_at) }}</dd></div>
        </dl>

        <h2 class="mt-8 font-semibold">{{ __('Prescribed drugs') }}</h2>
        <p class="text-sm text-gray-500">{{ __('Compare with the paper prescription: any difference means it was altered.') }}</p>
        <ol class="mt-3 list-decimal space-y-1 ps-6 text-sm" dir="ltr" lang="en">
            @foreach ($prescription->items as $item)
                <li><strong>{{ $item->drugLabel() }}</strong> — {{ $item->directions() }}@if ($item->quantity) (#{{ $item->quantity }})@endif</li>
            @endforeach
        </ol>
    </section>
</x-public-layout>
