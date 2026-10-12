<?php

namespace App\Models;

/**
 * One discharge summary (heart's main registry form). Fields are listed in config/procedures/discharge.php.
 */
class DischargeStudy extends ProcedureStudy
{
    public const PROCEDURE = 'discharge';
}
