@php
    /**
     * A run of yes/no fields that don't exclude each other, as one group of checkboxes: chips up
     * to four, a dropdown of checkboxes from five, while "checkbox" choices are always chips, one
     * checkbox each. Each box is still its own field (name=1).
     * A show_if the whole run shares hides the group; others hide just their box.
     *
     * @var array<string, array<string, mixed>> $fields
     * @var string|null $caption
     * @var \Closure $value
     * @var \Closure $visible
     * @var \Illuminate\Support\Collection<string, string> $watched
     */
    $caption ??= Arr::first($fields)['caption'] ?? null;
    $conditions = collect($fields)->map(fn (array $field) => $visible($field))->unique();
    $shared = $conditions->count() === 1 ? $conditions->first() : null;
    $dropdown = (Arr::first($fields)['group_widget'] ?? null) === 'dropdown'
        || (count($fields) >= 5 && collect($fields)->every(fn (array $field) => $field['type'] === 'boolean'));
@endphp

<div class="field {{ (Arr::first($fields)['group_widget'] ?? null) === 'single' || (Arr::first($fields)['group_width'] ?? null) === 'field' ? '' : 'cx-group-field' }}" @if ($shared) x-show="{{ $shared }}" x-cloak @endif>
    @if ($caption ?? null)
        <label>{{ $caption }}</label>
    @endif
    @if ((Arr::first($fields)['group_widget'] ?? null) === 'single')
        @include('procedures.partials.single-group')
    @elseif ($dropdown)
        <div class="cx-dropdown cx-group-dropdown" x-data="checkboxGroup()" :class="{ 'is-open': open }"
             x-on:change="count()" x-on:choices-sync.window="count()"
             x-on:click.outside="close()" x-on:keydown.escape.prevent="close(true)" x-on:focusout="if (! $el.contains($event.relatedTarget)) close()">
            <button type="button" class="cx-trigger" x-ref="trigger" aria-haspopup="true" :aria-expanded="open" :class="{ 'is-empty': ! picked.length }" x-on:click="open = ! open">
                <span class="cx-trigger-text" x-text="summary()">—</span>
            </button>
            <div class="cx-panel" x-ref="panel" role="group" x-show="open" x-cloak x-on:mousedown.prevent>
                @include('procedures.partials.checkbox-group-items', ['class' => 'cx-option'])
            </div>
        </div>
    @else
        <div class="cx-group" role="group">
            @include('procedures.partials.checkbox-group-items', ['class' => 'cx-chip'])
        </div>
    @endif
    @foreach ($fields as $name => $field)
        @error($name) <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
    @endforeach
</div>
