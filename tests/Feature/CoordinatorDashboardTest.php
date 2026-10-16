<?php

use App\Models\CoordinatorAgent;
use App\Models\User;

it('requires coordinator sign in for workspace pages', function (string $page) {
    $this->get(route('coordinator.'.$page))->assertRedirect(route('coordinator.login'));
})->with(['dashboard', 'register']);

it('does not allow staff authentication to open the coordinator workspace', function (string $page) {
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->get(route('coordinator.'.$page))->assertRedirect(route('coordinator.login'));
})->with(['dashboard', 'register']);

it('shows only the signed in membership and handles an unassigned unit', function () {
    $agent = CoordinatorAgent::factory()->create();
    $other = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.dashboard', ['membership_id' => $other->membership_id]))
        ->assertSee($agent->membership->name)
        ->assertViewHas('member', fn ($member) => $member->is($agent->membership))
        ->assertSee('Polling unit not assigned')
        ->assertSee('Awaiting voter register')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
});

it('shows an honest register empty state', function () {
    $agent = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.register'))
        ->assertSee('No voter records or photos have been imported.')
        ->assertSee(route('coordinator.dashboard'));
});

it('redirects signed in coordinators away from login to Home', function () {
    $agent = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.login'))
        ->assertRedirect(route('coordinator.dashboard'));
});
