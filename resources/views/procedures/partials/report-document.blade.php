@php
    /**
     * The printable report of a procedure study, laid out like the echo report (studies/partials/
     * report-document.php) and styled by the same stylesheet (studies/partials/report-styles.php):
     * hospital header, patient line, findings, summary box and signature. Procedures with a
     * narrative builder (app/Reports) print its sentences; the others list their filled fields.
     * Shared by the report page and the form's live preview. It stays in the clinical language
     * (English, or Persian right to left for the discharge summary) whatever the interface language.
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
    $rtl = $narrative ? $narrative->language() === 'fa' : Procedures::dir($procedure) === 'rtl';
    $t = $rtl
        ? ['patient' => 'نام بیمار', 'nationalID' => 'کد ملی', 'dob' => 'تاریخ تولد', 'gender' => 'جنسیت', 'phone' => 'شماره تماس', 'date' => 'تاریخ', 'admission' => 'پذیرش', 'physician' => 'پزشک معالج', 'draft' => 'پیش نویس — امضا نشده (DRAFT — NOT SIGNED)', 'awaiting' => 'پیش نویس — در انتظار امضا (DRAFT — AWAITING SIGNATURES)', 'other' => 'سایر داده های ثبت شده', 'none' => 'هنوز یافته ای ثبت نشده است.', 'amended' => 'اصلاح شده', 'license' => 'نظام پزشکی', 'signedOn' => 'تاریخ امضا', 'reporter' => 'پزشک گزارش دهنده', 'male' => 'مرد', 'female' => 'زن', 'yes' => 'بله']
        : ['patient' => 'Patient', 'nationalID' => 'National ID', 'dob' => 'Date of birth', 'gender' => 'Gender', 'phone' => 'Phone', 'date' => 'Procedure date', 'admission' => 'Admission', 'physician' => 'Physician', 'draft' => 'DRAFT — NOT SIGNED', 'awaiting' => 'DRAFT — AWAITING SIGNATURES', 'other' => 'Other recorded findings', 'none' => 'No findings recorded yet.', 'amended' => 'Amended', 'license' => 'License', 'signedOn' => 'Signed', 'reporter' => 'Reporting physician', 'male' => 'Male', 'female' => 'Female', 'yes' => 'Yes'];

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

    $titles = $narrative ? $narrative->titles() : [Procedures::label($procedure).' Report'];
    $boxes = $narrative ? $narrative->signatureBoxes() : [['title' => $t['reporter'], 'name' => null]];
    // Sign-off steps signed before the final signature (the discharge summary's intern and resident).
    $signoffs = $study?->signoffs() ?? [];

    $patientItems = array_filter([
        $t['patient'] => trim(($report['first_name'] ?? '').' '.($report['last_name'] ?? '')),
        $t['nationalID'] => $report['nationalID'] ?? null,
        $t['dob'] => $report['dob'] ?? null,
        $t['gender'] => match ($report['gender'] ?? null) { 'Male' => $t['male'], 'Female' => $t['female'], default => null },
        $t['phone'] => $report['phone_number'] ?? null,
        $t['date'] => $report[config("procedures.{$procedure}.date_field")] ?? null,
        $t['admission'] => $admission ? trim($admission->ward.' '.$admission->admitted_on) : null,
        $t['physician'] => $admission?->physician,
        ...($narrative ? $narrative->headerItems() : []),
    ], fn ($value) => $value !== null && $value !== '');

    // Without a narrative builder, every filled field is listed section by section.
    $display = function (array $field, mixed $value) use ($t): ?string {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return match ($field['type']) {
            'boolean' => $t['yes'],
            'choice' => $field['options'][$value] ?? (string) $value,
            'number' => is_float($value) && floor($value) === $value ? (string) (int) $value : (string) $value,
            'diagnoses' => is_array($value) ? (implode('; ', App\Support\Diagnoses::labels($value)) ?: null) : (string) $value,
            default => (string) $value,
        };
    };
    $fieldSections = $narrative ? collect() : collect(Procedures::sections($procedure))
        ->map(fn (array $section) => [
            'title' => $section['title'],
            'rows' => collect($section['fields'])
                ->map(fn (array $field, string $name) => ['label' => $field['label'], 'value' => $display($field, $report[$name] ?? null)])
                ->filter(fn (array $row) => $row['value'] !== null)
                ->values(),
        ])
        ->filter(fn (array $section) => $section['rows']->isNotEmpty());
@endphp

<div class="report-container" dir="{{ $rtl ? 'rtl' : 'ltr' }}" lang="{{ $rtl ? 'fa' : 'en' }}" @if ($rtl) style="direction: rtl; text-align: right;" @endif>
    @unless ($shownStatus->isSigned())
        <div style="border:2px solid #c53030;color:#c53030;font-weight:700;text-align:center;padding:6px;margin-bottom:10px;letter-spacing:2px">{{ $signoffs ? $t['awaiting'] : $t['draft'] }}</div>
    @endunless

    <div class="header" dir="ltr" style="position: relative; padding: 15px 0 5px; display: flex; align-items: center; justify-content: space-between;">
        @if (! empty($hospital?->settings['secondary_logo_path']))
            <img src="{{ Storage::disk('public')->url($hospital->settings['secondary_logo_path']) }}" alt="" class="header-logo">
        @else
            <span class="header-logo" aria-hidden="true"></span>
        @endif
        <h1 style="margin: 0; padding: 0 20px; line-height: 1.4; flex-grow: 1; text-align: center;">
            @if ($hospital)
                <span lang="fa" dir="auto">{{ $hospital->name }}</span><br>
            @endif
            @foreach ($titles as $title)
                <span dir="auto">{{ $title }}</span>@unless ($loop->last)<br>@endunless
            @endforeach
        </h1>
        @if ($hospital?->logo_path)
            <img src="{{ Storage::disk('public')->url($hospital->logo_path) }}" alt="{{ $hospital->name }}" class="header-logo">
        @else
            <span class="header-logo" aria-hidden="true"></span>
        @endif
    </div>

    <div class="section">
        <div class="patient-info-line" @unless ($rtl) style="direction: ltr;" @endunless>
            @foreach ($patientItems as $label => $value)
                <span class="patient-info-item"><span class="label">{{ $label }}:</span><span class="value" dir="auto">{{ $value }}</span></span>
            @endforeach
        </div>
    </div>

    @if ($narrative)
        @foreach ($narrative->sections() as $section)
            <div class="section">
                @if ($section['title'])
                    <div class="section-title">{{ $section['title'] }}</div>
                @endif
                @if (! empty($section['table']))
                    <div class="table-responsive">
                        <table>
                            @foreach ($section['table'] as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        @if ($loop->parent->first)<th style="text-align:start;padding:6px 8px;border:1px solid #edf2f7;background:#f8fafc">{{ $cell }}</th>@else<td>{{ $cell }}</td>@endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
                @foreach ($section['lines'] as $line)
                    <p dir="auto" style="margin: 0 0 6px; white-space: pre-line;">@if ($line['label'])<span class="label">{{ $line['label'] }}:</span> @endif{{ $line['text'] }}</p>
                @endforeach
            </div>
        @endforeach

        @if ($appendix = $narrative->appendix())
            <div class="section">
                <div class="report-summary">
                    <div class="section-title">{{ $t['other'] }}</div>
                    @foreach ($appendix as $group)
                        <p dir="auto" style="margin: 0 0 6px;"><strong>{{ $group['title'] }}:</strong> @foreach ($group['items'] as $item){{ $item['label'] }}: {{ $item['value'] }}@if (! $loop->last){{ $rtl ? '؛ ' : '; ' }}@endif @endforeach</p>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        @forelse ($fieldSections as $section)
            <div class="section">
                <div class="section-title">{{ $section['title'] }}</div>
                <div class="table-responsive">
                    <table>
                        <tbody>
                            @foreach ($section['rows']->chunk(2) as $pair)
                                <tr>
                                    @foreach ($pair as $row)
                                        <td style="width: 25%; font-weight: 600; color: #2c3e50;">{{ $row['label'] }}</td>
                                        <td style="width: 25%; white-space: pre-line;" dir="auto">{{ $row['value'] }}</td>
                                    @endforeach
                                    @if ($pair->count() === 1)
                                        <td style="width: 25%"></td><td style="width: 25%"></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="section"><p style="color: #6b7280;">{{ $t['none'] }}</p></div>
        @endforelse
    @endif

    @if ($reason && $shownStatus === StudyStatus::Amended)
        <div class="section" style="font-size:12px;color:#4a5568;padding:0 10px">
            <strong>{{ $t['amended'] }}</strong> {{ JalaliDate::format($signedAt) }} — {{ $reason }}
        </div>
    @endif

    <div class="section doctor-signature" style="margin-top: 20px; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 16px; padding: 0 10px;">
        @foreach ($boxes as $box)
            <div style="text-align: center; min-width: 200px; flex: {{ count($boxes) > 1 ? '1' : '0 0 auto' }}; border-top: 1px dashed #ccc; padding-top: 8px; min-height: 5rem;">
                <strong>{{ $box['title'] }}</strong>
                @if ($loop->last && $signer)
                    <div>{{ $signer->name }}</div>
                    @if ($signer->medical_license_no)
                        <div style="font-size:12px">{{ $t['license'] }}: {{ $signer->medical_license_no }}</div>
                    @endif
                    @if ($shownVersion?->signature_path)
                        <img src="{{ route('users.signature', [$signer, 'procedure_version' => $shownVersion->id]) }}" alt="Signature" style="max-height:60px;max-width:200px;display:block;margin:4px auto">
                    @endif
                    <div style="font-size:12px">{{ $t['signedOn'] }}: {{ JalaliDate::format($signedAt) }}</div>
                @elseif (($signoff = $signoffs[$box['step'] ?? ''] ?? null) && $signoff['signer'])
                    <div>{{ $signoff['signer']->name }}</div>
                    @if ($signoff['signer']->medical_license_no)
                        <div style="font-size:12px">{{ $t['license'] }}: {{ $signoff['signer']->medical_license_no }}</div>
                    @endif
                    @if ($signoff['signature_path'])
                        <img src="{{ route('users.signature', [$signoff['signer'], 'discharge' => $study->id, 'step' => $box['step']]) }}" alt="Signature" style="max-height:60px;max-width:200px;display:block;margin:4px auto">
                    @endif
                    <div style="font-size:12px">{{ $t['signedOn'] }}: {{ JalaliDate::format($signoff['signed_at']) }}</div>
                @elseif ($box['name'])
                    <div>{{ $box['name'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
