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
     * Sign-off steps before the final signature (key => label), in order; each has the columns
     * "{step}_signed_by", "{step}_signed_at" and "{step}_signature_path". The final signature
     * (finalize()) comes after all of them. Only the discharge summary has any.
     *
     * @var array<string, string>
     */
    public const SIGNOFFS = [];

    /**
     * Label of the final signature when the procedure has sign-off steps.
     */
    public const FINAL_SIGNOFF = 'Attending physician';

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
     * The sign-off step that signs next, or null when the next signature finalizes the report
     * (or it is already signed).
     */
    public function nextSignoff(): ?string
    {
        if ($this->isSigned()) {
            return null;
        }

        foreach (array_keys(static::SIGNOFFS) as $step) {
            if ($this->getAttribute("{$step}_signed_at") === null) {
                return $step;
            }
        }

        return null;
    }

    public function nextSignoffLabel(): string
    {
        return static::SIGNOFFS[$this->nextSignoff()] ?? static::FINAL_SIGNOFF;
    }

    /**
     * Whether a sign-off step has been signed, so the draft is now changed only by those who sign.
     */
    public function hasSignoffs(): bool
    {
        return collect(array_keys(static::SIGNOFFS))->contains(fn (string $step) => $this->getAttribute("{$step}_signed_at") !== null);
    }

    /**
     * Who signed each sign-off step so far, keyed by step.
     *
     * @return array<string, array{signer: User|null, signed_at: Carbon, signature_path: string|null}>
     */
    public function signoffs(): array
    {
        $signoffs = [];

        foreach (array_keys(static::SIGNOFFS) as $step) {
            if (($signedAt = $this->getAttribute("{$step}_signed_at")) !== null) {
                $signoffs[$step] = [
                    'signer' => User::find($this->getAttribute("{$step}_signed_by")),
                    'signed_at' => Carbon::parse($signedAt),
                    'signature_path' => $this->getAttribute("{$step}_signature_path"),
                ];
            }
        }

        return $signoffs;
    }

    /**
     * Signs the next step: a sign-off step, or the final signature that locks the report.
     * Each signer may change the draft before signing, including what earlier steps signed.
     */
    public function signNext(User $doctor): void
    {
        $step = $this->nextSignoff();

        if ($step === null) {
            $this->finalize($doctor);

            return;
        }

        $this->forceFill([
            "{$step}_signed_by" => $doctor->id,
            "{$step}_signed_at" => now(),
            "{$step}_signature_path" => $doctor->signature_path,
        ])->save();
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
