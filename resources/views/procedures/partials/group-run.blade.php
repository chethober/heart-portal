{{--
    A run of items of one group (group-items): runs of checkboxes as one checkbox group, every
    other field in its own cell. A panel item's own heading is shown unless $headings is false
    (the run is inside a disclosure that already carries it).

    @var \Illuminate\Support\Collection<int, array{booleans: bool, group: string|null, caption?: string|null, fields: array<string, array<string, mixed>>}> $items
    @var bool $headings
--}}
@php $lastPanelHeading = null; @endphp
@foreach ($items as $item)
    @php
        $itemField = Arr::first($item['fields']);
        $panelHeading = $headings && isset($itemField['panel']) ? ($itemField['group'] ?? null) : null;
    @endphp
    @if ($panelHeading !== null && $panelHeading !== $lastPanelHeading)
        <div class="subsection-title" style="flex: 1 1 100%; margin: 8px 0 0">{{ $panelHeading }}</div>
    @endif
    @php $lastPanelHeading = $panelHeading; @endphp
    @if (count($item['fields']) > 1 || (Arr::first($item['fields'])['widget'] ?? null) === 'checkbox')
        @include('procedures.partials.checkbox-group', ['fields' => $item['fields'], 'caption' => $item['caption'] ?? null])
        @continue
    @endif
    @php $name = array_key_first($item['fields']); $field = $item['fields'][$name]; @endphp

    @include('procedures.partials.field', ['sub' => false])
@endforeach
