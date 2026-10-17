<?php

use App\Models\User;
use App\Models\Voter;

it('marks voter directory and edit responses private and non-cacheable', function () {
    $agent = User::factory()->superAdmin()->create();
    $voter = Voter::factory()->create();
    $this->actingAs($agent, 'web');

    foreach ([route('voters.index'), route('voters.edit', $voter)] as $url) {
        $response = $this->get($url)->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('Confidential voter information')
            ->assertSee('Account #'.$agent->id)
            ->assertSee('class="voter-private"', false);

        expect($response->headers->get('Cache-Control'))->toContain('no-store', 'private', 'no-cache');
    }
});

it('keeps the privacy overlay off the import form', function () {
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->get('/voters/import')->assertOk()->assertDontSee('class="voter-private"', false);
});
