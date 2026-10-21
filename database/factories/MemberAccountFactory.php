<?php

namespace Database\Factories;

use App\Models\MemberAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberAccount>
 */
class MemberAccountFactory extends Factory
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
            'phone' => fake()->unique()->numerify('080########'),
            'password' => 'MemberPassword123!',
            'is_active' => true,
        ];
    }
}
