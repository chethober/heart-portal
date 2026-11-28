@php
    /**
     * A saved procedure study's report inside the dashboard, laid out like the echo report page
     * (studies/report.blade.php): actions, the printable report document and its signed versions.
     *
     * @var string $procedure
     * @var \App\Models\ProcedureStudy $study
     * @var \App\Models\ProcedureStudyVersion|null $version  Set when viewing an earlier signed version.
     * @var array<string, mixed> $report  Current values, or the version's snapshot.
     * @var \App\Reports\ProcedureReport|null $narrative  The clinical narrative, for procedures that have one.
     * @var \App\Models\Hospital|null $hospital
     */
    use App\Support\JalaliDate;
    use App\Support\Procedures;

    $buttonPrimary = 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700';
    $buttonSecondary = 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50';
@endphp

<x-dashboard-layout :title="__(Procedures::label($procedure))">
    @include('studies.partials.report-styles')

    <div>
        <div class="mb-6 flex flex-wrap items-center gap-3 print:hidden">
            <button type="button" onclick="window.print()" class="{{ $buttonPrimary }}">{{ __('Print Report') }}</button>
            <a href="{{ route('patients.index', ['nationalID' => $report['nationalID']]) }}" class="{{ $buttonSecondary }}">{{ __('Back to Patient') }}</a>
            @unless ($version)
                @can('update', $study)
                    <a href="{{ route('procedures.edit', [$procedure, $study]) }}" class="{{ $buttonSecondary }}">{{ $study->isSigned() ? __('Amend Report') : __('Edit Draft') }}</a>
                @endcan
                @can('finalize', $study)
                    {{-- With sign-off steps (discharge: intern → resident → attending physician) each signature is one step. --}}
                    @php($step = $study->nextSignoff())
                    <form method="POST" action="{{ route('procedures.finalize', [$procedure, $study]) }}" onsubmit="return confirm(@js($step ? __('Sign this report as :step?', ['step' => __($study::SIGNOFFS[$step])]) : __('Sign and finalize this report? It can only be changed by amendment afterwards.')));">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600">{{ $step ? __('Sign as :step', ['step' => __($study::SIGNOFFS[$step])]) : ($study::SIGNOFFS ? __('Sign as :step & Finalize', ['step' => __($study::FINAL_SIGNOFF)]) : __('Sign & Finalize')) }}</button>
                    </form>
                @endcan
                @can('delete', $study)
                    <form method="POST" action="{{ route('procedures.destroy', [$procedure, $study]) }}" onsubmit="return confirm(@js(__('Delete this draft?')));">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="{{ $buttonSecondary }} text-red-700">{{ __('Delete Draft') }}</button>
                    </form>
                @endcan
            @endunless
            <span class="ms-auto">@include('dashboard.status-badge', ['status' => $version?->status ?? $study->status])</span>
        </div>

        @if ($version)
            <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 print:hidden">
                {{ __('Showing signed version :version of :count.', ['version' => $version->version, 'count' => $study->versions->count()]) }}
                <a href="{{ route('procedures.show', [$procedure, $study]) }}" class="font-medium underline">{{ __('View current report') }}</a>
            </div>
        @endif

        <div class="echo-report">
            @include('procedures.report.document')
        </div>

        {{-- Discharge medications come from the summary's prescription (e-prescription), under the report. --}}
        @includeWhen($procedure === 'discharge', 'prescriptions.partials.discharge', ['study' => $study, 'report' => $report])

        @if ($study->versions->isNotEmpty())
            <div class="mt-8 border-t border-gray-200 pt-4 text-sm print:hidden">
                <h3 class="font-semibold text-gray-800">{{ __('Signed versions') }}</h3>
                <ul class="mt-2 space-y-1 text-gray-700">
                    @foreach ($study->versions as $v)
                        <li>
                            <a href="{{ route('procedures.show', [$procedure, $study, 'version' => $v->version]) }}" class="font-medium text-indigo-600 underline hover:text-indigo-500">v{{ $v->version }}</a>
                            — {{ __(':status by :name, :date', ['status' => __($v->status->label()), 'name' => $v->signer?->name ?? __('unknown'), 'date' => JalaliDate::format($v->created_at)]) }}
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
