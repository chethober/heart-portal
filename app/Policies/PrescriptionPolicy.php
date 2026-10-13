<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Prescription;
use App\Models\User;

/**
 * As in heart, staff who fill in the clinical forms (nurses, technicians, quality control) may
 * draft a prescription, e.g. the discharge drugs on a discharge summary, but only a doctor
 * (studies.finalize) signs it or cancels a signed one.
 */
class PrescriptionPolicy
{
    public function view(User $user, Prescription $prescription): bool
    {
        return $this->sameHospital($user, $prescription) && $user->can('patients.view');
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $this->sameHospital($user, $prescription) && $prescription->isDraft() && $user->can('studies.edit');
    }

    public function sign(User $user, Prescription $prescription): bool
    {
        return $this->sameHospital($user, $prescription) && $prescription->isDraft() && $user->can('studies.finalize');
    }

    public function cancel(User $user, Prescription $prescription): bool
    {
        return $this->sameHospital($user, $prescription) && $prescription->isSigned() && $user->can('studies.finalize');
    }

    /**
     * Drafts can be removed by their author or a hospital admin; signed ones are cancelled instead.
     */
    public function delete(User $user, Prescription $prescription): bool
    {
        if (! $this->sameHospital($user, $prescription) || ! $prescription->isDraft()) {
            return false;
        }

        return ($prescription->created_by === $user->id && $user->can('studies.create'))
            || $user->hasRole(Role::HospitalAdmin->value);
    }

    private function sameHospital(User $user, Prescription $prescription): bool
    {
        return $user->hospital_id !== null && $user->hospital_id === $prescription->hospital_id;
    }
}
