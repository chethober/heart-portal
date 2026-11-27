{{--
    The fields of one group of a section (form.blade.php). Fields in one of the procedure's
    "disclosure_groups" (config/procedures/{procedure}.php, e.g. an angiography vessel's "Vessel
    characteristics") are folded behind a dropdown button that reveals them on demand, with a
    count of how many are filled in; the rest are drawn as they are (group-run).

    @var \Illuminate\Support\Collection<int, array{booleans: bool, group: string|null, caption?: string|null, fields: array<string, array<string, mixed>>}> $items
--}}
@php
    $disclosureGroups = config('procedures.'.$procedure.'.disclosure_groups', []);
    $disclosureOf = fn (array $item): ?string => in_array(Arr::first($item['fields'])['group'] ?? null, $disclosureGroups, true) ? Arr::first($item['fields'])['group'] : null;
    $runs = $items->chunkWhile(fn (array $item, int $key, $chunk) => $disclosureOf($item) === $disclosureOf($chunk->last()))->values();
@endphp
@foreach ($runs as $run)
    @php $disclosure = $disclosureOf($run->first()); @endphp
    @if ($disclosure === null)
        @include('procedures.partials.group-run', ['items' => $run, 'headings' => true])
    @else
        @php $hasErrors = $run->contains(fn (array $item) => collect($item['fields'])->keys()->contains(fn ($name) => $errors->has($name))); @endphp
        <div class="disclosure-part" x-data="{ open: @js($hasErrors), set: 0, recount() { this.set = Array.from(this.$refs.panel.querySelectorAll('select[name], input[name]')).filter((el) => el.type === 'checkbox' ? el.checked : el.value !== '').length } }"
             x-init="recount()" x-on:change="recount()" x-on:choices-sync.window="$nextTick(() => recount())" :class="{ 'is-open': open }">
            <button type="button" class="collapse-trigger" :aria-expanded="open" x-on:click="open = ! open">
                <span>{{ $disclosure }} <span class="disclosure-count" x-show="set > 0" x-cloak x-text="'(' + set + ')'"></span></span>
            </button>
            <div class="row" x-ref="panel" x-show="open" @unless ($hasErrors) x-cloak @endunless>
                @include('procedures.partials.group-run', ['items' => $run, 'headings' => false])
            </div>
        </div>
    @endif
@endforeach
