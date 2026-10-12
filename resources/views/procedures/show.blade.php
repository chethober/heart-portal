@php
    /**
     * A saved procedure study: actions, the printable report and its signed versions. The report
     * lists only the fields that were filled in, section by section, in English like the echo report.
     *
     * @var string $procedure
     * @var \App\Models\ProcedureStudy $study
     * @var \App\Models\ProcedureStudyVersion|null $version  Set when viewing an earlier signed version.
     * @var array<string, mixed> $report  Current values, or the version's snapshot.
     */
    use App\Support\JalaliDate;
    use App\Support\Procedures;

    $display = function (array $field, mixed $value): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field['type']) {
            'boolean' => $value ? 'Yes' : null,
            'choice' => $field['options'][$value] ?? (string) $value,
            'number' => is_float($value) && floor($value) === $value ? (string) (int) $value : (string) $value,
            default => (string) $value,
        };
    };
    $sections = collect(Procedures::sections($procedure))
        ->map(fn (array $section) => [
            'title' => $section['title'],
            'rows' => collect($section['fields'])
                ->map(fn (array $field, string $name) => ['label' => $field['label'], 'value' => $display($field, $report[$name] ?? null)])
                ->filter(fn (array $row) => $row['value'] !== null),
        ])
        ->filter(fn (array $section) => $section['rows']->isNotEmpty());

    $buttonPrimary = 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700';
    $buttonSecondary = 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50';
@endphp

<x-dashboard-layout :title="Procedures::label($procedure)">
    <div dir="ltr" lang="en">
        <div class="mb-6 flex flex-wrap items-center gap-3 print:hidden">
            <button type="button" onclick="window.print()" class="{{ $buttonPrimary }}">Print Report</button>
            <a href="{{ route('patients.index', ['nationalID' => $report['nationalID']]) }}" class="{{ $buttonSecondary }}">Back to Patient</a>
            @unless ($version)
                @can('update', $study)
                    <a href="{{ route('procedures.edit', [$procedure, $study]) }}" class="{{ $buttonSecondary }}">{{ $study->isSigned() ? 'Amend Report' : 'Edit Draft' }}</a>
                @endcan
                @can('finalize', $study)
                    <form method="POST" action="{{ route('procedures.finalize', [$procedure, $study]) }}" onsubmit="return confirm('Sign and finalize this report? It can only be changed by amendment afterwards.');">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600">Sign &amp; Finalize</button>
                    </form>
                @endcan
                @can('delete', $study)
                    <form method="POST" action="{{ route('procedures.destroy', [$procedure, $study]) }}" onsubmit="return confirm('Delete this draft?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="{{ $buttonSecondary }} text-red-700">Delete Draft</button>
                    </form>
                @endcan
            @endunless
            <span class="ms-auto">@include('dashboard.status-badge', ['status' => $version?->status ?? $study->status])</span>
        </div>

        @if ($version)
            <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 print:hidden">
                Viewing signed version {{ $version->version }} of {{ $study->versions->count() }}.
                <a href="{{ route('procedures.show', [$procedure, $study]) }}" class="font-medium underline">Show the current report</a>
            </div>
        @endif

        <header class="mb-6 border-b border-gray-200 pb-4">
            <h1 class="text-2xl font-semibold">{{ Procedures::label($procedure) }} Report</h1>
            @unless (($version?->status ?? $study->status)->isSigned())
                <p class="mt-1 font-semibold text-red-700">DRAFT — NOT SIGNED</p>
            @endunless
            <dl class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-3">
                <div><dt class="inline text-gray-500">Patient:</dt> <dd class="inline font-medium">{{ $report['first_name'] }} {{ $report['last_name'] }}</dd></div>
                <div><dt class="inline text-gray-500">National ID:</dt> <dd class="inline">{{ $report['nationalID'] }}</dd></div>
                <div><dt class="inline text-gray-500">Date of birth:</dt> <dd class="inline">{{ $report['dob'] }}</dd></div>
                <div><dt class="inline text-gray-500">Procedure date:</dt> <dd class="inline">{{ $report[config("procedures.{$procedure}.date_field")] ?? '' }}</dd></div>
                @if ($study->admission)
                    <div><dt class="inline text-gray-500">Admission:</dt> <dd class="inline">{{ $study->admission->ward }} {{ $study->admission->admitted_on }}</dd></div>
                @endif
            </dl>
        </header>

        @forelse ($sections as $section)
            <section class="mb-4 break-inside-avoid">
                <h2 class="mb-1 border-b border-gray-100 font-semibold">{{ $section['title'] }}</h2>
                <dl class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                    @foreach ($section['rows'] as $row)
                        <div><dt class="inline text-gray-500">{{ $row['label'] }}:</dt> <dd class="inline whitespace-pre-line">{{ $row['value'] }}</dd></div>
                    @endforeach
                </dl>
            </section>
        @empty
            <p class="text-sm text-gray-500">No findings recorded yet.</p>
        @endforelse

        @php $signer = $version?->signer ?? ($study->isSigned() ? ($study->amender ?? $study->reporter) : null); @endphp
        @if ($signer)
            <footer class="mt-8 border-t border-gray-200 pt-4 text-sm">
                Signed by <strong>{{ $signer->name }}</strong>
                @if ($signer->medical_license_no) (License {{ $signer->medical_license_no }}) @endif
                @if ($signedAt = $version?->created_at ?? $study->amended_at ?? $study->finalized_at)
                    on {{ JalaliDate::format($signedAt) }}
                @endif
                @if ($reason = $version?->reason ?? ($study->amended_at ? $study->amendment_reason : null))
                    <p class="mt-1 text-gray-600">Amendment reason: {{ $reason }}</p>
                @endif
            </footer>
        @endif

        @if ($study->versions->isNotEmpty())
            <section class="mt-8 print:hidden">
                <h2 class="mb-2 font-semibold">Signed versions</h2>
                <ul class="space-y-1 text-sm">
                    @foreach ($study->versions as $v)
                        <li>
                            <a href="{{ route('procedures.show', [$procedure, $study, 'version' => $v->version]) }}" class="text-indigo-600 underline">Version {{ $v->version }}</a>
                            — {{ $v->status->label() }} by {{ $v->signer?->name }}{{ $v->reason ? ': '.$v->reason : '' }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-dashboard-layout>
