<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\EchoStudy;
use App\Models\Patient;
use App\Support\Audit;
use App\Support\Procedures;
use Illuminate\Database\Eloquent\Collection;
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
            $procedures = array_map(fn () => new Collection, Procedures::MODELS);
            $patient = Patient::whereNationalId($nationalID)->first();

            // The searched ID itself is not logged; a miss is recorded without it.
            Audit::record($patient ? 'patient.viewed' : 'patient.search_no_match', $patient);

            if ($patient) {
                $echoReports = $patient->studies()->with('author')->latest()->get()
                    ->each(fn (EchoStudy $study) => $study->setRelation('patient', $patient));

                foreach (Procedures::MODELS as $key => $model) {
                    $procedures[$key] = $model::where('patient_id', $patient->id)->latest()->get();
                }
            }
        }

        return view('dashboard.patients', ['nationalID' => $nationalID, 'patient' => $patient, 'echoReports' => $echoReports, 'procedures' => $procedures]);
    }
}
