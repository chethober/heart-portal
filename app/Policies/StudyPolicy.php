<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\EchoStudy;
use App\Models\ProcedureStudy;
use App\Models\User;
use App\Support\Forms;

/**
 * Rules shared by every study type; see EchoStudyPolicy and ProcedureStudyPolicy. Each ability
 * needs the user's permission for that form (e.g. "angiography.edit"), granted per user.
 */
abstract class StudyPolicy
{
    /**
     * The study's key in Forms::keys().
     */
    abstract protected function form(EchoStudy|ProcedureStudy $study): string;

    public function view(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        return $this->sameHospital($user, $study) && $user->can('patients.view') && $this->allows($user, $study, 'view');
    }

    /**
     * Drafts can be edited by clinical staff; signed reports only amended by those who may sign.
     * Once a sign-off step is signed (the discharge summary's intern, resident), the draft is
     * edited only by those who may sign: the next signer may change what the last one signed.
     */
    public function update(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        if (! $this->sameHospital($user, $study)) {
            return false;
        }

        return $this->allows($user, $study, $study->isSigned() || $this->hasSignoffs($study) ? 'finalize' : 'edit');
    }

    public function finalize(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        return $this->sameHospital($user, $study) && ! $study->isSigned() && $this->allows($user, $study, 'finalize');
    }

    /**
     * Only unsigned drafts (no sign-off step signed either) can be removed, by their author or a hospital admin.
     * Deletion is soft, so the record stays recoverable.
     */
    public function delete(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        if (! $this->sameHospital($user, $study) || $study->isSigned() || $this->hasSignoffs($study)) {
            return false;
        }

        $isAuthor = $study->created_by === $user->id && $this->allows($user, $study, 'create');

        return $isAuthor || $user->hasRole(Role::HospitalAdmin->value);
    }

    private function allows(User $user, EchoStudy|ProcedureStudy $study, string $ability): bool
    {
        return Forms::allows($user, $this->form($study), $ability);
    }

    private function hasSignoffs(EchoStudy|ProcedureStudy $study): bool
    {
        return $study instanceof ProcedureStudy && $study->hasSignoffs();
    }

    private function sameHospital(User $user, EchoStudy|ProcedureStudy $study): bool
    {
        return $user->hospital_id !== null && $user->hospital_id === $study->hospital_id;
    }
}
