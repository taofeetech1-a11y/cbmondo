<?php

namespace Database\Factories;

use App\Models\PollingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PollingUnit>
 */
class PollingUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ward_id' => WardFactory::new(), 'pu_number' => fake()->unique()->numberBetween(1, 99999), 'name' => fake()->streetName(), 'delimitation_code' => fake()->numerify('28/##/##/###'), 'next_member_number' => 1,
        ];
    }
}
