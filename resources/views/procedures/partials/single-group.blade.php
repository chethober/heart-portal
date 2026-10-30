{{-- One selection, mapped back to the existing clinical columns. --}}
@php
    $groupId = 'f-group-'.array_key_first($fields);
    $selected = collect($fields)->keys()->first(fn (string $name) => (string) $value($name) === ($fields[$name]['checkbox_value'] ?? '1'));
@endphp
<div x-data="{
        read() {
            const selected = Array.from(this.$el.querySelectorAll('input[type=hidden]')).find((input) => input.value === input.dataset.yes);
            this.$refs.select.value = selected?.name ?? '';
            this.$refs.select.dispatchEvent(new Event('change'));
        },
        write() {
            this.$el.querySelectorAll('input[type=hidden]').forEach((input) => {
                input.value = input.name === this.$refs.select.value ? input.dataset.yes : '';
            });
        },
    }" x-on:choices-sync.window="read()">
    @foreach ($fields as $name => $field)
        <input type="hidden" name="{{ $name }}" value="{{ $value($name) }}" data-yes="{{ $field['checkbox_value'] ?? '1' }}">
    @endforeach
    <select id="{{ $groupId }}" aria-label="{{ $caption }}" x-ref="select" x-on:change="write()">
        <option value=""></option>
        @foreach ($fields as $name => $field)
            <option value="{{ $name }}" @selected($selected === $name)>{{ $field['label'] }}</option>
        @endforeach
    </select>
</div>
