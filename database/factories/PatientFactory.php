<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'national_id' => fake()->unique()->numerify('##########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'dob' => fake()->numberBetween(1320, 1400).'/0'.fake()->numberBetween(1, 9).'/1'.fake()->numberBetween(0, 9),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'phone_number' => '09'.fake()->numerify('#########'),
        ];
    }
}
