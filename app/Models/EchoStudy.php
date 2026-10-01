<?php

namespace App\Models;

use App\Enums\StudyStatus;
use App\Models\Concerns\BelongsToHospital;
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
    use BelongsToHospital, HasFactory, SoftDeletes;

    public function getFillable(): array
    {
        return [
            'hospital_id', 'patient_id', 'created_by', 'legacy_id',
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
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function amender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'amended_by');
    }

    /**
     * @return HasMany<EchoStudyVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(EchoStudyVersion::class)->orderBy('version');
    }

    public function isSigned(): bool
    {
        return $this->status->isSigned();
    }

    /**
     * Sign a draft as the reporting doctor; the report is locked afterwards.
     */
    public function finalize(User $doctor): void
    {
        $this->forceFill([
            'status' => StudyStatus::Final,
            'reported_by' => $doctor->id,
            'finalized_at' => now(),
            'doctor' => $doctor->name,
        ])->save();

        $this->recordVersion($doctor);
    }

    /**
     * Change a signed report. The amending doctor re-signs it with a reason.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function amend(array $attributes, User $doctor, string $reason): void
    {
        $this->fill($attributes)->forceFill([
            'status' => StudyStatus::Amended,
            'amended_by' => $doctor->id,
            'amended_at' => now(),
            'amendment_reason' => $reason,
            'doctor' => $doctor->name,
        ])->save();

        $this->recordVersion($doctor, $reason);
    }

    private function recordVersion(User $signer, ?string $reason = null): void
    {
        $this->versions()->create([
            'hospital_id' => $this->hospital_id,
            'version' => ($this->versions()->max('version') ?? 0) + 1,
            'status' => $this->status,
            'data' => $this->load('patient')->toReportArray(),
            'signed_by' => $signer->id,
            'signature_path' => $signer->signature_path,
            'reason' => $reason,
        ]);
    }

    /**
     * Raw field values as the form expects them, for editing.
     *
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        $values = collect($this->toReportArray())
            ->only([...config('echo.patient_fields'), ...config('echo.meta_fields'), ...array_keys(config('echo.fields'))])
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
