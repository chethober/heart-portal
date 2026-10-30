@php
    // Step 2 of a study: the patient chosen on the dashboard, sent with the form as hidden fields.
    // A new study shows the patient in the taskbar (patient-taskbar); a saved one shows them here.
    $sharedValues = $sharedValues ?? [];
    $sharedDateField = $sharedDateField ?? 'echo_date';
    $shared = [...collect($sharedValues)->only([...config('echo.patient_fields'), 'admission_id', $sharedDateField])->all()];
    $admissionNumber = $study?->admission?->his_id;
@endphp
@if ($study)
<div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="font-semibold">{{ $shared['first_name'] ?? '' }} {{ $shared['last_name'] ?? '' }}</p>
        <a href="{{ route('patients.edit', $study->patient) }}" class="text-sm font-medium text-indigo-600 underline">{{ __('Patient details') }}</a>
    </div>
    <dl class="mt-3 grid gap-x-5 gap-y-2 text-sm sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([__('National ID') => $shared['nationalID'] ?? null, __('Date of birth') => $shared['dob'] ?? null, __('Phone number') => $shared['phone_number'] ?? null, __('Gender') => filled($shared['gender'] ?? null) ? __($shared['gender']) : null, __('Date of study') => $shared[$sharedDateField] ?? null, __('Admission number') => $admissionNumber] as $label => $detail)
            <div><dt class="text-gray-500">{{ $label }}</dt><dd class="font-medium">{{ filled($detail) ? $detail : '—' }}</dd></div>
        @endforeach
    </dl>
</div>
@endif
@foreach ([...config('echo.patient_fields'), 'admission_id', $sharedDateField] as $name)
    <input type="hidden" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $shared[$name] ?? '') }}">
@endforeach
