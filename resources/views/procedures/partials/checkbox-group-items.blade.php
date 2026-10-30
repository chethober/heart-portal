@foreach ($fields as $name => $field)
    <label class="{{ $class }}" @if (! $shared && $visible($field)) x-show="{{ $visible($field) }}" x-cloak @endif>
        <input type="checkbox" name="{{ $name }}" value="{{ $field['checkbox_value'] ?? '1' }}" @checked((string) $value($name) === ($field['checkbox_value'] ?? '1'))
               @if ($watched->has($name)) x-on:change="v['{{ $name }}'] = $event.target.checked ? '{{ $field['checkbox_value'] ?? '1' }}' : ''" @endif>
        <span>{{ $field['label'] }}</span>
    </label>
@endforeach
