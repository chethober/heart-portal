{{--
    The multi-patient taskbar: a tab for each study started on the dashboard and not yet saved,
    above step 1 and the new-study forms. Tabs open their study form; the current tab opens its
    patient step to correct the details. Unsaved study fields are kept per patient in this browser,
    so moving between tabs keeps them. "New Patient" starts another at step 1.
    Must sit outside any <form>: each tab's close button is a form of its own.
--}}
@php
    $tabs = \App\Support\StudyContext::tabs(request());
    $current = request('context');
    $onPatientStep = request()->routeIs('dashboard');
@endphp
<div class="patient-bar-wrapper">
    <nav class="patient-tabs-container" aria-label="{{ __('Patients') }}">
        @foreach ($tabs as $tab)
            @php $active = $tab['token'] === $current; @endphp
            <div @class(['patient-tab', 'active' => $active])>
                <a class="patient-tab-title" href="{{ $active && ! $onPatientStep ? route('dashboard', ['context' => $current]) : $tab['url'] }}"
                   title="{{ $tab['nationalID'] }}{{ $active && ! $onPatientStep ? ' — '.__('Patient details') : '' }}" @if ($active) aria-current="page" @endif>
                    {{ $tab['name'] }} <span class="text-xs font-normal opacity-75">{{ $tab['study'] }}</span>
                </a>
                <form method="POST" action="{{ route('studies.close', $tab['token']) }}" class="inline-flex">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="current" value="{{ $current }}">
                    <button type="submit" class="patient-tab-close" aria-label="{{ __('Close') }}: {{ $tab['name'] }}">&times;</button>
                </form>
            </div>
        @endforeach
    </nav>
    <a href="{{ route('dashboard') }}" class="add-patient-btn">+ {{ __('New Patient') }}</a>
</div>
