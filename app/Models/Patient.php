<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHospital;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $national_id
 * @property string|null $phone_number
 */
#[Fillable(['hospital_id', 'national_id', 'first_name', 'last_name', 'dob', 'gender', 'phone_number'])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use BelongsToHospital, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Patient $patient) {
            if ($patient->isDirty('national_id')) {
                $patient->national_id_hash = static::hashNationalId($patient->national_id);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'national_id' => 'encrypted',
            'phone_number' => 'encrypted',
        ];
    }

    /**
     * Keyed hash so encrypted national IDs can still be looked up exactly.
     */
    public static function hashNationalId(string $nationalId): string
    {
        return hash_hmac('sha256', static::normalizeNationalId($nationalId), config('app.key'));
    }

    public static function normalizeNationalId(string $nationalId): string
    {
        // Accept Persian/Arabic digits and stray separators from manual entry.
        $digits = strtr($nationalId, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/[\s\-]/u', '', $digits);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWhereNationalId(Builder $query, string $nationalId): void
    {
        $query->where('national_id_hash', static::hashNationalId($nationalId));
    }

    /**
     * @return HasMany<EchoStudy, $this>
     */
    public function studies(): HasMany
    {
        return $this->hasMany(EchoStudy::class);
    }

    /**
     * @return HasMany<Admission, $this>
     */
    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    /**
     * Demographics keyed by the names the echo form and report use.
     *
     * @return array<string, string|null>
     */
    public function toFormArray(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'nationalID' => $this->national_id,
            'dob' => $this->dob,
            'phone_number' => $this->phone_number,
            'gender' => $this->gender,
        ];
    }
}
