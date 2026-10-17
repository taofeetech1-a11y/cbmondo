<?php

use App\Models\CoordinatorAgent;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Voter;
use Database\Factories\MembershipFactory;

function pointsCoordinator(PollingUnit $unit): CoordinatorAgent
{
    return CoordinatorAgent::factory()->for(MembershipFactory::new()->state([
        'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id,
    ]), 'membership')->create();
}

it('credits one coordinator once and records who completed the voter', function () {
    $unit = PollingUnit::factory()->create();
    $first = pointsCoordinator($unit);
    $second = pointsCoordinator($unit);
    $voter = Voter::factory()->for($unit, 'pollingUnit')->create();

    $this->actingAs($first, 'coordinator')->patch(route('voters.update', $voter), [
        'phone' => 'invalid', 'phone_added_by' => $second->id,
    ])->assertSessionHasErrors('phone');
    expect($voter->fresh()->phone_added_by)->toBeNull();
    $this->patch(route('voters.update', $voter), [
        'phone' => '08012345678', 'phone_added_by' => $second->id,
    ])->assertRedirect(route('coordinator.dashboard'));
    expect($voter->fresh()->phone_added_by)->toBe($first->id);
    expect($voter->fresh()->phone_added_at)->not->toBeNull();
    $this->get(route('coordinator.dashboard'))->assertOk()
        ->assertViewHas('coordinatorPoints', 1)->assertViewHas('coordinatorRank', 1)
        ->assertDontSee($voter->full_name);
    $this->withSession(['password_hash_coordinator' => $second->getAuthPassword()])
        ->actingAs($second, 'coordinator')->patch(route('voters.update', $voter), ['phone' => '08099999999'])
        ->assertNotFound();
    $this->get(route('coordinator.dashboard'))->assertOk()->assertViewHas('coordinatorPoints', 0)
        ->assertViewHas('coordinatorRank', 2);
    expect($voter->fresh()->phone_added_by)->toBe($first->id);

    $this->actingAs(User::factory()->superAdmin()->create(), 'web')->get(route('voters.index'))
        ->assertOk()->assertSee('Phone number added by')->assertSee($first->membership->name);
});

it('keeps credit after staff corrections and does not award a second point when a number is collected again', function () {
    $unit = PollingUnit::factory()->create();
    $first = pointsCoordinator($unit);
    $second = pointsCoordinator($unit);
    $voter = Voter::factory()->for($unit, 'pollingUnit')->create([
        'phone' => '08012345678', 'phone_added_by' => $first->id, 'phone_added_at' => now()->subDay(),
    ]);
    $collectedAt = $voter->phone_added_at->toDateTimeString();
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->patch(route('voters.update', $voter), ['phone' => '08099999999', 'phone_added_by' => $second->id])
        ->assertSessionHasNoErrors();
    expect($voter->fresh()->phone_added_by)->toBe($first->id);
    $voter->update(['phone' => null]);
    auth('web')->logout();
    $this->actingAs($second, 'coordinator')->patch(route('voters.update', $voter), ['phone' => '08011111111'])
        ->assertRedirect(route('coordinator.dashboard'));
    expect($voter->fresh()->phone_added_by)->toBe($first->id);
    expect($voter->phone_added_at->toDateTimeString())->toBe($collectedAt);
    $this->get(route('coordinator.dashboard'))->assertViewHas('coordinatorPoints', 0);
});

it('limits the leaderboard to the assigned unit and shares ranks fairly without crediting legacy numbers', function () {
    $unit = PollingUnit::factory()->create();
    $first = pointsCoordinator($unit);
    $second = pointsCoordinator($unit);
    $outsideUnit = PollingUnit::factory()->create();
    $outside = pointsCoordinator($outsideUnit);
    foreach ([$first, $second] as $agent) {
        Voter::factory()->for($unit, 'pollingUnit')->create([
            'phone' => '08012345678', 'phone_added_by' => $agent->id, 'phone_added_at' => now(),
        ]);
    }
    Voter::factory()->for($unit, 'pollingUnit')->create(['phone' => '08011111111']);
    Voter::factory()->for($outsideUnit, 'pollingUnit')->create([
        'phone' => '08099999999', 'phone_added_by' => $first->id, 'phone_added_at' => now(),
    ]);
    $this->actingAs($first, 'coordinator')->get(route('coordinator.dashboard'))
        ->assertOk()->assertViewHas('coordinatorPoints', 1)->assertViewHas('coordinatorRank', 1)
        ->assertViewHas('unitLeaderboard', fn ($leaders): bool => $leaders->count() === 2)
        ->assertDontSee($outside->membership->name);
    $this->withSession(['password_hash_coordinator' => $second->getAuthPassword()])
        ->actingAs($second, 'coordinator')->get(route('coordinator.dashboard'))
        ->assertViewHas('coordinatorRank', 1);
});
