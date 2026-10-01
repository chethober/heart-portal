<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEchoStudyRequest;
use App\Http\Requests\UpdateEchoStudyRequest;
use App\Models\EchoStudy;
use App\Models\EchoStudyVersion;
use App\Models\Patient;
use App\Services\EchoCalculations;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EchoStudyController extends Controller
{
    public function create(): View
    {
        return view('studies.form');
    }

    public function store(StoreEchoStudyRequest $request): JsonResponse
    {
        $study = DB::transaction(function () use ($request) {
            $patient = $this->resolvePatient($request);

            $study = $patient->studies()->create([
                ...$request->studyAttributes(),
                'created_by' => $request->user()->id,
            ]);

            Audit::record('study.created', $study);

            return $study;
        });

        return response()->json([
            'success' => true,
            'message' => __('Echo data saved successfully.'),
            'id' => $study->id,
            'url' => route('studies.show', $study),
        ], 201);
    }

    public function show(Request $request, EchoStudy $study): View
    {
        Gate::authorize('view', $study);

        $study->load(['patient', 'reporter', 'amender', 'versions.signer']);
        $version = null;

        if ($request->filled('version')) {
            $version = $study->versions->firstWhere('version', (int) $request->query('version')) ?? abort(404);
            $row = $version->data;
        } else {
            $row = $study->toReportArray();
        }

        Audit::record('study.viewed', $study, $version ? ['version' => $version->version] : []);

        return $this->report(EchoCalculations::derive($row), isPreview: false, study: $study, version: $version);
    }

    public function edit(EchoStudy $study): View
    {
        Gate::authorize('update', $study);

        return view('studies.form', ['study' => $study->load('patient')]);
    }

    /**
     * Saves changes to a draft, or amends a signed report (re-signing it with a reason).
     */
    public function update(UpdateEchoStudyRequest $request, EchoStudy $study): JsonResponse
    {
        DB::transaction(function () use ($request, $study) {
            $patient = $this->resolvePatient($request);
            $attributes = [...$request->studyAttributes(), 'patient_id' => $patient->id];

            if ($study->isSigned()) {
                $changes = Audit::diff((clone $study)->fill($attributes));
                $study->amend($attributes, $request->user(), $request->validated('amendment_reason'));
                Audit::record('study.amended', $study, ['reason' => $study->amendment_reason, 'fields' => $changes]);

                return;
            }

            $study->fill($attributes);
            $changes = Audit::diff($study);
            $study->save();
            Audit::record('study.updated', $study, ['fields' => $changes]);
        });

        return response()->json([
            'success' => true,
            'message' => __('Echo data saved successfully.'),
            'id' => $study->id,
            'url' => route('studies.show', $study),
        ]);
    }

    public function finalize(Request $request, EchoStudy $study): RedirectResponse
    {
        Gate::authorize('finalize', $study);

        DB::transaction(function () use ($request, $study) {
            $study->finalize($request->user());
            Audit::record('study.finalized', $study);
        });

        return redirect()->route('studies.show', $study)->with('status', __('Report signed and finalized.'));
    }

    /**
     * Renders the report for unsaved form data (the form's live preview pane).
     */
    public function preview(Request $request): View
    {
        $known = [...config('echo.patient_fields'), ...config('echo.meta_fields'), ...array_keys(config('echo.fields'))];
        $report = array_fill_keys($known, '');

        foreach ($request->only($known) as $field => $value) {
            $report[$field] = is_string($value) ? $value : '';
        }

        // The form's "Manual" choice on these selects means "use the free-text box".
        foreach (['lv_size', 'rv_size', 'av_aorta'] as $field) {
            if ($report[$field] === 'Manual') {
                $report[$field] = (string) $request->input("{$field}_manual", '');
            }
        }

        return $this->report(EchoCalculations::derive($report), isPreview: true);
    }

    public function destroy(Request $request, EchoStudy $study): RedirectResponse
    {
        Gate::authorize('delete', $study);

        $study->delete();
        Audit::record('study.deleted', $study);

        // Return to whichever dashboard section the delete was made from.
        $redirect = $request->input('tab') === 'studies'
            ? redirect()->route('dashboard')
            : redirect()->route('patients.index', ['nationalID' => $study->patient->national_id]);

        return $redirect->with('status', __('Draft deleted.'));
    }

    /**
     * Finds the patient by national ID (or creates them) and refreshes their demographics.
     */
    private function resolvePatient(StoreEchoStudyRequest $request): Patient
    {
        $attributes = $request->patientAttributes();
        $patient = Patient::whereNationalId($attributes['national_id'])->lockForUpdate()->first() ?? new Patient;

        // Keep the latest demographics, but never blank out a value with an empty field.
        $patient->fill(array_filter($attributes, fn ($value) => $value !== null && $value !== ''));

        if (! $patient->exists || $patient->isDirty()) {
            $action = $patient->exists ? 'patient.updated' : 'patient.created';
            $changes = $patient->exists ? Audit::diff($patient) : [];
            $patient->save();
            Audit::record($action, $patient, $changes);
        }

        return $patient;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function report(array $report, bool $isPreview, ?EchoStudy $study = null, ?EchoStudyVersion $version = null): View
    {
        return view('studies.report', [
            'report' => $report,
            'isPreview' => $isPreview,
            'hospital' => auth()->user()->hospital,
            'study' => $study,
            'version' => $version,
        ]);
    }
}
