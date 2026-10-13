<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One discharge summary (heart's main registry form). Fields are listed in config/procedures/discharge.php.
 */
class DischargeStudy extends ProcedureStudy
{
    public const PROCEDURE = 'discharge';

    /**
     * Discharge medications (heart's drugDischarge_ep prescription widget), newest first.
     *
     * @return HasMany<Prescription, $this>
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class)->latest('id');
    }
}
