<?php

namespace App\Models;

use App\Enums\StudyStatus;
use App\Models\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Immutable snapshot of a procedure study each time it is signed (finalized or amended).
 *
 * @property StudyStatus $status
 * @property array<string, mixed> $data
 * @property Carbon $created_at
 */
#[Fillable(['hospital_id', 'version', 'status', 'data', 'signed_by', 'signature_path', 'reason'])]
class ProcedureStudyVersion extends Model
{
    use BelongsToHospital;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'status' => StudyStatus::class,
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function study(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
