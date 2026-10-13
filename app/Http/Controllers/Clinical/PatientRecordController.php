<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientDemographicsRequest;
use App\Models\Patient;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * A patient's record page: demographics and contact details from heart's patient
 * form, and their admissions.
 */
class PatientRecordController extends Controller
{
    public function show(Patient $patient): View
    {
        Audit::record('patient.viewed', $patient);

        return view('patients.show', [
            'patient' => $patient,
            'admissions' => $patient->admissions()->with('wardRecord')->orderByDesc('admitted_on')->orderByDesc('id')->get(),
        ]);
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', ['patient' => $patient]);
    }

    public function update(PatientDemographicsRequest $request, Patient $patient): RedirectResponse
    {
        $patient->fill($request->patientAttributes());
        $changes = Audit::diff($patient);
        $patient->save();

        if ($changes) {
            Audit::record('patient.updated', $patient, $changes);
        }

        return redirect()->route('patients.show', $patient)->with('status', __('Patient details saved.'));
    }
}
