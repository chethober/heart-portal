@php
    /**
     * Write or edit a draft prescription. Like the clinical forms it stays in English whatever the
     * interface language. Drugs are picked from the catalog with an autocomplete (heart's drug
     * search) or typed as free text; picking a drug fills in its usual dose and directions.
     *
     * @var \App\Models\Prescription $prescription  Unsaved when creating.
     * @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Admission> $admissions
     */
    use App\Support\Prescribing;

    $editing = $prescription->exists;
    $items = old('items', $prescription->items->map(fn ($item) => [
        ...$item->only(['drug_id', 'dose', 'unit', 'route', 'frequency', 'duration', 'quantity', 'instructions']),
        'drug_name' => $item->drug_name,
        'label' => $item->drugLabel(),
    ])->all());
    $items = array_values(array_map(fn ($item) => [...$item, 'label' => $item['label'] ?? ($item['drug_name'] ?? '')], (array) $items));

    $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $patient = $prescription->patient;
@endphp

<x-dashboard-layout title="Prescription">
    <link rel="stylesheet" href="/echo-form/voice-input.css">
    <script src="/echo-form/voice-input.js" defer></script>
    <form method="POST" action="{{ $editing ? route('prescriptions.update', $prescription) : route('prescriptions.store') }}"
          dir="ltr" lang="en" class="space-y-4"
          x-data="prescriptionForm(@js($items), @js(route('drugs.search')))">
        @csrf
        @if ($editing)
            @method('PUT')
        @else
            @if ($prescription->discharge_study_id)
                <input type="hidden" name="discharge_study_id" value="{{ $prescription->discharge_study_id }}">
            @else
                <input type="hidden" name="patient_id" value="{{ $prescription->patient_id }}">
            @endif
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold">{{ $prescription->discharge_study_id ? 'Discharge Prescription' : 'Prescription' }}</h2>
            <span class="text-sm text-gray-500">{{ $patient->first_name }} {{ $patient->last_name }}</span>
            @if ($editing)
                <span class="ms-auto">@include('prescriptions.partials.status-badge', ['status' => $prescription->status])</span>
            @endif
        </div>

        @if ($errors->any())
            <div class="rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert">
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <fieldset class="grid gap-4 rounded-lg border border-gray-200 p-4 sm:grid-cols-3">
            <legend class="px-1 font-semibold">Prescription</legend>
            <label class="block text-sm">
                <span class="font-medium text-gray-700">Date (YYYY/MM/DD)</span>
                <input type="text" name="prescribed_on" value="{{ old('prescribed_on', $prescription->prescribed_on) }}" class="{{ $input }} no-voice">
            </label>
            <label class="block text-sm">
                <span class="font-medium text-gray-700">Admission</span>
                @if ($prescription->discharge_study_id)
                    <span class="mt-2 block text-gray-600">Discharge summary #{{ $prescription->discharge_study_id }}</span>
                @else
                    <select name="admission_id" class="{{ $input }}">
                        <option value="">Outpatient / none</option>
                        @foreach ($admissions as $admission)
                            <option value="{{ $admission->id }}" @selected((string) old('admission_id', $prescription->admission_id) === (string) $admission->id)>{{ $admission->ward }} {{ $admission->admitted_on }}</option>
                        @endforeach
                    </select>
                @endif
            </label>
            <label class="block text-sm sm:col-span-3">
                <span class="font-medium text-gray-700">Notes for the patient or pharmacy</span>
                <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes', $prescription->notes) }}</textarea>
            </label>
        </fieldset>

        <fieldset class="rounded-lg border border-gray-200 p-4">
            <legend class="px-1 font-semibold">Drugs</legend>

            <template x-for="(item, index) in items" :key="item.key">
                <div class="mb-4 grid gap-3 border-b border-gray-100 pb-4 sm:grid-cols-6">
                    <div class="relative sm:col-span-6" @click.outside="item.results = []">
                        <label class="block text-sm">
                            <span class="font-medium text-gray-700" x-text="'Drug ' + (index + 1)"></span>
                            <input type="text" autocomplete="off" class="{{ $input }}" placeholder="Type at least 2 letters of the generic name"
                                   x-model="item.label" @input.debounce.250ms="search(item)" @keydown.escape="item.results = []"
                                   @keydown.arrow-down.prevent="item.active = Math.min(item.active + 1, item.results.length - 1)"
                                   @keydown.arrow-up.prevent="item.active = Math.max(item.active - 1, 0)"
                                   @keydown.enter="if (item.results.length) { $event.preventDefault(); pick(item, item.results[item.active]) }">
                        </label>
                        <input type="hidden" :name="'items[' + index + '][drug_id]'" :value="item.drug_id ?? ''">
                        <input type="hidden" :name="'items[' + index + '][drug_name]'" :value="item.drug_id ? '' : item.label">
                        <input type="hidden" :name="'items[' + index + '][label]'" :value="item.label">
                        <p class="mt-1 text-xs" :class="item.drug_id ? 'text-green-700' : 'text-gray-500'"
                           x-text="item.drug_id ? 'From the catalog' : (item.label ? 'Not in the catalog: saved as typed' : '')"></p>
                        <ul x-show="item.results.length" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border border-gray-200 bg-white text-sm shadow-lg">
                            <template x-for="(drug, i) in item.results" :key="drug.id">
                                <li class="cursor-pointer px-3 py-2" :class="i === item.active ? 'bg-indigo-50' : ''" @mousedown.prevent="pick(item, drug)">
                                    <span x-text="drug.label"></span>
                                    <span class="text-gray-500" dir="auto" x-text="drug.local_name ? ' — ' + drug.local_name : ''"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">Dose</span>
                        <input type="text" :name="'items[' + index + '][dose]'" x-model="item.dose" class="{{ $input }}">
                    </label>
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">Unit</span>
                        <select :name="'items[' + index + '][unit]'" x-model="item.unit" class="{{ $input }}">
                            <option value=""></option>
                            @foreach (Prescribing::UNITS as $unit)
                                <option value="{{ $unit }}">{{ $unit }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">Route</span>
                        <select :name="'items[' + index + '][route]'" x-model="item.route" class="{{ $input }}">
                            <option value=""></option>
                            @foreach (Prescribing::ROUTES as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">Frequency</span>
                        <select :name="'items[' + index + '][frequency]'" x-model="item.frequency" class="{{ $input }}">
                            <option value=""></option>
                            @foreach (Prescribing::FREQUENCIES as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">Duration</span>
                        <input type="text" :name="'items[' + index + '][duration]'" x-model="item.duration" placeholder="e.g. 30 days" class="{{ $input }}">
                    </label>
                    <label class="block text-sm">
                        <span class="font-medium text-gray-700">Quantity</span>
                        <input type="number" min="1" max="1000" :name="'items[' + index + '][quantity]'" x-model="item.quantity" class="{{ $input }}">
                    </label>
                    <label class="block text-sm sm:col-span-5">
                        <span class="font-medium text-gray-700">Instructions</span>
                        <input type="text" dir="auto" :name="'items[' + index + '][instructions]'" x-model="item.instructions" placeholder="e.g. after meals" class="{{ $input }}">
                    </label>
                    <div class="flex items-end">
                        <button type="button" class="text-sm font-medium text-red-700 underline" @click="items.splice(index, 1)">Remove</button>
                    </div>
                </div>
            </template>

            <button type="button" class="text-sm font-medium text-indigo-600 underline" @click="add()">+ Add drug</button>
        </fieldset>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Save Draft</button>
            <a href="{{ $editing ? route('prescriptions.show', $prescription) : route('patients.index', ['nationalID' => $patient->national_id]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancel</a>
        </div>
    </form>

    <script>
        function prescriptionForm(initial, searchUrl) {
            let next = 0;
            const blank = () => ({ drug_id: null, label: '', dose: '', unit: '', route: '', frequency: '', duration: '', quantity: '', instructions: '' });
            const row = (item) => ({ ...blank(), ...item, key: next++, results: [], active: 0 });

            return {
                items: (initial.length ? initial : [{}]).map(row),
                add() {
                    this.items.push(row({}));
                },
                async search(item) {
                    item.drug_id = null;
                    const q = item.label.trim();
                    if (q.length < 2) {
                        item.results = [];
                        return;
                    }
                    const response = await fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                    if (!response.ok || item.label.trim() !== q) {
                        return;
                    }
                    item.results = (await response.json()).data;
                    item.active = 0;
                },
                pick(item, drug) {
                    item.drug_id = drug.id;
                    item.label = drug.label;
                    item.results = [];
                    for (const field of ['dose', 'unit', 'route', 'frequency', 'duration', 'quantity', 'instructions']) {
                        if (drug[field] !== null && drug[field] !== undefined && drug[field] !== '') {
                            item[field] = drug[field];
                        }
                    }
                },
            };
        }
    </script>
</x-dashboard-layout>
