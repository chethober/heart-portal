<?php

namespace App\Models\Concerns;

use App\Enums\StudyStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The draft → final → amended workflow shared by every study type. Each signing
 * stores an immutable snapshot through the model's versions() relation.
 *
 * The model provides versions() and toReportArray(), and may override
 * signingAttributes() to stamp extra columns when it is signed.
 */
trait HasStudyLifecycle
{
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
            ...$this->signingAttributes($doctor),
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
            ...$this->signingAttributes($doctor),
        ])->save();

        $this->recordVersion($doctor, $reason);
    }

    /**
     * Extra columns to set whenever the study is signed.
     *
     * @return array<string, mixed>
     */
    protected function signingAttributes(User $doctor): array
    {
        return [];
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
}
