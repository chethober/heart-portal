<?php

namespace App\Models;

use App\Enums\PrescriptionStatus;
use App\Models\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * A prescription for one patient (heart's ep_prescriptions), optionally for an admission or as the
 * discharge medications of a discharge summary. Clinical staff draft it, a doctor signs it, and a
 * signed prescription can no longer change: it can only be cancelled (heart's ep_canceled) and
 * replaced by a new one.
 *
 * @property int $id
 * @property int $hospital_id
 * @property int $patient_id
 * @property int|null $admission_id
 * @property int|null $discharge_study_id
 * @property int|null $created_by
 * @property string|null $prescribed_on
 * @property string|null $notes
 * @property PrescriptionStatus $status
 * @property Carbon|null $signed_at
 * @property string|null $verification_token
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon $created_at
 */
#[Fillable(['hospital_id', 'patient_id', 'admission_id', 'discharge_study_id', 'prescribed_on', 'notes', 'created_by', 'legacy_id'])]
class Prescription extends Model
{
    use BelongsToHospital, SoftDeletes;

    /**
     * Columns cancelling a signed prescription may change.
     */
    private const CANCELLATION_COLUMNS = ['status', 'cancelled_by', 'cancelled_at', 'cancellation_reason', 'updated_at'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        // Enforced on the model too, so no code path can rewrite a signed prescription.
        static::updating(function (Prescription $prescription) {
            $was = PrescriptionStatus::from($prescription->getRawOriginal('status'));

            if ($was === PrescriptionStatus::Draft) {
                return;
            }

            $changed = array_keys($prescription->getDirty());
            $cancelling = $was === PrescriptionStatus::Signed && $prescription->status === PrescriptionStatus::Cancelled;

            if (! $cancelling || array_diff($changed, self::CANCELLATION_COLUMNS) !== []) {
                throw new LogicException('A signed prescription cannot be changed; cancel it and write a new one.');
            }
        });

        static::deleting(function (Prescription $prescription) {
            if ($prescription->getRawOriginal('status') !== PrescriptionStatus::Draft->value) {
                throw new LogicException('Only draft prescriptions can be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PrescriptionStatus::class,
            'signed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<Admission, $this>
     */
    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    /**
     * @return BelongsTo<DischargeStudy, $this>
     */
    public function dischargeStudy(): BelongsTo
    {
        return $this->belongsTo(DischargeStudy::class);
    }

    /**
     * @return HasMany<PrescriptionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class)->orderBy('position');
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
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isDraft(): bool
    {
        return $this->status === PrescriptionStatus::Draft;
    }

    public function isSigned(): bool
    {
        return $this->status === PrescriptionStatus::Signed;
    }

    public function isCancelled(): bool
    {
        return $this->status === PrescriptionStatus::Cancelled;
    }

    /**
     * Sign the draft as the prescribing doctor; it is locked afterwards.
     */
    public function sign(User $doctor): void
    {
        $this->forceFill([
            'status' => PrescriptionStatus::Signed,
            'signed_by' => $doctor->id,
            'signed_at' => now(),
            'verification_token' => Str::random(32),
        ])->save();
    }

    public function cancel(User $doctor, string $reason): void
    {
        $this->forceFill([
            'status' => PrescriptionStatus::Cancelled,
            'cancelled_by' => $doctor->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ])->save();
    }

    /**
     * The public link the printed QR code encodes. It carries only a random token: no patient data.
     */
    public function verificationUrl(): ?string
    {
        return $this->verification_token ? route('prescriptions.verify', $this->verification_token) : null;
    }
}
