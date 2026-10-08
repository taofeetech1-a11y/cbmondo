<?php

namespace Database\Factories;

use App\Models\ExecutiveAssignment;
use App\Models\ExecutivePosition;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExecutiveAssignment>
 */
class ExecutiveAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'executive_position_id' => ExecutivePositionFactory::new(),
            'membership_id' => MembershipFactory::new(),
            'assignment_group' => 'state',
            'scope_key' => fn (array $attributes) => ExecutivePosition::find($attributes['executive_position_id'])->level === 'lga' ? (int) Membership::find($attributes['membership_id'])->lga : 0,
        ];
    }
}
