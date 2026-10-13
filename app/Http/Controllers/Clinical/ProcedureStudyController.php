<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcedureStudyRequest;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\ProcedureStudy;
use App\Reports\ProcedureReport;
use App\Support\Audit;
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

        DB::transaction(function () use ($request, $procedure, $procedureStudy) {
            $procedureStudy->finalize($request->user());
            Audit::record("{$procedure}.finalized", $procedureStudy);
        });

        return redirect()->route('procedures.show', [$procedure, $procedureStudy])->with('status', __('Report signed and finalized.'));
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
            'values' => $study ? [...$study->patient->toFormArray(), ...$study->only(['admission_id', ...array_keys($study->fields())])] : $values,
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
