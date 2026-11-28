@php
    /**
     * Report body of a procedure with a narrative (app/Reports): its sections, then the filled fields
     * the narrative leaves out. Printed inside the shared template (report/document).
     *
     * @var \App\Reports\ProcedureReport $narrative
     * @var bool $rtl  The interface language is right to left.
     */
@endphp

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
                                @if ($loop->parent->first)<th style="text-align:start;padding:6px 8px;border:1px solid #edf2f7;background:#f8fafc">{{ $cell }}</th>@else<td dir="auto">{{ $cell }}</td>@endif
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
        {{-- A labelled line reads in the label's direction, its text isolated in its own (the clinical language's). --}}
        @foreach ($section['lines'] as $line)
            <p dir="auto" style="margin: 0 0 6px; white-space: pre-line;">@if ($line['label'])<span class="label">{{ $line['label'] }}:</span> <span dir="auto">{{ $line['text'] }}</span>@else{{ $line['text'] }}@endif</p>
        @endforeach
    </div>
@endforeach

@if ($appendix = $narrative->appendix())
    <div class="section">
        <div class="report-summary">
            <div class="section-title">{{ __('Other recorded findings') }}</div>
            @foreach ($appendix as $group)
                <p dir="auto" style="margin: 0 0 6px;"><strong>{{ $group['title'] }}:</strong> @foreach ($group['items'] as $item)<span dir="auto">{{ $item['label'] }}: {{ $item['value'] }}</span>@if (! $loop->last){{ $rtl ? '؛ ' : '; ' }}@endif @endforeach</p>
            @endforeach
        </div>
    </div>
@endif
