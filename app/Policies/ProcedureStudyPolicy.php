<?php

namespace App\Policies;

use App\Models\EchoStudy;
use App\Models\ProcedureStudy;

/**
 * Procedures ported from heart: each has its own form permissions, like heart's access flags.
 */
class ProcedureStudyPolicy extends StudyPolicy
{
    protected function form(EchoStudy|ProcedureStudy $study): string
    {
        return $study::PROCEDURE;
    }
}
