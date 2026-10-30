{{--
    Step 1 of a new study: the patient, the study date and the admission, entered once for any
    study form. Each button opens that study form (step 2) for the patient in a taskbar tab.
--}}
@php
    $studies = [
        ...(auth()->user()->can('echo.create') ? ['echo' => __('Echocardiography')] : []),
        ...collect(\App\Support\Forms::procedures(auth()->user(), 'create'))->mapWithKeys(fn (string $key) => [$key => __(\App\Support\Procedures::label($key))])->all(),
    ];
    $values = [...$initial, ...old()];
@endphp
<x-dashboard-layout :title="__('New Study')">
    <link rel="stylesheet" href="/echo-form/persianDatepicker/css/persianDatepicker-default.css">
    <script src="/echo-form/persianDatepicker/js/jquery-1.10.1.min.js"></script>
    <script src="/echo-form/persianDatepicker/js/persianDatepicker.min.js"></script>
    @include('studies.partials.form-styles')

    <div id="echo-app">
        @include('clinical.partials.patient-taskbar')
        <form id="patientForm" method="POST" action="{{ route('studies.start') }}" autocomplete="off">
            @csrf
            @if ($replaces)
                <input type="hidden" name="replaces" value="{{ $replaces }}">
            @endif
            <h2 class="mb-4 text-lg font-semibold">{{ __('Patient details') }}</h2>
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="row">
                @include('clinical.partials.patient-fields', ['values' => $values])
            </div>

            <div class="mt-6 border-t border-gray-200 pt-5">
                <h2 class="mb-3 text-lg font-semibold">{{ __('Start study') }}</h2>
                <div class="flex max-w-md flex-col gap-3">
                    @foreach ($studies as $key => $label)
                        <button type="submit" name="study" value="{{ $key }}" class="ef-btn ef-btn-secondary" style="justify-content:space-between">{{ $label }} &rarr;</button>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    @include('clinical.partials.patient-lookup')
    <script>
        if (window.jQuery?.fn.persianDatepicker) {
            ['dob', 'study_date'].forEach((name) => jQuery('#' + name).persianDatepicker({ formatDate: 'YYYY/0M/0D' }));
        }
    </script>
</x-dashboard-layout>
