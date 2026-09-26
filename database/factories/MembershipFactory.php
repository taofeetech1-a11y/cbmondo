<?php

namespace Database\Factories;

use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'phone' => fake()->unique()->numerify('080########'),
            'email' => null,
            'age_range' => '25-34',
            'gender' => 'female',
            'lga' => '1',
            'ward' => null,
            'pu' => null,
        ];
    }
}
