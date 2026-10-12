<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Models\PrescriptionItem;
use App\Support\Audit;
use App\Support\Prescribing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The drug catalog (heart's "لیست داروها" list and its drug autocomplete). Every hospital sees the
 * shared catalog plus its own additions, which hospital admins manage.
 */
class DrugController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('prescriptions.drugs', [
            'search' => $search,
            'drugs' => Drug::query()->search($search)->orderBy('generic_name')->orderBy('strength')->paginate(50)->withQueryString(),
        ]);
    }

    /**
     * Adds a drug to the user's hospital's own catalog.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('drug', [
            'generic_name' => ['required', 'string', 'max:255'],
            'local_name' => ['nullable', 'string', 'max:255'],
            'form' => ['required', 'string', Rule::in(Prescribing::FORMS)],
            'strength' => ['nullable', 'string', 'max:64'],
            'default_dose' => ['nullable', 'string', 'max:32'],
            'default_unit' => ['nullable', 'string', Rule::in(Prescribing::UNITS)],
            'default_route' => ['nullable', 'string', Rule::in(array_keys(Prescribing::ROUTES))],
            'default_frequency' => ['nullable', 'string', Rule::in(array_keys(Prescribing::FREQUENCIES))],
        ]);

        $drug = Drug::create([...$validated, 'hospital_id' => $request->user()->hospital_id]);
        Audit::record('drug.created', $drug, ['name' => $drug->displayName()]);

        return back()->with('status', __('Drug added to your hospital\'s catalog.'));
    }

    /**
     * Withdraws or restores one of the hospital's own drugs; shared drugs are not editable here.
     */
    public function update(Request $request, Drug $drug): RedirectResponse
    {
        abort_unless($drug->hospital_id !== null && $drug->hospital_id === $request->user()->hospital_id, 403);

        $drug->update($request->validate(['is_active' => ['required', 'boolean']]));
        Audit::record('drug.updated', $drug, ['is_active' => $drug->is_active]);

        return back()->with('status', __('Drug updated.'));
    }

    /**
     * Autocomplete for the prescription form. Each match carries its catalog defaults and, as heart
     * did, how the signed-in user last prescribed it, so repeat orders need no retyping.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $drugs = Drug::query()->where('is_active', true)->search(mb_substr($query, 0, 100))
            ->orderBy('generic_name')->orderBy('strength')->limit(10)->get();

        $lastOrders = PrescriptionItem::query()
            ->whereIn('drug_id', $drugs->modelKeys())
            ->whereHas('prescription', fn ($prescription) => $prescription->where('created_by', $request->user()->id))
            ->latest('id')
            ->get()
            ->unique('drug_id')
            ->keyBy('drug_id');

        return response()->json(['data' => $drugs->map(function (Drug $drug) use ($lastOrders) {
            $last = $lastOrders->get($drug->id);

            return [
                'id' => $drug->id,
                'label' => $drug->displayName(),
                'local_name' => $drug->local_name,
                'dose' => $last->dose ?? $drug->default_dose,
                'unit' => $last->unit ?? $drug->default_unit,
                'route' => $last->route ?? $drug->default_route,
                'frequency' => $last->frequency ?? $drug->default_frequency,
                'duration' => $last?->duration,
                'quantity' => $last?->quantity,
                'instructions' => $last?->instructions,
            ];
        })->values()]);
    }
}
