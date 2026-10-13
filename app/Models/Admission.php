<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHospital;
use App\Support\JalaliCalendar;
use Database\Factories\AdmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A hospital stay. Studies done during the stay link to it, as heart's records
 * link to its admit table.
 *
 * `ward` keeps the ward's name as recorded (imported admissions may only have the
 * name); a name without a ward_id is linked to the hospital's ward of that name on save.
 *
 * @property int $id
 * @property int $hospital_id
 * @property int $patient_id
 * @property int|null $ward_id
 * @property string|null $ward
 * @property string|null $admitted_on
 * @property string|null $admitted_at
 * @property int|null $admission_type
 * @property int|null $arrival_mode
 * @property string|null $referred_from
 * @property string|null $discharged_on
 * @property string|null $discharged_at
 * @property string|null $physician
 * @property string|null $his_id
 * @property array<string, string>|null $his_codes
 * @property-read Ward|null $wardRecord
 */
#[Fillable([
    'hospital_id', 'patient_id', 'ward_id', 'ward', 'admitted_on', 'admitted_at', 'admission_type', 'arrival_mode',
    'referred_from', 'discharged_on', 'discharged_at', 'physician', 'his_id', 'his_codes', 'legacy_id',
])]
class Admission extends Model
{
    /** @use HasFactory<AdmissionFactory> */
    use BelongsToHospital, HasFactory, SoftDeletes;

    /**
     * Heart's admittype codes.
     *
     * @return array<int, string>
     */
    public static function admissionTypes(): array
    {
        return [0 => __('Independent admission'), 1 => __('Outpatient'), 2 => __('Inpatient')];
    }

    /**
     * Heart's admittype_direct codes; 3 asks for the referring hospital.
     *
     * @return array<int, string>
     */
    public static function arrivalModes(): array
    {
        return [
            1 => __('Emergency services (115)'),
            2 => __('Referred from a clinic'),
            3 => __('Sent from another hospital'),
            4 => __('Came directly'),
            5 => __('Transferred from another ward'),
            9 => __('Unknown'),
        ];
    }

    /**
     * Hospital-information-system ids kept from heart's admit form.
     */
    public const HIS_CODES = ['IDCode', 'PCode', 'AdmCode', 'SepasPatientID', 'SepasHospitalID'];

    protected static function booted(): void
    {
        // Registered after BelongsToHospital's creating hook, so hospital_id is set.
        $link = function (Admission $admission) {
            if ($admission->ward_id === null && filled($admission->ward) && $admission->hospital_id) {
                $admission->ward_id = Ward::findOrCreateNamed($admission->hospital_id, $admission->ward)->id;
            }
        };

        static::creating($link);
        static::updating($link);
    }

    protected function casts(): array
    {
        return [
            'admission_type' => 'integer',
            'arrival_mode' => 'integer',
            'his_codes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * The ward record; named so it does not clash with the `ward` name column.
     *
     * @return BelongsTo<Ward, $this>
     */
    public function wardRecord(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id');
    }

    /**
     * @return HasMany<EchoStudy, $this>
     */
    public function echoStudies(): HasMany
    {
        return $this->hasMany(EchoStudy::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('discharged_on')->orWhere('discharged_on', ''));
    }

    public function isDischarged(): bool
    {
        return filled($this->discharged_on);
    }

    public function wardName(): ?string
    {
        return $this->wardRecord->name ?? $this->ward;
    }

    /**
     * Length of stay in days, to today while the patient is still admitted (heart's disch_los).
     */
    public function lengthOfStay(): ?int
    {
        return JalaliCalendar::daysBetween($this->admitted_on, $this->isDischarged() ? $this->discharged_on : null);
    }

    /**
     * Short description for lists and pickers, e.g. "1405/07/01 · CCU".
     */
    public function summary(): string
    {
        return implode(' · ', array_filter([$this->admitted_on, $this->wardName(), $this->his_id ? 'HIS '.$this->his_id : null]));
    }
}
