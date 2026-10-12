<?php

namespace App\Models;

use Database\Factories\AngiographyStudyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * One coronary angiography. Fields are listed in config/procedures/angiography.php.
 */
class AngiographyStudy extends ProcedureStudy
{
    /** @use HasFactory<AngiographyStudyFactory> */
    use HasFactory;

    public const PROCEDURE = 'angiography';
}
