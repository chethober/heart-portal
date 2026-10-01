<?php

namespace App\Models;

use App\Enums\HospitalStatus;
use Database\Factories\HospitalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property HospitalStatus $status
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $approved_at
 */
#[Fillable(['name', 'slug', 'city', 'address', 'phone', 'logo_path', 'status', 'plan', 'settings', 'approved_at'])]
class Hospital extends Model
{
    /** @use HasFactory<HospitalFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => HospitalStatus::class,
            'settings' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === HospitalStatus::Active;
    }
}
