<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Audit
{
    /**
     * Record an action. The hospital defaults to the subject's, then the actor's.
     *
     * @param  array<string, mixed>  $changes
     */
    public static function record(string $action, ?Model $subject = null, array $changes = [], ?User $actor = null): AuditLog
    {
        $actor ??= Auth::user();
        $request = request();

        return AuditLog::create([
            'hospital_id' => $subject instanceof Hospital ? $subject->id : ($subject?->getAttribute('hospital_id') ?? $actor?->hospital_id),
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
            'subject_id' => $subject?->getKey(),
            'changes' => $changes ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    /**
     * Field-level diff of a model's pending changes, as [field => [old, new]].
     * Encrypted attributes are reported as changed without their values.
     *
     * @return array<string, mixed>
     */
    public static function diff(Model $model): array
    {
        $diff = [];

        foreach ($model->getDirty() as $field => $new) {
            if (in_array($field, ['updated_at', 'created_at'], true)) {
                continue;
            }

            $diff[$field] = $model->hasCast($field, ['encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:object'])
                ? '[redacted]'
                : [$model->getOriginal($field), $model->getAttribute($field)];
        }

        return $diff;
    }
}
