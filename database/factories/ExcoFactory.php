<?php

namespace Database\Factories;

use App\Models\Exco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exco>
 */
class ExcoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_id' => MembershipFactory::new(),
        ];
    }
}
