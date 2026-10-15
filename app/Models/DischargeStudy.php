<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One discharge summary (heart's main registry form). Fields are listed in config/procedures/discharge.php.
 */
class DischargeStudy extends ProcedureStudy
{
    public const PROCEDURE = 'discharge';

    /**
     * Studies saved before history_mode existed (and heart's) wrote their history by hand when
     * there is one, so the form opens them in manual mode and saving keeps the text.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function historyMode(): Attribute
    {
        return Attribute::get(fn (?string $value, array $attributes) => $value
            ?? (filled($attributes['non_specific_explanation'] ?? null) ? 'manual' : null));
    }

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
