@php
    /**
     * Final-diagnosis picker (config/diagnoses.php, heart's ICD guide): a searchable list of
     * diagnoses with their ICD-10 codes and criteria, posted as {$name}[]. The list shows once three
     * letters are typed; the chosen ones show as chips above it. Used on the discharge
     * summary and on admissions; styled inline so it fits both the echo-style and dashboard forms.
     *
     * @var string $name
     * @var array<int, string>|null $selected
     */
    $selected = array_values((array) ($selected ?? []));
    $all = App\Support\Diagnoses::all();
@endphp
<div x-data="{ q: '', chosen: @js($selected), get searching() { return this.q.trim().length >= 3 }, match(text) { return text.toLowerCase().includes(this.q.trim().toLowerCase()) } }"
     dir="ltr" style="width: 100%; text-align: left;">
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;" x-show="chosen.length" x-cloak>
        <template x-for="key in chosen" :key="key">
            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 9999px; background: #eef2ff; color: #3730a3; font-size: 12px;">
                <span x-text="@js(array_map(fn ($entry) => $entry['label'].' ('.$entry['icd10'].')', $all))[key] ?? key"></span>
                <button type="button" style="font-weight: 700;" title="Remove" x-on:click="chosen = chosen.filter((k) => k !== key)">&times;</button>
            </span>
        </template>
    </div>
    <input type="search" x-model="q" placeholder="Search diagnoses or ICD-10 codes…" aria-label="Search diagnoses"
           style="width: 100%; margin-bottom: 6px; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
    {{-- Hidden rather than removed while searching is off, so the ticked boxes are still posted. --}}
    <div x-show="searching" x-cloak style="max-height: 260px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 4px 8px; background: #fff;">
        @foreach (App\Support\Diagnoses::groups() as $group => $entries)
            <div x-show="@js(collect($entries)->map(fn ($entry) => $entry['label'].' '.$entry['icd10'].' '.$group)->values()).some((text) => match(text))">
                <div style="margin: 6px 0 2px; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #6b7280;">{{ $group }}</div>
                @foreach ($entries as $key => $entry)
                    <label x-show="match(@js($entry['label'].' '.$entry['icd10'].' '.$group))"
                           style="display: flex; align-items: flex-start; gap: 8px; padding: 3px 0; font-size: 14px; font-weight: 400; cursor: pointer;">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $key }}" x-model="chosen" style="margin-top: 3px;">
                        <span>
                            {{ $entry['label'] }} <span style="color: #4338ca; font-weight: 600;">{{ $entry['icd10'] }}</span>
                            @if ($entry['criteria'] !== '')
                                <span style="display: block; color: #6b7280; font-size: 12px;" dir="auto">{{ $entry['criteria'] }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        @endforeach
    </div>
    <p x-show="q.trim() !== '' && ! searching" x-cloak style="margin: 0; font-size: 12px; color: #6b7280;">Type at least 3 letters to search.</p>
    <a href="{{ route('help') }}#diagnoses" target="_blank" style="display: inline-block; margin-top: 4px; font-size: 12px; color: #4f46e5; text-decoration: underline;">Final diagnosis guide</a>
</div>
