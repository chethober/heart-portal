{{-- Keep the existing text column and imported free text while offering multiple selections. --}}
<div x-data="{
        options: @js(array_values($field['options'])), picked: [], other: '',
        init() { this.read(); },
        read() {
            const parts = this.$refs.value.value.split(',').map((part) => part.trim()).filter(Boolean);
            this.picked = this.options.filter((option) => parts.some((part) => part.toLowerCase() === option.toLowerCase()));
            this.other = parts.filter((part) => !this.options.some((option) => option.toLowerCase() === part.toLowerCase())).join(', ');
        },
        write() { this.$refs.value.value = [...this.picked, this.other.trim()].filter(Boolean).join(', '); },
        toggle(option, on) {
            this.picked = on ? [...this.picked, option] : this.picked.filter((part) => part !== option);
            this.write();
        },
    }" x-on:choices-sync.window="read()">
    <input type="hidden" name="{{ $name }}" value="{{ $value($name) }}" x-ref="value">
    <div class="cx-group" role="group" aria-label="{{ $field['label'] }}">
        @foreach ($field['options'] as $option)
            <label class="cx-chip">
                <input type="checkbox" :checked="picked.includes(@js($option))" x-on:change="toggle(@js($option), $event.target.checked)">
                <span>{{ $option }}</span>
            </label>
        @endforeach
    </div>
    <label for="f-{{ $name }}">Other catheter(s)</label>
    <input id="f-{{ $name }}" type="text" x-model="other" x-on:input="write()">
</div>
