<?php

namespace Database\Seeders;

use App\Models\CoordinatorAgent;
use Illuminate\Database\Seeder;

class CoordinatorAgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            return;
        }

        CoordinatorAgent::factory()->create();
    }
}
