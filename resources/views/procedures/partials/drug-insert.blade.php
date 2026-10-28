{{--
    Drug catalog search above a text field (partials/field, "insert" => "drugs"): a picked drug is
    added to the text as its own line, "Atorvastatin 40 mg tablet — 1 tablet PO QHS", with the
    dose, route and frequency the catalog (or the user's last prescription of it) suggests. The
    line is plain text and can be edited like the rest. The search box itself is not posted.

    @var string $name
--}}
<div x-data="drugInsert(@js(route('drugs.search')), @js('f-'.$name))" x-on:click.outside="results = []"
     dir="ltr" style="position: relative; width: 100%; margin-bottom: 6px; text-align: left;">
    <input type="search" x-model="q" class="no-voice" autocomplete="off" placeholder="Add from the medications list…" aria-label="Search the medications list"
           x-on:input.debounce.250ms.stop="search()" x-on:change.stop x-on:keydown.escape="results = []"
           x-on:keydown.arrow-down.prevent="active = Math.min(active + 1, results.length - 1)"
           x-on:keydown.arrow-up.prevent="active = Math.max(active - 1, 0)"
           x-on:keydown.enter="if (results.length) { $event.preventDefault(); pick(results[active]) }"
           style="width: 100%; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
    <ul x-show="results.length" x-cloak role="listbox"
        style="position: absolute; z-index: 20; left: 0; right: 0; margin: 2px 0 0; padding: 0; list-style: none; max-height: 260px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,.08);">
        <template x-for="(drug, i) in results" :key="drug.id">
            <li role="option" :aria-selected="i === active" x-on:mousedown.prevent="pick(drug)" x-on:mouseenter="active = i"
                :style="'padding: 6px 10px; font-size: 14px; cursor: pointer;' + (i === active ? ' background: #eef2ff;' : '')">
                <span x-text="drug.label"></span>
                <span style="color: #6b7280;" dir="auto" x-text="drug.local_name ? ' — ' + drug.local_name : ''"></span>
            </li>
        </template>
    </ul>
    <p x-show="q.trim() !== '' && q.trim().length < 2" x-cloak style="margin: 2px 0 0; font-size: 12px; color: #6b7280;">Type at least 2 letters to search.</p>
</div>
