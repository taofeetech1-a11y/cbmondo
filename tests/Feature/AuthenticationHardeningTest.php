<?php

use App\Models\CoordinatorAgent;
use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Voter;
use App\Notifications\VerifyEmailChange;
use Database\Factories\MembershipFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

function secureCoordinator(array $attributes = []): CoordinatorAgent
{
    $unit = PollingUnit::factory()->create();

    return CoordinatorAgent::factory()->for(MembershipFactory::new()->state([
        'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id,
    ]), 'membership')->create($attributes);
}

it('requires a unique unexpired temporary password and a password change before voter access', function () {
    $agent = secureCoordinator(['password_changed_at' => null, 'temporary_password_expires_at' => now()->addDay()]);
    $voter = Voter::factory()->create(['polling_unit_id' => $agent->membership->pu]);
    $this->post(route('coordinator.login'), ['cbm_id' => $agent->membership->cbm_id, 'password' => 'temporary-password-123'])
        ->assertRedirect(route('coordinator.profile'));
    foreach (['coordinator.dashboard', 'voters.index'] as $route) {
        $this->get(route($route))->assertRedirect(route('coordinator.profile'));
    }
    $this->get(route('voters.edit', $voter))->assertRedirect(route('coordinator.profile'));
    $this->patch(route('voters.update', $voter), ['phone' => '08012345678'])->assertRedirect(route('coordinator.profile'));
    expect($voter->fresh()->phone)->toBeNull();
    $this->put(route('coordinator.password'), [
        'current_password' => 'temporary-password-123', 'password' => 'my-own-secure-password', 'password_confirmation' => 'my-own-secure-password',
    ])->assertSessionHasNoErrors();
    $this->get(route('coordinator.dashboard'))->assertOk();
    expect($agent->fresh()->temporary_password_expires_at)->toBeNull();
});

it('rejects expired legacy and suspended coordinator credentials', function (array $attributes) {
    $agent = secureCoordinator($attributes);
    $this->post(route('coordinator.login'), ['cbm_id' => $agent->membership->cbm_id, 'password' => 'temporary-password-123'])
        ->assertSessionHasErrors('cbm_id');
    $this->assertGuest('coordinator');
})->with([
    'expired temporary password' => [['password_changed_at' => null, 'temporary_password_expires_at' => now()->subMinute()]],
    'legacy shared password' => [['password_changed_at' => null]],
    'suspended account' => [['is_active' => false]],
]);

it('rejects revoked sessions across every voter entry point', function () {
    $agent = secureCoordinator();
    $voter = Voter::factory()->create(['polling_unit_id' => $agent->membership->pu]);
    $agent->increment('session_version');
    foreach ([route('coordinator.dashboard'), route('voters.index'), route('voters.edit', $voter), route('voters.photo', $voter)] as $url) {
        $this->actingAs($agent->fresh(), 'coordinator')->withSession(['account_version_coordinator' => 1])
            ->get($url)->assertRedirect(route('coordinator.login'));
    }
    $this->actingAs($agent->fresh(), 'coordinator')->withSession(['account_version_coordinator' => 1])
        ->patch(route('voters.update', $voter), ['phone' => '08012345678'])->assertRedirect(route('coordinator.login'));
    expect($voter->fresh()->phone)->toBeNull();
});

it('invalidates old staff sessions on password reset and permits a new sign in', function () {
    $user = User::factory()->superAdmin()->create();
    $token = Password::createToken($user);
    $this->post(route('password.store'), ['email' => $user->email, 'token' => $token,
        'password' => 'a-new-private-password', 'password_confirmation' => 'a-new-private-password',
    ])->assertSessionHasNoErrors();
    expect($user->fresh()->session_version)->toBe(2);
    $this->actingAs($user->fresh(), 'web')->withSession(['account_version_web' => 1])
        ->get(route('membership.index'))->assertRedirect(route('login'));
    $this->post(route('login'), ['email' => $user->email, 'password' => 'a-new-private-password'])
        ->assertSessionHasNoErrors();
    $this->get(route('membership.index'))->assertOk();
});

it('keeps the current staff session after a password change and rejects older sessions', function () {
    $user = User::factory()->superAdmin()->create();
    $this->actingAs($user, 'web')->put(route('password.update'), [
        'current_password' => 'password', 'password' => 'a-new-private-password', 'password_confirmation' => 'a-new-private-password',
    ])->assertSessionHasNoErrors();
    $this->get(route('membership.index'))->assertOk();
    $this->withSession(['account_version_web' => 1])->get(route('membership.index'))->assertRedirect(route('login'));
});

it('rejects a staff session with no security version and retains no privileged legacy session', function () {
    $user = User::factory()->superAdmin()->create();
    $this->actingAs($user, 'web');
    session()->forget('account_version_web');
    $this->get(route('membership.index'))->assertRedirect(route('login'));
});

it('restores a legitimate remembered staff login after its ordinary session ends', function () {
    $user = User::factory()->superAdmin()->create();
    $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'password', 'remember' => true]);
    $cookieName = auth('web')->getRecallerName();
    $cookie = collect($response->headers->getCookies())->first(fn ($cookie): bool => $cookie->getName() === $cookieName);
    expect($cookie)->not->toBeNull();
    $this->flushSession();
    app('auth')->forgetGuards();
    $this->withUnencryptedCookie($cookieName, $cookie->getValue())->get(route('membership.index'))->assertOk();
});

it('generates different one-time credentials and shows them only on the first member page response', function () {
    $first = MembershipFactory::new()->create();
    $second = MembershipFactory::new()->create();
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')->post(route('coordinator-agents.store', $first))
        ->assertSessionHasNoErrors();
    $password = session('coordinator_credentials.password');
    $this->get(route('membership.show', $first))->assertSee($password)->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $this->get(route('membership.show', $first))->assertDontSee($password);
    $this->post(route('coordinator-agents.store', $second))->assertSessionHasNoErrors();
    expect(session('coordinator_credentials.password'))->not->toBe($password);
});

it('rejects tampered expired replayed and other-account email confirmation links', function () {
    Notification::fake();
    $user = User::factory()->superAdmin()->create();
    $this->actingAs($user, 'web')->patch(route('profile.update'), [
        'name' => $user->name, 'email' => 'verified-new@example.test', 'current_password' => 'password',
    ])->assertSessionHasNoErrors();
    $link = null;
    Notification::assertSentOnDemand(VerifyEmailChange::class, function ($notification) use (&$link): bool {
        $link = $notification->verificationUrl;

        return true;
    });
    $this->get(str_replace('verified-new', 'tampered', $link))->assertForbidden();
    $this->travel(31)->minutes();
    $this->get($link)->assertForbidden();
    $this->travelBack();
    $other = User::factory()->superAdmin()->create();
    $this->actingAs($other, 'web')->get($link)->assertForbidden();
    $this->actingAs($user->fresh(), 'web')->get($link)->assertRedirect(route('profile.edit'));
    $this->get($link)->assertForbidden();
    expect($user->fresh()->email)->toBe('verified-new@example.test');
});

it('blocks inactive staff password changes and requires the password for email changes', function () {
    $inactive = User::factory()->create(['is_active' => false]);
    $this->actingAs($inactive, 'web')->put(route('password.update'), [
        'current_password' => 'password', 'password' => 'a-new-private-password', 'password_confirmation' => 'a-new-private-password',
    ])->assertForbidden();
    expect(Hash::check('password', $inactive->fresh()->password))->toBeTrue();
    $active = User::factory()->superAdmin()->create();
    $this->actingAs($active, 'web')->patch(route('profile.update'), ['name' => $active->name, 'email' => 'replacement@example.test'])
        ->assertSessionHasErrors('current_password');
    expect($active->fresh()->email)->not->toBe('replacement@example.test');
});

it('uses uniform recovery responses and throttles requests', function () {
    Notification::fake();
    $user = User::factory()->create();
    $expected = 'If an account exists for this email, password reset instructions have been sent.';
    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status', $expected);
    $this->post(route('password.email'), ['email' => 'unknown@example.test'])->assertSessionHas('status', $expected);
    for ($i = 0; $i < 3; $i++) {
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])->assertRedirect();
    }
    $this->post(route('password.email'), ['email' => 'unknown@example.test'])->assertStatus(429);
});

it('throttles repeated coordinator current-password guesses', function () {
    $agent = secureCoordinator();
    $this->actingAs($agent, 'coordinator');
    $data = ['current_password' => 'wrong-password', 'password' => 'a-new-private-password', 'password_confirmation' => 'a-new-private-password'];
    for ($i = 0; $i < 5; $i++) {
        $this->put(route('coordinator.password'), $data)->assertSessionHasErrorsIn('password', 'current_password');
    }
    $this->put(route('coordinator.password'), $data)->assertStatus(429);
});

it('lets only super admins suspend and reset coordinator access while preserving completion credit', function () {
    $agent = secureCoordinator();
    $voter = Voter::factory()->create(['polling_unit_id' => $agent->membership->pu, 'phone_added_by' => $agent->id, 'phone_added_at' => now()]);
    $this->actingAs(User::factory()->create(), 'web')->patch(route('coordinator-agents.update', $agent->membership), ['action' => 'suspend'])
        ->assertForbidden();
    $this->actingAs(User::factory()->superAdmin()->create(), 'web')
        ->patch(route('coordinator-agents.update', $agent->membership), ['action' => 'suspend'])->assertSessionHasNoErrors();
    expect($agent->fresh()->is_active)->toBeFalse();
    expect($agent->fresh()->session_version)->toBe(2);
    $this->patch(route('coordinator-agents.update', $agent->membership), ['action' => 'activate'])->assertSessionHasNoErrors();
    $this->patch(route('coordinator-agents.update', $agent->membership), ['action' => 'reset-password'])->assertSessionHasNoErrors();
    expect(Hash::check(session('coordinator_credentials.password'), $agent->fresh()->password))->toBeTrue();
    expect($agent->fresh()->password_changed_at)->toBeNull();
    expect($voter->fresh()->phone_added_by)->toBe($agent->id);
});
