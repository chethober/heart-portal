@foreach ($fields as $name => $field)
    <label class="{{ $class }}" @if (! $shared && $visible($field)) x-show="{{ $visible($field) }}" x-cloak @endif>
        <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value($name))
               @if ($watched->has($name)) x-on:change="v['{{ $name }}'] = $event.target.checked ? '1' : ''" @endif>
        <span>{{ $field['label'] }}</span>
    </label>
@endforeach
