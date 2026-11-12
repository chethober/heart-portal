<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEchoStudyRequest;
use App\Http\Requests\UpdateEchoStudyRequest;
use App\Models\Admission;
use App\Models\EchoStudy;
use App\Models\EchoStudyVersion;
use App\Models\Patient;
use App\Services\EchoCalculations;
use App\Support\Audit;
use App\Support\Forms;
use App\Support\PatientRecords;
use App\Support\StudyContext;
use App\Support\StudySummaries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EchoStudyController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $context = StudyContext::get($request, 'echo');
        if (! $context) {
            return redirect()->route('dashboard', $request->only(['patient', 'nationalID', 'admission']));
        }

        return view('studies.form', ['initialPatient' => StudyContext::values($context), 'patientContext' => $context]);
    }

    public function store(StoreEchoStudyRequest $request): JsonResponse
    {
        $study = DB::transaction(function () use ($request) {
            $patient = PatientRecords::resolve($request->patientAttributes());

            $study = $patient->studies()->create([
                ...$this->attributesFor($request, $patient),
                'created_by' => $request->user()->id,
            ]);

            Audit::record('study.created', $study);

            return $study;
        });
        StudyContext::forget($request, $request->string('context')->toString());

        return response()->json([
            'success' => true,
            'message' => __('Echo data saved successfully.'),
            'id' => $study->id,
            'url' => route('studies.show', $study),
        ], 201);
    }

    /**
     * The summaries of all the patient's echo studies (by national ID), each with its date,
     * oldest first, for another form to extract: the discharge summary fills its echo findings
     * from them. Studies with no summary are left out.
     */
    public function summaries(Request $request): JsonResponse
    {
        abort_unless(Forms::allows($request->user(), 'echo', 'view'), 403);

        $nationalID = Patient::normalizeNationalId(trim((string) $request->query('nationalID', '')));
        abort_unless(preg_match('/^[0-9A-Za-z]{1,32}$/', $nationalID) === 1, 422);

        $patient = Patient::whereNationalId($nationalID)->first();
        $studies = $patient ? $patient->studies()->get() : collect();

        $studies->each(fn (EchoStudy $study) => Audit::record('study.viewed', $study, ['extracted' => true]));

        return StudySummaries::response($studies->map(fn (EchoStudy $study) => [
            'id' => $study->id,
            'date' => $study->echo_date,
            'created_at' => $study->created_at,
            'status' => $study->status->value,
            'summary' => (string) $study->getAttribute('summary'),
            'url' => route('studies.show', $study),
        ]));
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
            $patient = PatientRecords::resolve($request->patientAttributes());
            $attributes = [...$this->attributesFor($request, $patient), 'patient_id' => $patient->id];

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
            ? redirect()->route('worklist')
            : redirect()->route('patients.index', ['nationalID' => $study->patient->national_id]);

        return $redirect->with('status', __('Draft deleted.'));
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function report(array $report, bool $isPreview, ?EchoStudy $study = null, ?EchoStudyVersion $version = null): View
    {
        // The preview is a bare page for the form's preview pane; saved reports open in the dashboard.
        return view($isPreview ? 'studies.preview' : 'studies.report', [
            'report' => $report,
            'hospital' => auth()->user()->hospital,
            'study' => $study,
            'version' => $version,
        ]);
    }

    /**
     * The study's fields, after checking a linked admission is the same patient's.
     *
     * @return array<string, mixed>
     */
    private function attributesFor(StoreEchoStudyRequest $request, Patient $patient): array
    {
        $attributes = $request->studyAttributes();

        if ($attributes['admission_id'] && Admission::find($attributes['admission_id'])?->patient_id !== $patient->id) {
            throw ValidationException::withMessages(['admission_id' => __('The admission belongs to another patient.')]);
        }

        return $attributes;
    }
}
