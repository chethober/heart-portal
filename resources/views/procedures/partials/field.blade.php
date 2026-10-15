{{--
    One procedure field. Fields that only show for a value of this one (show_if) are drawn inside
    its cell, under it, so revealing them never moves the fields around it; consecutive checkbox
    ones share one row of checkboxes.

    A choice's "widget" can draw it as a "dropdown" whatever its size, a "checkbox" (ticked = the
    "1" code), or "combine": checkboxes whose combination picks the code (config "combine").

    @var string $name
    @var array<string, mixed> $field
    @var array<string, list<string>> $children  Dependent fields by the field they depend on.
    @var bool $sub  Drawn inside another field's cell.
--}}
<div class="field {{ $sub ?? false ? 'is-sub' : '' }}" @if ($visible($field)) x-show="{{ $visible($field) }}" x-cloak @endif
     @if (in_array($field['type'], ['multiline', 'diagnoses'], true)) style="flex: 1 1 100%" @endif>
    @if ($field['type'] === 'diagnoses')
        <label>{{ $field['label'] }}</label>
        @include('clinical.partials.diagnosis-picker', ['name' => $name, 'selected' => old($name, $values[$name] ?? [])])
    @elseif ($field['type'] === 'boolean')
        <label style="display:flex;align-items:center;gap:8px;margin-top:22px">
            <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value($name))
                   @if ($watched->has($name)) x-on:change="v['{{ $name }}'] = $event.target.checked ? '1' : ''" @endif>
            <span>{{ $field['label'] }}</span>
        </label>
    @else
        <label for="f-{{ $name }}">{{ $field['label'] }}</label>
        @switch($field['type'])
            @case('choice')
                @if (($field['widget'] ?? null) === 'combine')
                    @include('procedures.partials.combine-field')
                    @break
                @endif
                <select id="f-{{ $name }}" name="{{ $name }}" {!! $model($name) !!} @if (($field['widget'] ?? null) === 'dropdown') data-widget="dropdown" @endif>
                    <option value=""></option>
                    @foreach ($field['options'] as $code => $label)
                        <option value="{{ $code }}" @selected((string) $value($name) === (string) $code)>{{ $label }}</option>
                    @endforeach
                </select>
                @break
            @case('multiline')
                <textarea id="f-{{ $name }}" name="{{ $name }}" style="min-height: 60px">{{ $value($name) }}</textarea>
                @break
            @case('number')
                <input id="f-{{ $name }}" type="number" step="any" name="{{ $name }}" value="{{ $value($name) }}" {!! $model($name) !!}>
                @break
            @case('date')
                <input id="f-{{ $name }}" type="text" name="{{ $name }}" value="{{ $value($name) }}" placeholder="1405/01/01" class="no-voice" data-jalali {!! $model($name) !!}>
                @break
            @case('time')
                <input id="f-{{ $name }}" type="text" name="{{ $name }}" value="{{ $value($name) }}" placeholder="HH:MM" class="no-voice" {!! $model($name) !!}>
                @break
            @default
                <input id="f-{{ $name }}" type="text" name="{{ $name }}" value="{{ $value($name) }}" {!! $model($name) !!}>
        @endswitch
    @endif
    @error($name) <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
    @foreach (collect($children[$name] ?? [])->chunkWhile(fn (string $child, int $key, $chunk) => ($fields[$child]['widget'] ?? null) === 'checkbox' && ($fields[$chunk->last()]['widget'] ?? null) === 'checkbox') as $chunk)
        @if (($fields[$chunk->first()]['widget'] ?? null) === 'checkbox')
            <div class="field is-sub" @if ($visible($fields[$chunk->first()])) x-show="{{ $visible($fields[$chunk->first()]) }}" x-cloak @endif>
                <div class="cx-group" role="group">
                    @foreach ($chunk as $child)
                        <label class="cx-chip">
                            <input type="checkbox" name="{{ $child }}" value="1" @checked((string) $value($child) === '1')>
                            <span>{{ $fields[$child]['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @else
            @include('procedures.partials.field', ['name' => $chunk->first(), 'field' => $fields[$chunk->first()], 'sub' => true])
        @endif
    @endforeach
</div>
