{{--
    A choice whose codes are combinations (config "combine", e.g. diet / drugs / diet + drugs),
    ticked as one checkbox per part. The hidden input holds the code of the ticked combination,
    or nothing when no code matches; the form's draft restore announces itself with choices-sync.

    @var string $name
    @var array<string, mixed> $field
--}}
@php $parts = array_values(array_unique(array_merge(...array_values($field['combine'])))); @endphp
<div x-data="{
        codes: @js($field['combine']),
        picked: [],
        init() { this.read(); },
        read() { this.picked = [...(this.codes[this.$refs.value.value] ?? [])]; },
        toggle(part, on) {
            this.picked = on ? [...this.picked, part] : this.picked.filter((p) => p !== part);
            this.$refs.value.value = Object.keys(this.codes).find((code) => this.codes[code].length === this.picked.length
                && this.codes[code].every((p) => this.picked.includes(p))) ?? '';
        },
    }" x-on:choices-sync.window="read()">
    <input type="hidden" name="{{ $name }}" value="{{ $value($name) }}" x-ref="value">
    <div class="cx-group" role="group">
        @foreach ($parts as $part)
            <label class="cx-chip">
                <input type="checkbox" :checked="picked.includes(@js((string) $part))" x-on:change="toggle(@js((string) $part), $event.target.checked)">
                <span>{{ $field['options'][$part] }}</span>
            </label>
        @endforeach
    </div>
</div>
