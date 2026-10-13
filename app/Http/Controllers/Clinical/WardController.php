<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The hospital's wards, managed by its admins. Wards are deactivated, not deleted,
 * so past admissions keep their ward.
 */
class WardController extends Controller
{
    public function index(): View
    {
        return view('wards.index', [
            'wards' => Ward::query()->withCount(['admissions as current_count' => fn ($query) => $query->current()])->orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => $this->nameRules($request)]);

        $ward = Ward::create(['name' => trim($validated['name'])]);
        Audit::record('ward.created', $ward);

        return back()->with('status', __('Ward :name added.', ['name' => $ward->name]));
    }

    public function update(Request $request, Ward $ward): RedirectResponse
    {
        $validated = $request->validate([
            'name' => $this->nameRules($request, $ward),
            'is_active' => ['required', 'boolean'],
        ]);

        $ward->fill(['name' => trim($validated['name']), 'is_active' => $validated['is_active']]);
        $changes = Audit::diff($ward);
        $ward->save();

        if ($changes) {
            Audit::record('ward.updated', $ward, $changes);
        }

        return back()->with('status', __('Ward :name updated.', ['name' => $ward->name]));
    }

    /**
     * @return list<mixed>
     */
    private function nameRules(Request $request, ?Ward $ward = null): array
    {
        return ['required', 'string', 'max:100', Rule::unique('wards', 'name')->where('hospital_id', $request->user()->hospital_id)->ignore($ward)];
    }
}
