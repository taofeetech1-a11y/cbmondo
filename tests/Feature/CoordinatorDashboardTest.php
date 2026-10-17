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

it('redirects the register shortcut to the scoped voter directory', function () {
    $agent = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.register'))
        ->assertRedirect(route('voters.index'));
});

it('redirects signed in coordinators away from login to Home', function () {
    $agent = CoordinatorAgent::factory()->create();

    $this->actingAs($agent, 'coordinator')->get(route('coordinator.login'))
        ->assertRedirect(route('coordinator.dashboard'));
});
