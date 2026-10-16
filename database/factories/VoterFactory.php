<?php

namespace Database\Factories;

use App\Models\Voter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voter>
 */
class VoterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vin' => fake()->unique()->numerify('#### #### #### #### ###'),
            'full_name' => fake()->name(),
            'surname' => fake()->lastName(),
            'other_names' => fake()->firstName(),
            'gender' => 'Female',
            'age' => 30,
        ];
    }
}
