<?php

namespace Database\Factories;

use App\Models\PollingUnit;
use App\Models\User;
use App\Models\VoterRegisterImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoterRegisterImport>
 */
class VoterRegisterImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'it']),
            'polling_unit_id' => PollingUnit::factory(),
            'original_name' => 'register.pdf',
            'pdf_path' => 'register-imports/'.fake()->uuid().'.pdf',
            'status' => 'queued',
        ];
    }
}
