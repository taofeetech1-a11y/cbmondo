<?php

use App\Models\CoordinatorAgent;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Voter;
use Database\Factories\MembershipFactory;

it('requires coordinator sign in for workspace pages', function (string $page) {
    $this->get(route('coordinator.'.$page))->assertRedirect(route('coordinator.login'));
})->with(['dashboard', 'register']);

it('does not allow staff authentication to open the coordinator workspace', function (string $page) {
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->get(route('coordinator.'.$page))->assertRedirect(route('coordinator.login'));
})->with(['dashboard', 'register']);

it('shows the private voter directory for only the coordinators polling unit on the dashboard', function () {
    $unit = PollingUnit::factory()->create();
    $otherUnit = PollingUnit::factory()->create();
    $agent = CoordinatorAgent::factory()->for(MembershipFactory::new()->state([
        'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id,
    ]), 'membership')->create();
    Voter::factory()->for($unit, 'pollingUnit')->create(['full_name' => 'Assigned unit voter']);
    Voter::factory()->for($otherUnit, 'pollingUnit')->create(['full_name' => 'Other unit voter']);
    Voter::factory()->create(['full_name' => 'Unassigned voter']);

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.dashboard'))
        ->assertOk()->assertSee('Assigned unit voter')->assertDontSee('Other unit voter')->assertDontSee('Unassigned voter')
        ->assertSee($unit->name)->assertSee('Welcome, '.$agent->membership->name)
        ->assertSee('Your polling unit workspace')->assertSee('class="voter-private"', false)
        ->assertSee('PHONE NUMBERS ADDED')->assertSee('STILL TO ADD')
        ->assertDontSee('class="panel voter-filters"', false)
        ->assertDontSee('REGISTERS UPLOADED')->assertDontSee('NOT YET UPLOADED')
        ->assertDontSee('Polling-unit upload status')->assertDontSee('Upload status applies')
        ->assertDontSee('voter-status-grid')->assertSee('Voters awaiting phone numbers')
        ->assertHeader('Pragma', 'no-cache')->assertHeader('X-Frame-Options', 'DENY')
        ->assertViewHas('voters', fn ($voters): bool => $voters->total() === 1);

    $this->get(route('coordinator.dashboard', ['polling_unit_id' => $otherUnit->id]))
        ->assertOk()->assertDontSee('Other unit voter')
        ->assertViewHas('voters', fn ($voters): bool => $voters->total() === 0);
    $this->get(route('coordinator.dashboard', ['q' => 'missing name']))
        ->assertOk()->assertViewHas('voters', fn ($voters): bool => $voters->total() === 0);
});

it('does not expose voters on the dashboard when no unit is assigned', function () {
    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('coordinator.dashboard'))->assertForbidden();
});

it('shows zero phone progress for a polling unit with no voters', function () {
    $unit = PollingUnit::factory()->create();
    $agent = CoordinatorAgent::factory()->for(MembershipFactory::new()->state([
        'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id,
    ]), 'membership')->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.dashboard'))
        ->assertOk()->assertViewHas('phoneProgress', ['added' => 0, 'missing' => 0, 'percent' => 0]);
});

it('redirects the register shortcut to the scoped voter directory', function () {
    $agent = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.register'))
        ->assertRedirect(route('coordinator.dashboard'));
});

it('redirects signed in coordinators away from login to Home', function () {
    $agent = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.login'))
        ->assertRedirect(route('coordinator.dashboard'));
});
