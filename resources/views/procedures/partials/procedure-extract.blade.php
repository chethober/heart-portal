{{--
    "Extract" above a text field (partials/field, "extract" => [procedures]): copies the summaries
    of all the patient's studies of a procedure (ProcedureReport::summaryText(), or an echo study's summary), oldest first
    and each with its date, into the field, after what is already there. Each procedure has a row of its own with
    its own Extract button; only those the user may view are offered. The patient is the form's (its nationalID field).

    @var string $name
    @var list<string> $sources
--}}
@php
    // "echo" is the echo form (its summary); the other sources are procedures.
    $labels = collect($sources)->mapWithKeys(fn (string $source) => [$source => $source === 'echo' ? 'Echo' : \App\Support\Procedures::label($source)]);
@endphp
<div class="procedure-extracts" dir="ltr" style="display: flex; flex-direction: column; gap: 6px; width: 100%; margin-bottom: 6px; text-align: left;">
    @foreach ($labels as $source => $label)
        <div x-data="procedureExtract(@js([$source => $source === 'echo' ? route('studies.summaries') : route('procedures.summaries', $source)]), @js('f-'.$name))"
             class="procedure-extract" style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px;">
            @if ($labels->count() > 1)
                <span style="flex: 0 0 140px; font-size: 13px; font-weight: 600;">{{ $label }}</span>
            @endif
            <button type="button" class="ef-btn ef-btn-secondary" x-on:click="extract()" :disabled="busy"
                    title="Copy the summaries of all this patient's {{ $label }} studies, with their dates">
                <span x-text="busy ? 'Extracting…' : 'Extract'">Extract</span>
            </button>
            <span style="font-size: 12px; color: #6b7280;">all of the patient's {{ $label }} studies, with dates</span>
            <span x-show="message" x-cloak x-text="message" role="status" style="font-size: 12px;" :style="{ color: failed ? '#dc2626' : '#047857' }"></span>
        </div>
    @endforeach
</div>
