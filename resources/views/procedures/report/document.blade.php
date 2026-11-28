@php
    /**
     * The shared template every procedure report prints in, laid out like the echo report
     * (studies/partials/report-document.php) and styled by the same stylesheet
     * (studies/partials/report-styles.php): draft banner, hospital header and report title, the
     * patient and study line, then the study's own body, the amendment note and the signature boxes.
     * The body is the narrative of the procedure's report class (app/Reports, report/narrative),
     * or the filled fields section by section for a procedure without one (report/fields).
     * Shared by the report page and the form's live preview.
     *
     * The template and every label follow the interface language (right to left for Persian and
     * Arabic); the narrative's sentences and the form's captions stay in its clinical language.
     *
     * @var string $procedure
     * @var array<string, mixed> $report  Current values, a signed snapshot, or unsaved form data.
     * @var \App\Reports\ProcedureReport|null $narrative
     * @var \App\Models\Hospital|null $hospital
     * @var \App\Models\ProcedureStudy|null $study  Null in the live preview.
     * @var \App\Models\ProcedureStudyVersion|null $version  Set when viewing an earlier signed version.
     * @var \App\Models\Admission|null $admission  The linked admission (for the preview, before saving).
     */
    use App\Enums\StudyStatus;
    use App\Support\JalaliDate;
    use App\Support\Procedures;
    use Illuminate\Support\Facades\Storage;

    $study ??= null;
    $version ??= null;
    $admission ??= $study?->admission;
    $locale = app()->getLocale();
    $rtl = in_array($locale, config('app.rtl_locales'), true);

    $shownStatus = $version?->status ?? $study?->status ?? StudyStatus::Draft;
    $signer = match (true) {
        $version !== null => $version->signer,
        $shownStatus === StudyStatus::Amended => $study?->amender,
        $shownStatus === StudyStatus::Final => $study?->reporter,
        default => null,
    };
    $signedAt = $version?->created_at ?? ($shownStatus === StudyStatus::Amended ? $study?->amended_at : $study?->finalized_at);
    $shownVersion = $version ?? ($shownStatus->isSigned() ? $study?->versions->last() : null);
    $reason = $version?->reason ?? ($shownStatus === StudyStatus::Amended ? $study?->amendment_reason : null);

    $title = $narrative ? $narrative->title() : __(':procedure Report', ['procedure' => __(Procedures::label($procedure))]);
    $boxes = $narrative ? $narrative->signatureBoxes() : [['title' => __('Reporting physician'), 'name' => null]];
    // Sign-off steps signed before the final signature (the discharge summary's intern and resident).
    $signoffs = $study?->signoffs() ?? [];
    // A doctor who signed more than one step is printed once, in the last (most senior) box they signed.
    $boxSigners = array_map(fn (array $box, int $i) => $i === count($boxes) - 1
        ? $signer?->id
        : ($signoffs[$box['step'] ?? '']['signer'] ?? null)?->id, $boxes, array_keys($boxes));
    $repeatedBoxes = array_keys(array_filter($boxSigners, fn (?int $id, int $i) => $id !== null
        && in_array($id, array_slice($boxSigners, $i + 1), true), ARRAY_FILTER_USE_BOTH));

    $patientItems = array_filter([
        __('Patient') => trim(($report['first_name'] ?? '').' '.($report['last_name'] ?? '')),
        __('National ID') => $report['nationalID'] ?? null,
        __('Date of birth') => $report['dob'] ?? null,
        __('Gender') => match ($report['gender'] ?? null) { 'Male' => __('Male'), 'Female' => __('Female'), default => null },
        __('Phone') => $report['phone_number'] ?? null,
        __('Procedure date') => $report[config("procedures.{$procedure}.date_field")] ?? null,
        __('Admission') => $admission ? trim($admission->ward.' '.$admission->admitted_on) : null,
        __('Physician') => $admission?->physician,
        ...($narrative ? $narrative->headerItems() : []),
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<div class="report-container" dir="{{ $rtl ? 'rtl' : 'ltr' }}" lang="{{ str_replace('_', '-', $locale) }}" @if ($rtl) style="direction: rtl; text-align: right;" @endif>
    @unless ($shownStatus->isSigned())
        <div style="border:2px solid #c53030;color:#c53030;font-weight:700;text-align:center;padding:6px;margin-bottom:10px;letter-spacing:2px">{{ $signoffs ? __('DRAFT — AWAITING SIGNATURES') : __('DRAFT — NOT SIGNED') }}</div>
    @endunless

    <div class="header" dir="ltr" style="position: relative; padding: 15px 0 5px; display: flex; align-items: center; justify-content: space-between;">
        @if (! empty($hospital?->settings['secondary_logo_path']))
            <img src="{{ Storage::disk('public')->url($hospital->settings['secondary_logo_path']) }}" alt="" class="header-logo">
        @else
            <span class="header-logo" aria-hidden="true"></span>
        @endif
        <h1 style="margin: 0; padding: 0 20px; line-height: 1.4; flex-grow: 1; text-align: center;">
            @if ($hospital)
                <span dir="auto">{{ $hospital->name }}</span><br>
            @endif
            <span dir="auto">{{ $title }}</span>
        </h1>
        @if ($hospital?->logo_path)
            <img src="{{ Storage::disk('public')->url($hospital->logo_path) }}" alt="{{ $hospital->name }}" class="header-logo">
        @else
            <span class="header-logo" aria-hidden="true"></span>
        @endif
    </div>

    <div class="section">
        <div class="patient-info-line" style="direction: {{ $rtl ? 'rtl' : 'ltr' }};">
            @foreach ($patientItems as $label => $value)
                <span class="patient-info-item"><span class="label">{{ $label }}:</span><span class="value" dir="auto">{{ $value }}</span></span>
            @endforeach
        </div>
    </div>

    @if ($narrative)
        @include('procedures.report.narrative', ['narrative' => $narrative, 'rtl' => $rtl])
    @else
        @include('procedures.report.fields', ['procedure' => $procedure, 'report' => $report])
    @endif

    @if ($reason && $shownStatus === StudyStatus::Amended)
        <div class="section" style="font-size:12px;color:#4a5568;padding:0 10px">
            <strong>{{ __('Amended') }}</strong> {{ JalaliDate::format($signedAt) }} — {{ $reason }}
        </div>
    @endif

    <div class="section doctor-signature" style="margin-top: 20px; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 16px; padding: 0 10px;">
        @foreach ($boxes as $box)
            <div style="text-align: center; min-width: 200px; flex: {{ count($boxes) > 1 ? '1' : '0 0 auto' }}; border-top: 1px dashed #ccc; padding-top: 8px; min-height: 5rem;">
                <strong>{{ $box['title'] }}</strong>
                @if ($loop->last && $signer)
                    <div>{{ $signer->name }}</div>
                    @if ($signer->medical_license_no)
                        <div style="font-size:12px">{{ __('License') }}: {{ $signer->medical_license_no }}</div>
                    @endif
                    @if ($shownVersion?->signature_path)
                        <img src="{{ route('users.signature', [$signer, 'procedure_version' => $shownVersion->id]) }}" alt="{{ __('Signature') }}" style="max-height:60px;max-width:200px;display:block;margin:4px auto">
                    @endif
                    <div style="font-size:12px">{{ __('Signed on') }}: {{ JalaliDate::format($signedAt) }}</div>
                @elseif (! in_array($loop->index, $repeatedBoxes, true) && ($signoff = $signoffs[$box['step'] ?? ''] ?? null) && $signoff['signer'])
                    <div>{{ $signoff['signer']->name }}</div>
                    @if ($signoff['signer']->medical_license_no)
                        <div style="font-size:12px">{{ __('License') }}: {{ $signoff['signer']->medical_license_no }}</div>
                    @endif
                    @if ($signoff['signature_path'])
                        <img src="{{ route('users.signature', [$signoff['signer'], 'discharge' => $study->id, 'step' => $box['step']]) }}" alt="{{ __('Signature') }}" style="max-height:60px;max-width:200px;display:block;margin:4px auto">
                    @endif
                    <div style="font-size:12px">{{ __('Signed on') }}: {{ JalaliDate::format($signoff['signed_at']) }}</div>
                @elseif ($box['name'])
                    <div>{{ $box['name'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
