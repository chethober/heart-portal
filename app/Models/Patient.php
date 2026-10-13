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
 * @property string|null $father_name
 * @property string|null $nationality
 * @property string|null $nationality_other
 * @property int|null $marital_status
 * @property string|null $occupation
 * @property string|null $home_phone
 * @property string|null $emergency_phone
 * @property string|null $email
 * @property string|null $address
 */
#[Fillable([
    'hospital_id', 'national_id', 'first_name', 'last_name', 'dob', 'gender', 'phone_number',
    'father_name', 'nationality', 'nationality_other', 'marital_status', 'occupation',
    'home_phone', 'emergency_phone', 'email', 'address',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use BelongsToHospital, HasFactory, SoftDeletes;

    /**
     * Heart's nationality codes (99999: another, named in nationality_other; PHP makes that key an int).
     *
     * @return array<int|string, string>
     */
    public static function nationalities(): array
    {
        return ['ir' => __('Iranian'), 'af' => __('Afghan'), 'iq' => __('Iraqi'), '99999' => __('Other nationality')];
    }

    /**
     * Heart's marital status codes.
     *
     * @return array<int, string>
     */
    public static function maritalStatuses(): array
    {
        return [1 => __('Single'), 2 => __('Married'), 3 => __('Widowed or divorced'), 4 => __('Without a guardian'), 9 => __('Unknown')];
    }

    public function nationalityLabel(): ?string
    {
        if ($this->nationality === '99999') {
            return $this->nationality_other ?: __('Other nationality');
        }

        return $this->nationality ? (self::nationalities()[$this->nationality] ?? $this->nationality) : null;
    }

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
            'home_phone' => 'encrypted',
            'emergency_phone' => 'encrypted',
            'email' => 'encrypted',
            'address' => 'encrypted',
            'marital_status' => 'integer',
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
