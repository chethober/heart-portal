@php
    /**
     * The prescriptions section of the patient page.
     *
     * @var \App\Models\Patient $patient
     */
    use App\Support\JalaliDate;

    $patientPrescriptions = \App\Models\Prescription::where('patient_id', $patient->id)->withCount('items')->latest('id')->get();
@endphp

<section>
    <div class="mb-2 flex flex-wrap items-center gap-3">
        <h3 class="text-lg font-semibold">{{ __('Prescriptions') }}</h3>
        @can('prescriptions.create')
            <a href="{{ route('prescriptions.create', ['patient' => $patient->id]) }}" class="text-sm font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('Write a prescription') }}</a>
        @endcan
    </div>
    @if ($patientPrescriptions->isNotEmpty())
        <ul class="divide-y divide-gray-100 text-sm">
            @foreach ($patientPrescriptions as $prescription)
                <li class="flex flex-wrap items-center gap-3 py-2">
                    <span class="whitespace-nowrap">{{ $prescription->prescribed_on ?? JalaliDate::format($prescription->created_at, 'yyyy/MM/dd') }}</span>
                    @include('prescriptions.partials.status-badge', ['status' => $prescription->status])
                    <span class="text-gray-600">{{ __('Drugs: :count', ['count' => $prescription->items_count]) }}@if ($prescription->discharge_study_id) · {{ __('Discharge') }}@endif</span>
                    <a href="{{ route('prescriptions.show', $prescription) }}" class="ms-auto font-medium text-indigo-600 underline hover:text-indigo-500">{{ __('View prescription') }}</a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
