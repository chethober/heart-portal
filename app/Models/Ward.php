<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHospital;
use Database\Factories\WardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A hospital ward (heart's location.ward): admissions are made to a ward and the
 * patients page lists who is in each one.
 *
 * @property int $id
 * @property int $hospital_id
 * @property string $name
 * @property bool $is_active
 */
#[Fillable(['hospital_id', 'name', 'is_active'])]
class Ward extends Model
{
    /** @use HasFactory<WardFactory> */
    use BelongsToHospital, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return HasMany<Admission, $this>
     */
    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    /**
     * The hospital's ward with this name, created if the hospital has none yet
     * (for admissions recorded or imported with only a ward name).
     */
    public static function findOrCreateNamed(int $hospitalId, string $name): self
    {
        $name = mb_substr(trim($name), 0, 100);

        return static::withoutGlobalScopes()->firstOrCreate(['hospital_id' => $hospitalId, 'name' => $name]);
    }
}
