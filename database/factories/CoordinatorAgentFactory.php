<?php

namespace Database\Factories;

use App\Models\CoordinatorAgent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CoordinatorAgent> */
class CoordinatorAgentFactory extends Factory
{
    public function definition(): array
    {
        return ['membership_id' => MembershipFactory::new(), 'password' => 'temporary-password-123', 'password_changed_at' => now()];
    }
}
