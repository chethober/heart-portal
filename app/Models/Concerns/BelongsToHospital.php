<?php

namespace App\Models\Concerns;

use App\Models\Hospital;
use App\Models\Scopes\HospitalScope;
use App\Support\CurrentHospital;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes every query on the model to the current hospital and stamps
 * hospital_id on create. Use on all tables that hold tenant data.
 */
trait BelongsToHospital
{
    public static function bootBelongsToHospital(): void
    {
        static::addGlobalScope(new HospitalScope);

        static::creating(function ($model) {
            $model->hospital_id ??= app(CurrentHospital::class)->id();
        });
    }

    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }
}
