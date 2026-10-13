<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hospital_id' => Hospital::factory(),
            'name' => fake()->unique()->randomElement(['CCU', 'ICU', 'Cardiology', 'Emergency', 'Post-CCU', 'Cardiac Surgery', 'Cath Lab', 'Internal Medicine']).' '.fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ];
    }
}
