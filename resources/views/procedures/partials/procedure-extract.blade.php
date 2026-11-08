{{--
    "Extract" above a text field (partials/field, "extract" => [procedures]): copies the summaries
    of all the patient's studies of the chosen procedure (ProcedureReport::SUMMARY), oldest first
    and each with its date, into the field, after what is already there. With several procedures a picker chooses which one; only
    those the user may view are offered. The patient is the form's (its nationalID field). The
    picker is not posted.

    @var string $name
    @var list<string> $sources
--}}
@php
    $labels = collect($sources)->mapWithKeys(fn (string $source) => [$source => \App\Support\Procedures::label($source)]);
    $urls = $labels->keys()->mapWithKeys(fn (string $source) => [$source => route('procedures.summaries', $source)]);
@endphp
<div x-data="procedureExtract(@js($urls), @js('f-'.$name))" class="procedure-extract" dir="ltr"
     style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; width: 100%; margin-bottom: 6px; text-align: left;">
    @if ($labels->count() > 1)
        <div style="flex: 0 1 260px; min-width: 200px;">
            <select x-model="source" data-widget="dropdown" class="no-voice" aria-label="Procedure type" x-on:change.stop x-on:input.stop>
                @foreach ($labels as $source => $label)
                    <option value="{{ $source }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <button type="button" class="ef-btn ef-btn-secondary" x-on:click="extract()" :disabled="busy"
            title="Copy the summaries of all this patient's {{ $labels->count() > 1 ? 'studies of the chosen procedure' : $labels->first().' studies' }}, with their dates">
        <span x-text="busy ? 'Extracting…' : 'Extract'">Extract</span>
    </button>
    @if ($labels->count() === 1)
        <span style="font-size: 12px; color: #6b7280;">all of the patient's {{ $labels->first() }} studies, with dates</span>
    @endif
    <span x-show="message" x-cloak x-text="message" role="status" style="font-size: 12px;" :style="{ color: failed ? '#dc2626' : '#047857' }"></span>
</div>
