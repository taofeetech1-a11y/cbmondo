<?php

namespace Database\Factories;

use App\Models\ExecutivePosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExecutivePosition>
 */
class ExecutivePositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Chairperson',
            'name_key' => 'chairperson',
            'level' => 'state',
            'location_key' => 0,
        ];
    }
}
