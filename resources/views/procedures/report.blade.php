@php
    /**
     * The printable clinical narrative of a procedure study (included by procedures/show.blade.php when
     * the procedure has a report builder in app/Reports). Laid out like heart's printed reports: hospital
     * header, patient line, the narrative, then signature boxes. It stays in the clinical language
     * (English, or Persian right to left for the discharge summary) whatever the interface language.
     *
     * @var \App\Reports\ProcedureReport $narrative
     * @var \App\Models\ProcedureStudy $study
     * @var \App\Models\ProcedureStudyVersion|null $version
     * @var array<string, mixed> $report
     * @var \App\Models\Hospital|null $hospital
     * @var string $procedure
     */
    use App\Enums\StudyStatus;
    use App\Support\JalaliDate;
    use Illuminate\Support\Facades\Storage;

    $fa = $narrative->language() === 'fa';
    $t = $fa
        ? ['patient' => 'نام بیمار', 'nationalID' => 'کد ملی', 'dob' => 'تاریخ تولد', 'gender' => 'جنسیت', 'date' => 'تاریخ', 'admission' => 'پذیرش', 'physician' => 'پزشک معالج', 'draft' => 'پیش نویس — امضا نشده (DRAFT — NOT SIGNED)', 'other' => 'سایر داده های ثبت شده', 'amended' => 'اصلاح شده', 'license' => 'نظام پزشکی', 'signedOn' => 'تاریخ امضا', 'male' => 'مرد', 'female' => 'زن']
        : ['patient' => 'Patient', 'nationalID' => 'National ID', 'dob' => 'Date of birth', 'gender' => 'Gender', 'date' => 'Procedure date', 'admission' => 'Admission', 'physician' => 'Physician', 'draft' => 'DRAFT — NOT SIGNED', 'other' => 'Other recorded findings', 'amended' => 'Amended', 'license' => 'License', 'signedOn' => 'Signed', 'male' => 'Male', 'female' => 'Female'];

    $shownStatus = $version?->status ?? $study->status;
    $signer = match (true) {
        $version !== null => $version->signer,
        $shownStatus === StudyStatus::Amended => $study->amender,
        $shownStatus === StudyStatus::Final => $study->reporter,
        default => null,
    };
    $signedAt = $version?->created_at ?? ($shownStatus === StudyStatus::Amended ? $study->amended_at : $study->finalized_at);
    $shownVersion = $version ?? ($shownStatus->isSigned() ? $study->versions->last() : null);
    $reason = $version?->reason ?? ($shownStatus === StudyStatus::Amended ? $study->amendment_reason : null);
    $boxes = $narrative->signatureBoxes();

    $patientItems = array_filter([
        $t['patient'] => trim(($report['first_name'] ?? '').' '.($report['last_name'] ?? '')),
        $t['nationalID'] => $report['nationalID'] ?? null,
        $t['dob'] => $report['dob'] ?? null,
        $t['gender'] => match ($report['gender'] ?? null) { 'Male' => $t['male'], 'Female' => $t['female'], default => null },
        $t['date'] => $report[config("procedures.{$procedure}.date_field")] ?? null,
        $t['admission'] => $study->admission ? trim($study->admission->ward.' '.$study->admission->admitted_on) : null,
        $t['physician'] => $study->admission?->physician,
        ...$narrative->headerItems(),
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<style>
    .procedure-report { font-family: system-ui, -apple-system, "Segoe UI", Tahoma, Roboto, Arial, sans-serif; color: #111; line-height: 1.6; font-size: 0.95rem; }
    .procedure-report .pr-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 2px solid #1f2937; padding-bottom: 0.5rem; margin-bottom: 0.75rem; }
    .procedure-report .pr-logo { height: 72px; width: 72px; object-fit: contain; }
    .procedure-report .pr-title { flex: 1; text-align: center; margin: 0; font-size: 1.15rem; font-weight: 700; line-height: 1.5; }
    .procedure-report .pr-patient { display: flex; flex-wrap: wrap; gap: 0.25rem 1.5rem; border: 1px solid #d1d5db; border-radius: 4px; padding: 0.5rem 0.75rem; margin-bottom: 1rem; font-size: 0.9rem; }
    .procedure-report .pr-patient dt { display: inline; color: #4b5563; }
    .procedure-report .pr-patient dd { display: inline; font-weight: 600; margin: 0; }
    .procedure-report .pr-section { margin-bottom: 0.75rem; break-inside: avoid; page-break-inside: avoid; }
    .procedure-report .pr-section h2 { font-size: 1rem; font-weight: 700; border-bottom: 1px solid #e5e7eb; margin: 0 0 0.25rem; }
    .procedure-report .pr-section p { margin: 0 0 0.25rem; white-space: pre-line; }
    .procedure-report .pr-label { font-weight: 700; }
    .procedure-report .pr-appendix { font-size: 0.85rem; color: #374151; }
    .procedure-report .pr-draft { border: 2px solid #c53030; color: #c53030; font-weight: 700; text-align: center; padding: 4px; margin-bottom: 0.75rem; letter-spacing: 2px; }
    .procedure-report .pr-signatures { display: flex; gap: 1rem; margin-top: 2rem; break-inside: avoid; page-break-inside: avoid; }
    .procedure-report .pr-signature { flex: 1; text-align: center; border-top: 1px dashed #9ca3af; padding-top: 0.5rem; min-height: 6rem; font-size: 0.85rem; }
    .procedure-report .pr-signature img { max-height: 60px; max-width: 180px; margin: 0.25rem auto; display: block; }
    @media print {
        @page { size: A4 portrait; margin: 10mm 12mm; }
        .procedure-report { font-size: 10.5pt; }
        .procedure-report .pr-patient { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<article class="procedure-report" dir="{{ $fa ? 'rtl' : 'ltr' }}" lang="{{ $fa ? 'fa' : 'en' }}">
    @unless ($shownStatus->isSigned())
        <div class="pr-draft">{{ $t['draft'] }}</div>
    @endunless

    <header class="pr-header" dir="ltr">
        @if (! empty($hospital?->settings['secondary_logo_path']))
            <img src="{{ Storage::disk('public')->url($hospital->settings['secondary_logo_path']) }}" alt="" class="pr-logo">
        @else
            <span class="pr-logo" aria-hidden="true"></span>
        @endif
        <h1 class="pr-title">
            @if ($hospital)
                <span lang="fa" dir="auto">{{ $hospital->name }}</span><br>
            @endif
            @foreach ($narrative->titles() as $title)
                <span dir="auto">{{ $title }}</span><br>
            @endforeach
        </h1>
        @if ($hospital?->logo_path)
            <img src="{{ Storage::disk('public')->url($hospital->logo_path) }}" alt="{{ $hospital->name }}" class="pr-logo">
        @else
            <span class="pr-logo" aria-hidden="true"></span>
        @endif
    </header>

    <dl class="pr-patient">
        @foreach ($patientItems as $label => $value)
            <div><dt>{{ $label }}:</dt> <dd>{{ $value }}</dd></div>
        @endforeach
    </dl>

    @foreach ($narrative->sections() as $section)
        <section class="pr-section">
            @if ($section['title'])
                <h2>{{ $section['title'] }}</h2>
            @endif
            @foreach ($section['lines'] as $line)
                <p dir="auto">@if ($line['label'])<span class="pr-label">{{ $line['label'] }}:</span> @endif{{ $line['text'] }}</p>
            @endforeach
        </section>
    @endforeach

    @if ($appendix = $narrative->appendix())
        <section class="pr-section pr-appendix">
            <h2>{{ $t['other'] }}</h2>
            @foreach ($appendix as $group)
                <p dir="auto"><span class="pr-label">{{ $group['title'] }}:</span> @foreach ($group['items'] as $item){{ $item['label'] }}: {{ $item['value'] }}@if (! $loop->last){{ $fa ? '؛ ' : '; ' }}@endif @endforeach</p>
            @endforeach
        </section>
    @endif

    @if ($reason && $shownStatus === StudyStatus::Amended)
        <p class="pr-appendix">{{ $t['amended'] }} {{ JalaliDate::format($signedAt) }} — {{ $reason }}</p>
    @endif

    <div class="pr-signatures">
        @foreach ($boxes as $box)
            <div class="pr-signature">
                <strong>{{ $box['title'] }}</strong>
                @if ($loop->last && $signer)
                    <div>{{ $signer->name }}</div>
                    @if ($signer->medical_license_no)
                        <div>{{ $t['license'] }}: {{ $signer->medical_license_no }}</div>
                    @endif
                    @if ($shownVersion?->signature_path)
                        <img src="{{ route('users.signature', [$signer, 'procedure_version' => $shownVersion->id]) }}" alt="Signature">
                    @endif
                    <div>{{ $t['signedOn'] }}: {{ JalaliDate::format($signedAt) }}</div>
                @elseif ($box['name'])
                    <div>{{ $box['name'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
</article>
