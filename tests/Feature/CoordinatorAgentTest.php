<?php

use App\Models\CoordinatorAgent;
use App\Models\User;
use Database\Factories\MembershipFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

it('allows only super admins to create accounts for existing members', function () {
    config(['coordinator.default_password' => 'shared-temporary-password']);
    $member = MembershipFactory::new()->create();
    $this->post(route('coordinator-agents.store', $member))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(), 'web')->post(route('coordinator-agents.store', $member))->assertForbidden();
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')->get(route('membership.show', $member))->assertOk()->assertSee('Create coordinator-agent');
    $this->post(route('coordinator-agents.store', $member))->assertSessionHasNoErrors()->assertRedirect(route('membership.show', $member));
    $agent = CoordinatorAgent::sole();
    expect($agent->membership_id)->toBe($member->id);
    expect(Hash::check('shared-temporary-password', $agent->password))->toBeTrue();
    $this->get(route('membership.show', $member))->assertSee('This member has a coordinator-agent account');
    $this->post(route('coordinator-agents.store', $member))->assertSessionHasErrors('coordinator');
    $this->post(route('coordinator-agents.store', 999999))->assertNotFound();
    $this->assertDatabaseCount('coordinator_agents', 1);
});

it('refuses account creation until a shared default is configured', function () {
    config(['coordinator.default_password' => null]);
    $member = MembershipFactory::new()->create();
    $this->actingAs(User::factory()->superAdmin()->create())->post(route('coordinator-agents.store', $member))->assertSessionHasErrors('coordinator');
    $this->assertDatabaseCount('coordinator_agents', 0);
});

it('signs in with a CBM ID and keeps coordinator access separate from staff access', function () {
    $agent = CoordinatorAgent::factory()->create();
    $this->get(route('coordinator.profile'))->assertRedirect(route('coordinator.login'));
    $this->get(route('coordinator.login'))->assertOk()->assertSee('CBM ID');
    $this->post(route('coordinator.login'), ['cbm_id' => strtolower($agent->membership->cbm_id), 'password' => 'temporary-password-123'])->assertRedirect(route('coordinator.dashboard'));
    $this->assertAuthenticatedAs($agent, 'coordinator');
    $this->assertGuest('web');
    $this->get(route('coordinator.profile'))->assertOk()->assertSee($agent->membership->name)->assertSee('Change password');
    $this->get('/membership')->assertRedirect(route('login'));
    $this->get('/staff-users')->assertRedirect(route('login'));
    $this->post(route('coordinator.logout'))->assertRedirect(route('coordinator.login'));
    $this->assertGuest('coordinator');
});

it('does not grant coordinator access to staff or unappointed members', function () {
    $member = MembershipFactory::new()->create();
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')->get(route('coordinator.profile'))->assertRedirect(route('coordinator.login'));
    $this->post(route('coordinator.login'), ['cbm_id' => $member->cbm_id, 'password' => 'temporary-password-123'])->assertSessionHasErrors('cbm_id');
    $this->assertGuest('coordinator');
});

it('rate limits repeated coordinator login attempts', function () {
    $agent = CoordinatorAgent::factory()->create();
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('coordinator.login'), ['cbm_id' => $agent->membership->cbm_id, 'password' => 'wrong'])->assertSessionHasErrors('cbm_id');
    }
    $this->post(route('coordinator.login'), ['cbm_id' => $agent->membership->cbm_id, 'password' => 'temporary-password-123'])->assertSessionHasErrors('cbm_id');
    $this->assertGuest('coordinator');
});

it('changes only the signed in coordinators password and rejects the old password', function () {
    $agent = CoordinatorAgent::factory()->create();
    $other = CoordinatorAgent::factory()->create();
    $this->actingAs($agent, 'coordinator')->put(route('coordinator.password'), ['current_password' => 'temporary-password-123', 'password' => 'a-new-private-password', 'password_confirmation' => 'a-new-private-password', 'membership_id' => $other->membership_id])->assertSessionHasNoErrors();
    expect(Hash::check('a-new-private-password', $agent->fresh()->password))->toBeTrue();
    expect($agent->fresh()->password_changed_at)->not->toBeNull();
    expect(Hash::check('temporary-password-123', $other->fresh()->password))->toBeTrue();
    $this->get(route('coordinator.profile'))->assertOk()->assertDontSee('You are using the shared default password.');
    $this->post(route('coordinator.logout'));
    $this->post(route('coordinator.login'), ['cbm_id' => $agent->membership->cbm_id, 'password' => 'temporary-password-123'])->assertSessionHasErrors('cbm_id');
    $this->post(route('coordinator.login'), ['cbm_id' => $agent->membership->cbm_id, 'password' => 'a-new-private-password'])->assertRedirect(route('coordinator.dashboard'));
});

it('rejects invalid password changes', function (array $overrides) {
    $agent = CoordinatorAgent::factory()->create();
    $this->actingAs($agent, 'coordinator')->put(route('coordinator.password'), [...['current_password' => 'temporary-password-123', 'password' => 'a-new-private-password', 'password_confirmation' => 'a-new-private-password'], ...$overrides])->assertSessionHasErrorsIn('password');
    expect(Hash::check('temporary-password-123', $agent->fresh()->password))->toBeTrue();
})->with([
    'wrong current password' => [['current_password' => 'wrong']],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short']],
    'confirmation mismatch' => [['password_confirmation' => 'mismatch']],
    'unchanged password' => [['password' => 'temporary-password-123', 'password_confirmation' => 'temporary-password-123']],
]);

it('stores photos privately and replaces only the current coordinators photo', function () {
    Storage::fake('local');
    $agent = CoordinatorAgent::factory()->create();
    $other = CoordinatorAgent::factory()->create();
    $this->actingAs($agent, 'coordinator')->post(route('coordinator.photo.update'), ['photo' => UploadedFile::fake()->image('photo.png'), 'membership_id' => $other->membership_id])->assertSessionHasNoErrors();
    $previous = $agent->fresh()->photo_path;
    Storage::disk('local')->assertExists($previous);
    expect($other->fresh()->photo_path)->toBeNull();
    $this->get(route('coordinator.photo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $this->post(route('coordinator.photo.update'), ['photo' => UploadedFile::fake()->image('new.jpg')])->assertSessionHasNoErrors();
    Storage::disk('local')->assertMissing($previous);
    $this->flushSession();
    $this->actingAs($other, 'coordinator')->get(route('coordinator.photo'))->assertNotFound();
});

it('rejects unsafe or oversized photo uploads', function (string $kind) {
    Storage::fake('local');
    $agent = CoordinatorAgent::factory()->create();
    $file = match ($kind) {
        'svg' => UploadedFile::fake()->create('photo.svg', 10, 'image/svg+xml'),
        'large' => UploadedFile::fake()->image('photo.png')->size(2049),
        'text' => UploadedFile::fake()->create('photo.txt', 10, 'text/plain'),
    };
    $this->actingAs($agent, 'coordinator')->post(route('coordinator.photo.update'), ['photo' => $file])->assertSessionHasErrorsIn('photo', 'photo');
    expect($agent->fresh()->photo_path)->toBeNull();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with(['svg', 'large', 'text']);
