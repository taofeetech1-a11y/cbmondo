<?php

use App\Models\CoordinatorAgent;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Voter;
use Database\Factories\MembershipFactory;

it('requires coordinator sign in for workspace pages', function (string $page) {
    $this->get(route('coordinator.'.$page))->assertRedirect(route('coordinator.login'));
})->with(['dashboard', 'register', 'contributions']);

it('does not allow staff authentication to open the coordinator workspace', function (string $page) {
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->get(route('coordinator.'.$page))->assertRedirect(route('coordinator.login'));
})->with(['dashboard', 'register', 'contributions']);

it('shows the assigned polling unit dashboard and keeps its register scoped', function () {
    $unit = PollingUnit::factory()->create();
    $otherUnit = PollingUnit::factory()->create();
    $agent = CoordinatorAgent::factory()->for(MembershipFactory::new()->state([
        'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id,
    ]), 'membership')->create();
    Voter::factory()->for($unit, 'pollingUnit')->create(['full_name' => 'Assigned unit voter']);
    Voter::factory()->for($otherUnit, 'pollingUnit')->create(['full_name' => 'Other unit voter']);
    Voter::factory()->create(['full_name' => 'Unassigned voter']);

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.dashboard'))
        ->assertOk()->assertDontSee('Other unit voter')->assertDontSee('Unassigned voter')
        ->assertSee($unit->name)->assertSee('Welcome, '.$agent->membership->name)
        ->assertSee('class="voter-private"', false)
        ->assertSee('Collected')->assertSee('Not Yet Collected')->assertSee('Consented')->assertSee('Not Consented')
        ->assertDontSee('class="panel voter-filters"', false)
        ->assertDontSee('REGISTERS UPLOADED')->assertDontSee('NOT YET UPLOADED')
        ->assertDontSee('Polling-unit upload status')->assertDontSee('Upload status applies')
        ->assertDontSee('voter-status-grid')->assertDontSee('Unit leaderboard')
        ->assertHeader('Pragma', 'no-cache')->assertHeader('X-Frame-Options', 'DENY')
        ->assertViewHas('stats', fn ($stats): bool => $stats['total'] === 1 && $stats['pending'] === 1);

    $this->get(route('coordinator.register', ['polling_unit_id' => $otherUnit->id]))
        ->assertOk()->assertDontSee('Other unit voter')
        ->assertViewHas('voters', fn ($voters): bool => $voters->total() === 1);
    $this->get(route('coordinator.register', ['q' => 'missing name']))
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
        ->assertOk()->assertViewHas('stats', ['total' => 0, 'collected' => 0, 'pending' => 0, 'consented' => 0, 'not_consented' => 0, 'consent_unknown' => 0, 'spoken_to' => 0, 'apc_members' => 0]);
});

it('requires an assigned unit before opening the register', function () {
    $agent = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.register'))
        ->assertForbidden();
});

it('redirects signed in coordinators away from login to Home', function () {
    $agent = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.login'))
        ->assertRedirect(route('coordinator.dashboard'));
});
