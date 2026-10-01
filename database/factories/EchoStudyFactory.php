<?php

namespace Database\Factories;

use App\Models\EchoStudy;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EchoStudy>
 */
class EchoStudyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'hospital_id' => fn (array $attributes) => Patient::withoutGlobalScopes()->find($attributes['patient_id'])->hospital_id,
            'echo_date' => '1405/07/09',
            'Height' => 175,
            'Weight' => 75,
            'lv_EF' => 55,
            'lv_LVEDV' => 120,
            'lv_LVEDD' => 50,
            'summary' => 'Normal study.',
        ];
    }
}
