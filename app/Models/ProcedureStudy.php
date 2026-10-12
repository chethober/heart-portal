<?php

namespace App\Models;

use App\Enums\StudyStatus;
use App\Models\Concerns\BelongsToHospital;
use App\Models\Concerns\HasStudyLifecycle;
use App\Support\Procedures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A study of one of the procedures ported from the heart system. Subclasses only
 * name their procedure; the fields come from config/procedures/{procedure}.php.
 *
 * @property int $id
 * @property int $hospital_id
 * @property int $patient_id
 * @property int|null $admission_id
 * @property int|null $created_by
 * @property string|null $amendment_reason
 * @property StudyStatus $status
 * @property Carbon $created_at
 * @property Carbon|null $finalized_at
 * @property Carbon|null $amended_at
 */
abstract class ProcedureStudy extends Model
{
    use BelongsToHospital, HasStudyLifecycle, SoftDeletes;

    /**
     * Key in Procedures::MODELS and config/procedures.
     */
    public const PROCEDURE = '';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    public function getTable(): string
    {
        return static::PROCEDURE.'_studies';
    }

    public function getFillable(): array
    {
        return ['hospital_id', 'patient_id', 'admission_id', 'created_by', 'legacy_id', ...array_keys($this->fields())];
    }

    protected function casts(): array
    {
        return collect($this->fields())
            ->map(fn (array $field) => Procedures::cast($field['type']))
            ->merge([
                'status' => StudyStatus::class,
                'finalized_at' => 'datetime',
                'amended_at' => 'datetime',
            ])
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function fields(): array
    {
        return Procedures::fields(static::PROCEDURE);
    }

    /**
     * Date of the procedure as entered on the form (Jalali).
     */
    public function performedOn(): ?string
    {
        return $this->getAttribute(config('procedures.'.static::PROCEDURE.'.date_field'));
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Admission, $this>
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * @return MorphMany<ProcedureStudyVersion, $this>
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(ProcedureStudyVersion::class, 'study')->orderBy('version');
    }

    /**
     * Field values with the patient's demographics, as the report shows them
     * and as signed versions store them.
     *
     * @return array<string, mixed>
     */
    public function toReportArray(): array
    {
        $row = ['id' => $this->id, 'created_at' => $this->created_at->toDateTimeString()];

        foreach (array_keys($this->fields()) as $field) {
            $row[$field] = $this->getAttribute($field);
        }

        return [...$row, ...$this->patient->toFormArray()];
    }
}
