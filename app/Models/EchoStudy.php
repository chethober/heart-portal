<?php

namespace App\Models;

use App\Enums\StudyStatus;
use App\Models\Concerns\BelongsToHospital;
use App\Models\Concerns\HasStudyLifecycle;
use Database\Factories\EchoStudyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One echocardiogram. Clinical columns are listed in config/echo.php.
 *
 * @property StudyStatus $status
 * @property Carbon $created_at
 * @property Carbon|null $finalized_at
 * @property Carbon|null $amended_at
 */
class EchoStudy extends Model
{
    /** @use HasFactory<EchoStudyFactory> */
    use BelongsToHospital, HasFactory, HasStudyLifecycle, SoftDeletes;

    public function getFillable(): array
    {
        return [
            'hospital_id', 'patient_id', 'admission_id', 'created_by', 'legacy_id',
            ...config('echo.meta_fields'),
            ...array_keys(config('echo.fields')),
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return collect(config('echo.fields'))
            ->map(fn (string $type) => match ($type) {
                'number' => 'float',
                'boolean' => 'boolean',
                default => 'string',
            })
            ->merge([
                'status' => StudyStatus::class,
                'finalized_at' => 'datetime',
                'amended_at' => 'datetime',
            ])
            ->all();
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
     * @return HasMany<EchoStudyVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(EchoStudyVersion::class)->orderBy('version');
    }

    /**
     * The signing doctor's name is printed on the report.
     *
     * @return array<string, mixed>
     */
    protected function signingAttributes(User $doctor): array
    {
        return ['doctor' => $doctor->name];
    }

    /**
     * Raw field values as the form expects them, for editing.
     *
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        $values = collect([...$this->toReportArray(), 'admission_id' => $this->admission_id])
            ->only([...config('echo.patient_fields'), ...config('echo.meta_fields'), 'admission_id', ...array_keys(config('echo.fields'))])
            ->map(fn ($value) => match (true) {
                $value === true => '1',
                $value === false, $value === null => '',
                default => (string) $value,
            });

        return $values->all();
    }

    /**
     * Flat array in the shape of a legacy echo_reports row, which is what the
     * report templates and EchoCalculations consume.
     *
     * @return array<string, mixed>
     */
    public function toReportArray(): array
    {
        $row = ['id' => $this->id, 'created_at' => $this->created_at->toDateTimeString()];

        foreach ([...config('echo.meta_fields'), ...array_keys(config('echo.fields'))] as $field) {
            $value = $this->getAttribute($field);
            // Templates compare numbers as strings ("12" not "12.0") like SQLite returned them.
            $row[$field] = is_float($value) && floor($value) === $value ? (string) (int) $value : $value;
        }

        return [...$row, ...$this->patient->toFormArray()];
    }
}
