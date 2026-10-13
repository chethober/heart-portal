<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['hospital_id', 'name', 'email', 'password', 'medical_license_no', 'signature_path', 'is_active'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Mirror the column default so freshly created models are active in memory too.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Hospital, $this>
     */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function hasEnabledTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->hasRole(Role::PlatformAdmin->value);
    }

    /**
     * Gives the member this role (replacing any other) and resets their form access to the
     * role's defaults. Use this rather than assignRole()/syncRoles() for staff.
     */
    public function assignStaffRole(Role $role): void
    {
        $this->syncRoles([$role->value]);
        $this->syncPermissions($role->defaultFormPermissions());
    }

    /**
     * The form permissions this member may be granted, given their roles.
     *
     * @return list<string>
     */
    public function formCeiling(): array
    {
        return $this->getRoleNames()
            ->flatMap(fn (string $name) => Role::from($name)->formCeiling())
            ->unique()->values()->all();
    }

    public function primaryRole(): ?Role
    {
        $name = $this->getRoleNames()->first();

        return $name ? Role::from($name) : null;
    }
}
