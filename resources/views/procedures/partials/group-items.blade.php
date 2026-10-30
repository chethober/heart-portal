{{--
    The fields of one group of a section (form.blade.php): runs of checkboxes as one checkbox
    group, every other field in its own cell. Checkboxes shown for a ticked checkbox carry its
    label as their caption.

    @var \Illuminate\Support\Collection<int, array{booleans: bool, group: string|null, caption?: string|null, fields: array<string, array<string, mixed>>}> $items
--}}
@php $lastPanelHeading = null; @endphp
@foreach ($items as $item)
    @php
        $itemField = Arr::first($item['fields']);
        $panelHeading = isset($itemField['panel']) ? ($itemField['group'] ?? null) : null;
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
