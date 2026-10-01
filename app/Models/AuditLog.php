<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHospital;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only record of who did what. A database trigger rejects updates
 * and deletes on audit_logs; the model refuses them too.
 *
 * @property array<string, mixed>|null $changes
 * @property Carbon $created_at
 */
#[Fillable(['hospital_id', 'user_id', 'action', 'subject_type', 'subject_id', 'changes', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    use BelongsToHospital;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit logs are append-only.'));
    }

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
