<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdmissionRequest;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Ward;
use App\Support\Audit;
use App\Support\JalaliCalendar;
use App\Support\Procedures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A patient's hospital stays (heart's admit form): admit, edit, discharge, and the
 * studies recorded during the stay.
 */
class AdmissionController extends Controller
{
    public function create(Patient $patient): View
    {
        return $this->form($patient, new Admission(['admitted_on' => JalaliCalendar::today()]));
    }

    public function store(AdmissionRequest $request, Patient $patient): RedirectResponse
    {
        $admission = $patient->admissions()->create($this->attributes($request));
        Audit::record('admission.created', $admission);

        return redirect()->route('admissions.show', [$patient, $admission])->with('status', __('Admission saved.'));
    }

    public function show(Patient $patient, Admission $admission): View
    {
        $admission->setRelation('patient', $patient)->load('wardRecord');
        Audit::record('admission.viewed', $admission);

        $procedures = [];
        foreach (Procedures::MODELS as $key => $model) {
            $procedures[$key] = $model::where('admission_id', $admission->id)->latest()->get();
        }

        return view('admissions.show', [
            'patient' => $patient,
            'admission' => $admission,
            'echoStudies' => $admission->echoStudies()->with('author')->latest()->get(),
            'procedures' => $procedures,
        ]);
    }

    public function edit(Patient $patient, Admission $admission): View
    {
        return $this->form($patient, $admission);
    }

    public function update(AdmissionRequest $request, Patient $patient, Admission $admission): RedirectResponse
    {
        $admission->fill($this->attributes($request));
        $changes = Audit::diff($admission);
        $admission->save();

        if ($changes) {
            Audit::record('admission.updated', $admission, $changes);
        }

        return redirect()->route('admissions.show', [$patient, $admission])->with('status', __('Admission saved.'));
    }

    /**
     * Discharges a current admission (heart's discharge date and time).
     */
    public function discharge(Request $request, Patient $patient, Admission $admission): RedirectResponse
    {
        abort_if($admission->isDischarged(), 409);

        $request->merge(array_map(
            fn ($value) => is_string($value) ? JalaliCalendar::normalize($value) : $value,
            $request->only(['discharged_on', 'discharged_at']),
        ));

        $validated = $request->validate([
            'discharged_on' => ['required', 'string', 'regex:/^\d{4}\/\d{1,2}\/\d{1,2}$/', function (string $attribute, mixed $value, \Closure $fail) use ($admission) {
                $date = JalaliCalendar::toDate($value);
                $admitted = JalaliCalendar::toDate($admission->admitted_on);

                if (! $date) {
                    $fail(__('This is not a valid date.'));
                } elseif ($admitted && $date < $admitted) {
                    $fail(__('The discharge date cannot be before the admission date.'));
                }
            }],
            'discharged_at' => ['nullable', 'string', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'],
        ]);

        $admission->fill($validated);
        $changes = Audit::diff($admission);
        $admission->save();
        Audit::record('admission.discharged', $admission, $changes);

        return redirect()->route('admissions.show', [$patient, $admission])->with('status', __('Patient discharged.'));
    }

    private function form(Patient $patient, Admission $admission): View
    {
        // Inactive wards stay selectable only for the admission already in them.
        $wards = Ward::query()
            ->where(fn ($query) => $query->where('is_active', true)->when($admission->ward_id, fn ($query) => $query->orWhere('id', $admission->ward_id)))
            ->orderBy('name')
            ->get();

        return view('admissions.form', ['patient' => $patient, 'admission' => $admission, 'wards' => $wards]);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(AdmissionRequest $request): array
    {
        $attributes = $request->admissionAttributes();
        $attributes['ward'] = isset($attributes['ward_id']) ? Ward::findOrFail($attributes['ward_id'])->name : null;

        return $attributes;
    }
}
