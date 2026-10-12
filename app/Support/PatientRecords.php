<?php

namespace App\Support;

use App\Models\Patient;

final class PatientRecords
{
    /**
     * Finds the patient by national ID (or creates them) and refreshes their demographics.
     * Call inside a transaction: the patient row is locked until it commits.
     *
     * @param  array<string, mixed>  $attributes  Patient columns, including national_id.
     */
    public static function resolve(array $attributes): Patient
    {
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
}
