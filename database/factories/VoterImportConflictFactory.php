<?php

namespace Database\Factories;

use App\Models\PollingUnit;
use App\Models\Voter;
use App\Models\VoterImportConflict;
use App\Models\VoterRegisterImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoterImportConflict>
 */
class VoterImportConflictFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'voter_register_import_id' => VoterRegisterImport::factory(),
            'polling_unit_id' => fn (array $attributes) => VoterRegisterImport::findOrFail($attributes['voter_register_import_id'])->polling_unit_id,
            'existing_voter_id' => Voter::factory()->for(PollingUnit::factory(), 'pollingUnit'),
            'existing_polling_unit_id' => fn (array $attributes) => Voter::findOrFail($attributes['existing_voter_id'])->polling_unit_id,
            'record_key' => fake()->sha256(),
            'vin' => fn (array $attributes) => Voter::findOrFail($attributes['existing_voter_id'])->vin,
            'reason' => 'vin_in_another_unit',
            'record' => ['full_name' => 'Incoming voter', 'serial' => 1],
            'photo_path' => null,
            'status' => 'pending',
        ];
    }
}
