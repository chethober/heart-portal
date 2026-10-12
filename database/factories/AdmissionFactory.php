<?php

namespace Database\Factories;

use App\Models\Admission;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Admission>
 */
class AdmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'hospital_id' => fn (array $attributes) => Patient::withoutGlobalScopes()->find($attributes['patient_id'])->hospital_id,
            'ward' => 'CCU',
            'admitted_on' => '1405/07/01',
        ];
    }
}
