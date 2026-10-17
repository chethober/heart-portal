<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcedureStudyRequest;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\ProcedureStudy;
use App\Reports\ProcedureReport;
use App\Support\Audit;
use App\Support\Diagnoses;
use App\Support\Forms;
use App\Support\PatientRecords;
use App\Support\Procedures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Forms and reports for the procedures ported from heart (angiography, ...).
 * Routes carry the procedure key; {procedureStudy} is bound to that procedure's model.
 */
class ProcedureStudyController extends Controller
{
    /**
     * New study form; ?nationalID= fills in the patient (and a known patient's demographics).
     */
    public function create(Request $request, string $procedure): View
    {
        abort_unless(Forms::allows($request->user(), $procedure, 'create'), 403);

        $nationalID = trim((string) $request->query('nationalID', ''));
        $patient = $nationalID !== '' ? Patient::whereNationalId($nationalID)->first() : null;

        if ($patient) {
            Audit::record('patient.viewed', $patient);
        }

        return $this->form($procedure, values: $patient?->toFormArray() ?? ['nationalID' => $nationalID], patient: $patient);
    }

    public function store(ProcedureStudyRequest $request, string $procedure): RedirectResponse
    {
        $study = DB::transaction(function () use ($request, $procedure) {
            $patient = PatientRecords::resolve($request->patientAttributes());
            $attributes = $this->attributesFor($request, $patient);

            $study = Procedures::model($procedure)::create([
                ...$attributes,
                'patient_id' => $patient->id,
                'created_by' => $request->user()->id,
            ]);

            Audit::record("{$procedure}.created", $study);

            return $study;
        });

        return redirect()->route('procedures.show', [$procedure, $study])->with('status', __('Study saved.'));
    }

    public function show(Request $request, string $procedure, ProcedureStudy $procedureStudy): View
    {
        Gate::authorize('view', $procedureStudy);

        $procedureStudy->load(['patient', 'admission', 'reporter', 'amender', 'versions.signer']);
        $version = null;

        if ($request->filled('version')) {
            $version = $procedureStudy->versions->firstWhere('version', (int) $request->query('version')) ?? abort(404);
        }

        Audit::record("{$procedure}.viewed", $procedureStudy, $version ? ['version' => $version->version] : []);

        $report = $version ? $version->data : $procedureStudy->toReportArray();

        return view('procedures.show', [
            'procedure' => $procedure,
            'study' => $procedureStudy,
            'version' => $version,
            'report' => $report,
            // Clinical narrative for procedures that have one (app/Reports); the others list their fields.
            'narrative' => ProcedureReport::for($procedure, $report),
            'hospital' => $request->user()->hospital,
        ]);
    }

    /**
     * The report for unsaved form data, for the form's live preview pane. Values are filtered like
     * the saved form (only the procedure's fields, known choice codes, show_if) but not validated.
     */
    public function preview(Request $request, string $procedure): View
    {
        abort_unless(Forms::allows($request->user(), $procedure, 'create') || Forms::allows($request->user(), $procedure, 'edit'), 403);

        $study = new (Procedures::model($procedure));
        $values = [];

        foreach (Procedures::formFields($procedure) as $name => $field) {
            $value = $request->input($name);
            $value = match ($field['type']) {
                'boolean' => (bool) $value ?: null,
                'number' => is_numeric($value) ? $value : null,
                'choice' => is_scalar($value) && array_key_exists((string) $value, $field['options']) ? (string) $value : null,
                'diagnoses' => is_array($value) ? (array_values(array_intersect($value, Diagnoses::keys())) ?: null) : null,
                default => is_string($value) && trim($value) !== '' ? $value : null,
            };

            if (isset($field['show_if']) && (string) ($values[$field['show_if'][0]] ?? '') !== $field['show_if'][1]) {
                $value = null;
            }

            $values[$name] = $value;
        }

        $study->forceFill($values);
        $report = ['id' => null, 'created_at' => now()->toDateTimeString()];
        foreach (array_keys($values) as $name) {
            $report[$name] = $study->getAttribute($name);
        }
        foreach (['first_name', 'last_name', 'nationalID', 'dob', 'phone_number', 'gender'] as $name) {
            $report[$name] = is_string($request->input($name)) ? $request->input($name) : null;
        }

        return view('procedures.preview', [
            'procedure' => $procedure,
            'report' => $report,
            'narrative' => ProcedureReport::for($procedure, $report),
            'hospital' => $request->user()->hospital,
            'admission' => $request->filled('admission_id') ? Admission::find((int) $request->input('admission_id')) : null,
        ]);
    }

    public function edit(string $procedure, ProcedureStudy $procedureStudy): View
    {
        Gate::authorize('update', $procedureStudy);

        return $this->form($procedure, $procedureStudy->load('patient'));
    }

    /**
     * Saves changes to a draft, or amends a signed report (re-signing it with a reason).
     */
    public function update(ProcedureStudyRequest $request, string $procedure, ProcedureStudy $procedureStudy): RedirectResponse
    {
        DB::transaction(function () use ($request, $procedure, $procedureStudy) {
            $patient = PatientRecords::resolve($request->patientAttributes());
            $attributes = [...$this->attributesFor($request, $patient), 'patient_id' => $patient->id];

            if ($procedureStudy->isSigned()) {
                $changes = Audit::diff((clone $procedureStudy)->fill($attributes));
                $procedureStudy->amend($attributes, $request->user(), $request->validated('amendment_reason'));
                Audit::record("{$procedure}.amended", $procedureStudy, ['reason' => $procedureStudy->amendment_reason, 'fields' => $changes]);

                return;
            }

            $procedureStudy->fill($attributes);
            $changes = Audit::diff($procedureStudy);
            $procedureStudy->save();
            Audit::record("{$procedure}.updated", $procedureStudy, ['fields' => $changes]);
        });

        return redirect()->route('procedures.show', [$procedure, $procedureStudy])->with('status', __('Study saved.'));
    }

    public function finalize(Request $request, string $procedure, ProcedureStudy $procedureStudy): RedirectResponse
    {
        Gate::authorize('finalize', $procedureStudy);

        // Procedures with sign-off steps (discharge: intern, resident) are signed one step at a time.
        $step = $procedureStudy->nextSignoff();

        DB::transaction(function () use ($request, $procedure, $procedureStudy, $step) {
            $procedureStudy->signNext($request->user());
            Audit::record($step ? "{$procedure}.signed" : "{$procedure}.finalized", $procedureStudy, $step ? ['step' => $step] : []);
        });

        return redirect()->route('procedures.show', [$procedure, $procedureStudy])->with('status', $step
            ? __('Signed as :step. Next to sign: :next.', ['step' => __($procedureStudy::SIGNOFFS[$step]), 'next' => __($procedureStudy->nextSignoffLabel())])
            : __('Report signed and finalized.'));
    }

    public function destroy(string $procedure, ProcedureStudy $procedureStudy): RedirectResponse
    {
        Gate::authorize('delete', $procedureStudy);

        $procedureStudy->delete();
        Audit::record("{$procedure}.deleted", $procedureStudy);

        return redirect()->route('patients.index', ['nationalID' => $procedureStudy->patient->national_id])
            ->with('status', __('Draft deleted.'));
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function form(string $procedure, ?ProcedureStudy $study = null, array $values = [], ?Patient $patient = null): View
    {
        $patient = $study->patient ?? $patient;

        return view('procedures.form', [
            'procedure' => $procedure,
            'study' => $study,
            // A known patient's admissions, to link the study to one of them.
            'admissions' => $patient ? $patient->admissions()->with('wardRecord')->orderByDesc('admitted_on')->orderByDesc('id')->get() : collect(),
            // A new study starts from the model's defaults (e.g. the discharge history in Auto mode).
            'values' => $study
                ? [...$study->patient->toFormArray(), ...$study->only(['admission_id', ...array_keys($study->fields())])]
                : [...array_filter((new (Procedures::model($procedure)))->only(array_keys(Procedures::formFields($procedure))), fn ($value) => $value !== null), ...$values],
        ]);
    }

    /**
     * The study's fields, after checking a linked admission is the same patient's.
     *
     * @return array<string, mixed>
     */
    private function attributesFor(ProcedureStudyRequest $request, Patient $patient): array
    {
        $attributes = $request->studyAttributes();

        if ($attributes['admission_id'] && Admission::find($attributes['admission_id'])?->patient_id !== $patient->id) {
            throw ValidationException::withMessages(['admission_id' => __('The admission belongs to another patient.')]);
        }

        return $attributes;
    }
}
