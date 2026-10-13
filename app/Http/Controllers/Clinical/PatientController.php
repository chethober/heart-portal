<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\EchoStudy;
use App\Models\Patient;
use App\Support\Audit;
use App\Support\Forms;
use App\Support\NationalId;
use App\Support\Procedures;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * The dashboard's "Patient echos" section: search by national ID and list that patient's studies.
     */
    public function index(Request $request): View
    {
        $nationalID = trim((string) $request->query('nationalID', ''));
        $patient = null;
        $echoReports = new Collection;
        $procedures = [];

        if ($nationalID !== '') {
            // Only the forms this member may view (per-form permissions).
            $procedures = array_fill_keys(Forms::procedures($request->user(), 'view'), new Collection);
            $patient = Patient::whereNationalId($nationalID)->first();

            // The searched ID itself is not logged; a miss is recorded without it.
            Audit::record($patient ? 'patient.viewed' : 'patient.search_no_match', $patient);

            if ($patient && Forms::allows($request->user(), 'echo', 'view')) {
                $echoReports = $patient->studies()->with('author')->latest()->get()
                    ->each(fn (EchoStudy $study) => $study->setRelation('patient', $patient));

            }
            if ($patient) {
                foreach (array_keys($procedures) as $key) {
                    $procedures[$key] = Procedures::model($key)::where('patient_id', $patient->id)->latest()->get();
                }
            }
        }

        return view('dashboard.patients', ['nationalID' => $nationalID, 'patient' => $patient, 'echoReports' => $echoReports, 'procedures' => $procedures]);
    }

    /**
     * For the study forms: whether a national ID belongs to a registered patient of this hospital,
     * and if so their details and admissions, to fill in the form. Like the patient search, the
     * searched ID itself is not logged.
     */
    public function lookup(Request $request): JsonResponse
    {
        $nationalID = Patient::normalizeNationalId(trim((string) $request->query('nationalID', '')));
        abort_unless(preg_match('/^[0-9A-Za-z]{1,32}$/', $nationalID) === 1, 422);

        $patient = Patient::whereNationalId($nationalID)->first();
        Audit::record($patient ? 'patient.viewed' : 'patient.search_no_match', $patient);

        return response()->json([
            'valid' => NationalId::isValid($nationalID),
            'patient' => $patient ? [
                ...$patient->toFormArray(),
                'url' => route('patients.show', $patient),
                'admissions' => $patient->admissions()->with('wardRecord')->orderByDesc('admitted_on')->orderByDesc('id')->get()
                    ->map(fn (Admission $admission) => [
                        'id' => $admission->id,
                        'label' => $admission->summary().($admission->isDischarged() ? ' (discharged '.$admission->discharged_on.')' : ''),
                    ]),
            ] : null,
        ]);
    }
}
