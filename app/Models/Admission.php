<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHospital;
use Database\Factories\AdmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A hospital stay. Studies done during the stay link to it, as heart's records
 * link to its admit table.
 */
#[Fillable(['hospital_id', 'patient_id', 'ward', 'admitted_on', 'discharged_on', 'physician', 'his_id', 'legacy_id'])]
class Admission extends Model
{
    /** @use HasFactory<AdmissionFactory> */
    use BelongsToHospital, HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
