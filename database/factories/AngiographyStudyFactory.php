<?php

namespace Database\Factories;

use App\Models\AngiographyStudy;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AngiographyStudy>
 */
class AngiographyStudyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'hospital_id' => fn (array $attributes) => Patient::withoutGlobalScopes()->find($attributes['patient_id'])->hospital_id,
            'DateOf_Date_of_angiography' => '1405/07/09',
            'angiography_status' => 'Elective_angiography',
            'lad_Vessel_patency' => '3',
        ];
    }
}
