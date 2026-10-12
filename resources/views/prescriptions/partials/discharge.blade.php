@php
    /**
     * Discharge medications on a discharge summary's report (heart's drugDischarge_ep widget): the
     * summary's current prescription, with links to write or open it. As in heart's report, the
     * list is left out when the patient died (disch_status 4).
     *
     * @var \App\Models\DischargeStudy $study
     * @var array<string, mixed> $report
     */
    $dischargePrescription = $study->prescriptions()->with('items')->get()->first(fn ($prescription) => ! $prescription->isCancelled());
    $deceased = (string) ($report['disch_status'] ?? '') === '4';
@endphp

@unless ($deceased)
    <section class="mb-4 break-inside-avoid" dir="ltr" lang="en">
        <h2 class="mb-1 flex flex-wrap items-center gap-3 border-b border-gray-100 font-semibold">
            Discharge medications
            @if ($dischargePrescription)
                <a href="{{ route('prescriptions.show', $dischargePrescription) }}" class="text-sm font-medium text-indigo-600 underline print:hidden">Open prescription</a>
            @else
                @can('studies.create')
                    <a href="{{ route('prescriptions.create', ['discharge' => $study->id]) }}" class="text-sm font-medium text-indigo-600 underline print:hidden">Write discharge prescription</a>
                @endcan
            @endif
        </h2>
        @if ($dischargePrescription && $dischargePrescription->items->isNotEmpty())
            @if ($dischargePrescription->isDraft())
                <p class="text-sm font-semibold text-red-700">Prescription not signed yet.</p>
            @endif
            <ol class="list-decimal space-y-0.5 ps-6 text-sm">
                @foreach ($dischargePrescription->items as $item)
                    <li>
                        <span class="font-medium">{{ $item->drugLabel() }}</span> — {{ $item->directions() }}
                        @if ($item->instructions)
                            <span dir="auto">({{ $item->instructions }})</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        @else
            <p class="text-sm text-gray-500">No discharge prescription.</p>
        @endif
    </section>
@endunless
