@php
    /**
     * A saved study's report inside the dashboard: actions, the printable report document and its
     * signed versions. The document stays in its clinical languages whatever the interface language.
     *
     * @var array<string, mixed> $report  Output of EchoCalculations::derive().
     * @var \App\Models\Hospital $hospital
     * @var \App\Models\EchoStudy $study
     * @var \App\Models\EchoStudyVersion|null $version  Set when viewing an earlier signed version.
     */
    use App\Support\JalaliDate;

    $patientEchos = route('patients.index', ! empty($report['nationalID']) ? ['nationalID' => $report['nationalID']] : []);
    $buttonPrimary = 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700';
    $buttonSecondary = 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50';
@endphp

<x-dashboard-layout :title="__('View report')">
    @include('studies.partials.report-styles')

    <div dir="ltr" lang="en">
        <div class="mb-6 flex flex-wrap items-center gap-3 print:hidden">
            <button type="button" onclick="window.print()" class="{{ $buttonPrimary }}">Print Report</button>
            <a href="{{ $patientEchos }}" class="{{ $buttonSecondary }}">Back to Patient Echos</a>
            @unless ($version)
                @can('update', $study)
                    <a href="{{ route('studies.edit', $study) }}" class="{{ $buttonSecondary }}">{{ $study->isSigned() ? 'Amend Report' : 'Edit Draft' }}</a>
                @endcan
                @can('finalize', $study)
                    <form method="POST" action="{{ route('studies.finalize', $study) }}" onsubmit="return confirm('Sign and finalize this report? It can only be changed by amendment afterwards.');">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600">Sign &amp; Finalize</button>
                    </form>
                @endcan
            @endunless
            <span class="ms-auto">@include('dashboard.status-badge', ['status' => $version?->status ?? $study->status])</span>
        </div>

        @if ($version)
            <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 print:hidden">
                Showing signed version {{ $version->version }} of {{ $study->versions->count() }}.
                <a href="{{ route('studies.show', $study) }}" class="font-medium underline">View current report</a>
            </div>
        @endif

        <div class="echo-report">
            @include('studies.partials.report-document')
        </div>

        @if ($study->versions->isNotEmpty())
            <div class="mt-8 border-t border-gray-200 pt-4 text-sm print:hidden">
                <h3 class="font-semibold text-gray-800">Signed versions</h3>
                <ul class="mt-2 space-y-1 text-gray-700">
                    @foreach ($study->versions as $v)
                        <li>
                            <a href="{{ route('studies.show', [$study, 'version' => $v->version]) }}" class="font-medium text-indigo-600 underline hover:text-indigo-500">v{{ $v->version }}</a>
                            — {{ $v->status->label() }} by {{ $v->signer?->name ?? 'unknown' }}, {{ JalaliDate::format($v->created_at) }}
                            @if ($v->reason)
                                — {{ $v->reason }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <script>if (window.location.search.includes('print=1')) { window.onload = function() { setTimeout(function() { window.print(); }, 500); }; }</script>
</x-dashboard-layout>
