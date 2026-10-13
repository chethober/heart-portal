<?php

namespace App\Policies;

use App\Models\EchoStudy;
use App\Models\ProcedureStudy;

class EchoStudyPolicy extends StudyPolicy
{
    protected function form(EchoStudy|ProcedureStudy $study): string
    {
        return 'echo';
    }
}
