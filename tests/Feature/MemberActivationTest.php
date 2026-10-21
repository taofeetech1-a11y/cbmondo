<?php

use App\Models\MemberAccount;
use App\Models\User;
use Database\Factories\MembershipFactory;
use Illuminate\Support\Facades\Hash;

test('members activate with a normalized phone and name then sign in to their own profile', function () {
    $member = MembershipFactory::new()->create(['name' => 'Ada Bello', 'phone' => '+234 (803) 123-4567']);
    $other = MembershipFactory::new()->create(['name' => 'Private Other Name']);
    $this->get(route('member.activate'))->assertOk();
    $this->post(route('member.activate.phone'), ['phone' => '08031234567'])->assertRedirect(route('member.activate'));
    $this->get(route('member.activate'))->assertOk()->assertDontSee('Ada Bello');
    $this->post(route('member.activate.name'), ['name' => ' ADA   BELLO '])->assertRedirect(route('member.activate'));
    $this->get(route('member.activate'))->assertSee('Create your password');
    $this->post(route('member.activate.password'), ['password' => 'MemberPassword123!', 'password_confirmation' => 'MemberPassword123!', 'is_active' => false, 'membership_id' => $other->id])->assertRedirect(route('member.login'));
    $account = MemberAccount::sole();
    expect($account->membership_id)->toBe($member->id);
    expect($account->phone)->toBe('08031234567');
    expect(Hash::check('MemberPassword123!', $account->password))->toBeTrue();
    $this->assertGuest('member');
    $this->assertDatabaseCount('users', 0);
    $this->post(route('member.login'), ['phone' => '+2348031234567', 'password' => 'MemberPassword123!'])->assertRedirect(route('member.profile'));
    $this->get(route('member.profile'))->assertOk()->assertSee('Ada Bello')->assertDontSee($other->name)->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    $this->get(route('membership.show', $other))->assertRedirect(route('login'));
    $this->get(route('voters.index'))->assertRedirect(route('login'));
    $this->get(route('coordinator.dashboard'))->assertRedirect(route('coordinator.login'));
    $this->post(route('member.logout'))->assertRedirect(route('member.login'));
    $this->get(route('member.profile'))->assertRedirect(route('member.login'));
});

test('matching names allow selection or skipping and flag records without deleting them', function (bool $skip) {
    $original = MembershipFactory::new()->create(['name' => 'Ada Bello', 'phone' => '08031234567']);
    $duplicate = MembershipFactory::new()->create(['name' => 'ada bello', 'phone' => '08031234568']);
    $unrelated = MembershipFactory::new()->create(['name' => 'Different Person', 'phone' => '08031234569']);
    $this->post(route('member.activate.phone'), ['phone' => $original->phone]);
    $this->post(route('member.activate.name'), ['name' => $original->name]);
    $this->get(route('member.activate'))->assertOk()->assertSee($duplicate->cbm_id)->assertDontSee($duplicate->phone)->assertDontSee($unrelated->name);
    $this->post(route('member.activate.choose'), ['membership_id' => (string) $unrelated->id])->assertSessionHasErrors('membership_id');
    $this->post(route('member.activate.choose'), ['membership_id' => $skip ? 'skip' : (string) $duplicate->id])->assertRedirect(route('member.activate'));
    $this->post(route('member.activate.password'), ['password' => 'MemberPassword123!', 'password_confirmation' => 'MemberPassword123!'])->assertRedirect(route('member.login'));
    $this->assertDatabaseHas('member_accounts', ['membership_id' => $skip ? $original->id : $duplicate->id, 'phone' => $original->phone]);
    $this->assertDatabaseHas('membership_duplicate_flags', ['membership_id' => $skip ? $duplicate->id : $original->id, 'selection_skipped' => $skip, 'status' => 'pending']);
    $this->assertDatabaseCount('memberships', 3);
})->with([true, false]);

test('unknown phones invalid phones and wrong names cannot activate memberships', function () {
    $member = MembershipFactory::new()->create(['name' => 'Ada Bello', 'phone' => '08031234567']);
    $this->post(route('member.activate.phone'), ['phone' => '123'])->assertSessionHasErrors('phone');
    $this->post(route('member.activate.phone'), ['phone' => '08031234568'])->assertSessionHasErrors('phone');
    $this->post(route('member.activate.phone'), ['phone' => $member->phone]);
    $this->post(route('member.activate.name'), ['name' => 'Someone Else'])->assertSessionHasErrors('name');
    $this->assertDatabaseCount('member_accounts', 0);
});

test('activation cannot skip steps or reuse expired sessions', function () {
    $member = MembershipFactory::new()->create(['phone' => '08031234567']);
    $password = ['password' => 'MemberPassword123!', 'password_confirmation' => 'MemberPassword123!'];
    $this->post(route('member.activate.password'), $password)->assertSessionHasErrors('phone');
    $this->post(route('member.activate.phone'), ['phone' => $member->phone]);
    $this->travel(16)->minutes();
    $this->post(route('member.activate.name'), ['name' => $member->name])->assertSessionHasErrors('phone');
    $this->assertDatabaseCount('member_accounts', 0);
});

test('password creation requires confirmation and a strong password', function () {
    $member = MembershipFactory::new()->create(['phone' => '08031234567']);
    $this->post(route('member.activate.phone'), ['phone' => $member->phone]);
    $this->post(route('member.activate.name'), ['name' => $member->name]);
    $this->post(route('member.activate.password'), ['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
    $this->post(route('member.activate.password'), ['password' => 'MemberPassword123!', 'password_confirmation' => 'DifferentPassword123!'])->assertSessionHasErrors('password');
    $this->assertDatabaseCount('member_accounts', 0);
});

test('an existing account cannot be activated again or have its password replaced', function () {
    $account = MemberAccount::factory()->create();
    $this->post(route('member.activate.phone'), ['phone' => $account->phone])->assertSessionHasErrors('phone');
    $this->post(route('member.activate.phone'), ['phone' => $account->membership->phone])->assertSessionHasErrors('phone');
    expect(Hash::check('MemberPassword123!', $account->fresh()->password))->toBeTrue();
});

test('concurrent claims and changed membership details cannot complete activation', function (bool $claimed) {
    $member = MembershipFactory::new()->create(['phone' => '08031234567']);
    $this->post(route('member.activate.phone'), ['phone' => $member->phone]);
    $this->post(route('member.activate.name'), ['name' => $member->name]);
    if ($claimed) {
        MemberAccount::factory()->create(['membership_id' => $member->id, 'phone' => $member->phone]);
    } else {
        $member->update(['phone' => '08031234568']);
    }
    $this->post(route('member.activate.password'), ['password' => 'DifferentPassword123!', 'password_confirmation' => 'DifferentPassword123!'])->assertSessionHasErrors('password');
    $this->assertDatabaseCount('member_accounts', $claimed ? 1 : 0);
})->with([true, false]);

test('inactive members and incorrect passwords cannot sign in', function () {
    $account = MemberAccount::factory()->create();
    $this->post(route('member.login'), ['phone' => $account->phone, 'password' => 'wrong-password'])->assertSessionHasErrors('phone');
    $account->forceFill(['is_active' => false])->save();
    $this->post(route('member.login'), ['phone' => $account->phone, 'password' => 'MemberPassword123!'])->assertSessionHasErrors('phone');
    $this->assertGuest('member');
    $this->actingAs($account, 'member')->get(route('member.profile'))->assertForbidden();
});

test('login and activation attempts are rate limited', function () {
    $this->freezeTime();
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('member.login'), ['phone' => '08031234567', 'password' => 'wrong-password']);
    }
    $this->post(route('member.login'), ['phone' => '08031234567', 'password' => 'wrong-password'])->assertSessionHasErrors(['phone' => 'Too many attempts. Please try again in 60 seconds.']);
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('member.activate.phone'), ['phone' => '123']);
    }
    $this->post(route('member.activate.phone'), ['phone' => '123'])->assertTooManyRequests();
});

test('only super admins can inspect possible duplicates', function () {
    $this->get(route('membership.duplicate-flags'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('membership.duplicate-flags'))->assertForbidden();
    $this->actingAs(User::factory()->superAdmin()->create())->get(route('membership.duplicate-flags'))->assertOk();
});

test('ambiguous legacy phone numbers require support rather than choosing the first record', function () {
    MembershipFactory::new()->create(['phone' => '08031234567']);
    MembershipFactory::new()->create(['phone' => '+2348031234567']);
    $this->post(route('member.activate.phone'), ['phone' => '08031234567'])->assertSessionHasErrors('phone');
    $this->assertDatabaseCount('member_accounts', 0);
});

test('selecting an already activated duplicate never replaces that account', function () {
    $source = MembershipFactory::new()->create(['name' => 'Ada Bello', 'phone' => '08031234567']);
    $existing = MemberAccount::factory()->for(MembershipFactory::new()->state(['name' => 'Ada Bello']), 'membership')->create();
    $this->post(route('member.activate.phone'), ['phone' => $source->phone]);
    $this->post(route('member.activate.name'), ['name' => $source->name]);
    $this->post(route('member.activate.choose'), ['membership_id' => (string) $existing->membership_id]);
    $this->post(route('member.activate.password'), ['password' => 'DifferentPassword123!', 'password_confirmation' => 'DifferentPassword123!'])->assertSessionHasErrors('password');
    $this->assertDatabaseCount('member_accounts', 1);
    $this->assertDatabaseCount('membership_duplicate_flags', 0);
    expect(Hash::check('MemberPassword123!', $existing->fresh()->password))->toBeTrue();
});

test('restarting activation removes the previous claim', function () {
    $member = MembershipFactory::new()->create();
    $this->post(route('member.activate.phone'), ['phone' => $member->phone]);
    $this->post(route('member.activate.restart'))->assertRedirect(route('member.activate'));
    $this->get(route('member.activate'))->assertSee('Find my membership');
    $this->post(route('member.activate.name'), ['name' => $member->name])->assertSessionHasErrors('phone');
});

test('profile output escapes stored member names', function () {
    $account = MemberAccount::factory()->for(MembershipFactory::new()->state(['name' => '<script>alert(1)</script>']), 'membership')->create();
    $this->actingAs($account, 'member')->get(route('member.profile'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->get(route('member.login'))->assertRedirect(route('member.profile'));
});
