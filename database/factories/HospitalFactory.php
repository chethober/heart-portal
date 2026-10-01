<?php

namespace Database\Factories;

use App\Enums\HospitalStatus;
use App\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Hospital>
 */
class HospitalFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company().' Hospital';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'city' => fake()->city(),
            'phone' => fake()->phoneNumber(),
            'status' => HospitalStatus::Active,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => HospitalStatus::Pending, 'approved_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => HospitalStatus::Suspended]);
    }
}
