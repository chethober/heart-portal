<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureHospitalIsActive;
use App\Http\Requests\StartStudyRequest;
use App\Models\Admission;
use App\Models\Patient;
use App\Support\Audit;
use App\Support\Forms;
use App\Support\JalaliCalendar;
use App\Support\PatientRecords;
use App\Support\StudyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard home: step 1 of a new study (the patient) for staff who record studies.
 * Everyone else lands on the first section they may open (worklist or hospitals).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isPlatformAdmin()) {
            return redirect()->route('admin.hospitals.index');
        }

        // Everything from here shows or links to patient data, so it needs the same membership
        // checks as the clinical routes (platform admins were redirected above).
        return app(EnsureHospitalIsActive::class)->handle($request, function () use ($request, $user) {
            // Links from before the sections had their own URLs.
            if ($request->query('tab') === 'patients') {
                return redirect()->route('patients.index', $request->only('nationalID'));
            }
            if ($request->hasAny(['status', 'search'])) {
                return redirect()->route('worklist', $request->only(['status', 'search', 'page']));
            }
            if (! Forms::canRecordAny($user)) {
                return redirect()->route('worklist');
            }

            // Step 1 of a new study: who it is for. Arriving from a patient or admission link, or
            // back from a study's taskbar tab to correct it, fills the details in.
            $context = StudyContext::get($request);
            $patient = $context['patient'] ?? ($request->filled('patient') ? Patient::findOrFail($request->integer('patient')) : null);
            if (! $patient && $request->filled('nationalID')) {
                $patient = Patient::whereNationalId((string) $request->query('nationalID'))->first();
            }
            $admission = $context['admission'] ?? null;
            if ($request->filled('admission')) {
                $admission = Admission::findOrFail($request->integer('admission'));
                abort_if($patient && $admission->patient_id !== $patient->id, 404);
                $patient ??= $admission->patient;
            }
            if ($patient) {
                Audit::record('patient.viewed', $patient);
            }

            return response(view('dashboard.index', [
                'initial' => [
                    ...($patient?->toFormArray() ?? ['nationalID' => (string) $request->query('nationalID', '')]),
                    'study_date' => $context['study_date'] ?? JalaliCalendar::today(),
                    'admission_number' => $admission->his_id ?? '',
                ],
                // Back from a study's tab to correct its details: resubmitting replaces that tab.
                'replaces' => $context ? $request->query('context') : null,
            ]));
        });
    }

    /**
     * Step 1 submitted: register or update the patient (and a new admission number), then open
     * the chosen study form for them in a taskbar tab.
     */
    public function start(StartStudyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $context = DB::transaction(function () use ($request, $data) {
            $patient = PatientRecords::resolve($request->patientAttributes());
            $admission = null;
            if (filled($data['admission_number'] ?? null)) {
                $number = trim($data['admission_number']);
                $admission = Admission::whereIn('his_id', [$number, $number.'.00'])->first();
                if ($admission && $admission->patient_id !== $patient->id) {
                    throw ValidationException::withMessages(['admission_number' => __('The admission belongs to another patient.')]);
                }
                if (! $admission) {
                    if (! $request->user()->can('admission.create')) {
                        throw ValidationException::withMessages(['admission_number' => __('No admission found.')]);
                    }
                    $admission = $patient->admissions()->create(['his_id' => $number]);
                    Audit::record('admission.created', $admission);
                }
            }

            return [
                'user_id' => $request->user()->id, 'hospital_id' => $request->user()->hospital_id,
                'patient_id' => $patient->id, 'admission_id' => $admission?->id,
                'study_date' => $data['study_date'] ?? null, 'form' => $data['study'],
            ];
        });
        $token = StudyContext::put($request, $context, $data['replaces'] ?? null);

        return redirect()->to(StudyContext::url($token, $data['study']));
    }

    /** Close a taskbar tab; closing the one being viewed moves to the last tab left, or step 1. */
    public function close(Request $request, string $context): RedirectResponse
    {
        StudyContext::forget($request, $context);
        if ($request->input('current') !== $context) {
            return redirect()->back();
        }
        $last = last(StudyContext::tabs($request));

        return $last ? redirect()->to($last['url']) : redirect()->route('dashboard');
    }
}
