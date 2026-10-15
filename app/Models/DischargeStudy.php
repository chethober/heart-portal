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
     * Signed in order intern (کارورز), resident (دستیار), then the attending physician (پزشک معالج),
     * whose signature finalizes the summary. See the 2026_10_15_120000 migration.
     */
    public const SIGNOFFS = ['intern' => 'Intern', 'resident' => 'Resident'];

    protected function casts(): array
    {
        return [...parent::casts(), 'intern_signed_at' => 'datetime', 'resident_signed_at' => 'datetime'];
    }

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
