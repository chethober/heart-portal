<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\EchoStudy;
use App\Models\ProcedureStudy;
use App\Models\User;

/**
 * Rules shared by every study type; see EchoStudyPolicy and ProcedureStudyPolicy.
 */
abstract class StudyPolicy
{
    public function view(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        return $this->sameHospital($user, $study) && $user->can('patients.view');
    }

    /**
     * Drafts can be edited by clinical staff; signed reports only amended by a doctor.
     */
    public function update(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        if (! $this->sameHospital($user, $study)) {
            return false;
        }

        return $study->isSigned() ? $user->can('studies.finalize') : $user->can('studies.edit');
    }

    public function finalize(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        return $this->sameHospital($user, $study) && ! $study->isSigned() && $user->can('studies.finalize');
    }

    /**
     * Only unsigned drafts can be removed, by their author or a hospital admin.
     * Deletion is soft, so the record stays recoverable.
     */
    public function delete(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        if (! $this->sameHospital($user, $study) || $study->isSigned()) {
            return false;
        }

        $isAuthor = $study->created_by === $user->id && $user->can('studies.create');

        return $isAuthor || $user->hasRole(Role::HospitalAdmin->value);
    }

    private function sameHospital(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        return $user->hospital_id !== null && $user->hospital_id === $study->hospital_id;
    }
}
