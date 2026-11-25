{{--
    The fields of one group of a section (form.blade.php). Fields in one of the procedure's
    "disclosure_groups" (config/procedures/{procedure}.php, e.g. an angiography vessel's "Vessel
    characteristics") are folded behind a dropdown button that reveals them on demand, with a
    count of how many are filled in. Fields with "reveal_after" (a field's name) are drawn in a
    block shown once that field has a value other than "0" (none), e.g. a PCI territory's lesion
    fields; nothing is cleared when it hides again. The rest are drawn as they are (group-run).

    @var \Illuminate\Support\Collection<int, array{booleans: bool, group: string|null, caption?: string|null, fields: array<string, array<string, mixed>>}> $items
--}}
@php
    $disclosureGroups = config('procedures.'.$procedure.'.disclosure_groups', []);
    $disclosureOf = fn (array $item): ?string => in_array(Arr::first($item['fields'])['group'] ?? null, $disclosureGroups, true) ? Arr::first($item['fields'])['group'] : null;
    $revealOf = fn (array $item): ?string => Arr::first($item['fields'])['reveal_after'] ?? null;
    $runs = $items->chunkWhile(fn (array $item, int $key, $chunk) => $disclosureOf($item) === $disclosureOf($chunk->last()) && $revealOf($item) === $revealOf($chunk->last()))->values();
@endphp
@foreach ($runs as $run)
    @php $disclosure = $disclosureOf($run->first()); @endphp
    @php $revealAfter = $revealOf($run->first()); @endphp
    @if ($revealAfter !== null)
        @php
            $hasErrors = $run->contains(fn (array $item) => collect($item['fields'])->keys()->contains(fn ($name) => $errors->has($name)));
            $revealed = $hasErrors || ! in_array((string) $value($revealAfter), ['', '0'], true);
        @endphp
        <div class="row" style="margin-bottom: 0" x-show="{{ $hasErrors ? 'true || ' : '' }}! ['', '0'].includes(v['{{ $revealAfter }}'] ?? '')" {{ $revealed ? '' : 'x-cloak' }}>
            @include('procedures.partials.group-run', ['items' => $run, 'headings' => true])
        </div>
    @elseif ($disclosure === null)
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
