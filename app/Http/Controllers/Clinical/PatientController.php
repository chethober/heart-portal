<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\EchoStudy;
use App\Models\Patient;
use App\Models\Ward;
use App\Support\Audit;
use App\Support\Forms;
use App\Support\NationalId;
use App\Support\PatientSearch;
use App\Support\Procedures;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * The dashboard's "Patients" section: search by national ID, HIS admission number or name
     * (PatientSearch, heart's search), then list one patient's studies or the matching patients;
     * before a search, who is in hospital by ward. The search text itself is never logged.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('nationalID', ''));
        $nationalID = '';
        $patient = null;
        $results = null;
        $kind = null;
        $fromHis = false;
        $echoReports = new Collection;
        $procedures = [];

        if ($search !== '') {
            ['patients' => $matches, 'kind' => $kind, 'fromHis' => $fromHis] = PatientSearch::search($search);

            if ($matches->count() === 1) {
                $patient = $matches->first();
                $nationalID = $patient->national_id;
            } elseif ($matches->isEmpty() && preg_match('/^[0-9A-Za-z]*\d[0-9A-Za-z]*$/', Patient::normalizeNationalId($search))) {
                // Not on record yet (an ID, possibly a foreign one): the page offers to start a study for it.
                $nationalID = Patient::normalizeNationalId($search);
            } else {
                $results = $matches->load(['admissions' => fn ($query) => $query->orderByDesc('admitted_on')]);
                Audit::record($matches->isEmpty() ? 'patient.search_no_match' : 'patient.searched', null, $matches->isEmpty() ? [] : ['matches' => $matches->count()]);
            }
        }

        if ($nationalID !== '') {
            // Only the forms this member may view (per-form permissions).
            $procedures = array_fill_keys(Forms::procedures($request->user(), 'view'), new Collection);

            // A miss is recorded without the searched ID.
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

        return view('dashboard.patients', [
            'search' => $search,
            'nationalID' => $nationalID,
            'patient' => $patient,
            'results' => $results,
            'kind' => $kind,
            'fromHis' => $fromHis,
            'echoReports' => $echoReports,
            'procedures' => $procedures,
            // Without a search the page lists who is in hospital now, by ward (heart's home page).
            ...($search === '' ? $this->inHospital($request) : []),
        ]);
    }

    /**
     * Patients currently admitted (no discharge date), optionally in one ward.
     *
     * @return array{wards: Collection<int, Ward>, ward: Ward|null, wardId: int|null, admissions: Collection<int, Admission>}
     */
    private function inHospital(Request $request): array
    {
        $wardId = $request->integer('ward') ?: null;

        return [
            'wards' => Ward::query()->active()->orderBy('name')->get(),
            'ward' => $wardId ? Ward::find($wardId) : null,
            'wardId' => $wardId,
            'admissions' => Admission::query()
                ->current()
                ->with(['patient', 'wardRecord'])
                ->whereHas('patient')
                ->when($wardId, fn ($query) => $query->where('ward_id', $wardId))
                ->orderBy('admitted_on')
                ->orderBy('id')
                ->get(),
        ];
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
                        'number' => $admission->his_id,
                        'label' => $admission->summary().($admission->isDischarged() ? ' (discharged '.$admission->discharged_on.')' : ''),
                    ]),
            ] : null,
        ]);
    }
}
