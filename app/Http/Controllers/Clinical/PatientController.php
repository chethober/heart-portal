<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\EchoStudy;
use App\Models\Patient;
use App\Services\EchoCalculations;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * Search by national ID and list that patient's echo studies.
     */
    public function index(Request $request): View
    {
        $nationalID = trim((string) $request->query('nationalID', ''));
        $echoReports = [];

        if ($nationalID !== '') {
            $patient = Patient::whereNationalId($nationalID)->first();

            // The searched ID itself is not logged; a miss is recorded without it.
            Audit::record($patient ? 'patient.viewed' : 'patient.search_no_match', $patient);

            $echoReports = $patient?->studies()
                ->latest()
                ->get()
                ->map(function (EchoStudy $study) use ($patient, $request) {
                    $study->setRelation('patient', $patient);

                    return [
                        ...EchoCalculations::derive($study->toReportArray()),
                        'status' => $study->status->value,
                        'can_edit' => $request->user()->can('update', $study),
                        'can_delete' => $request->user()->can('delete', $study),
                    ];
                })
                ->all() ?? [];
        }

        return view('patients.history', ['nationalID' => $nationalID, 'echoReports' => $echoReports]);
    }
}
