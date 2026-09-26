<?php

use App\Models\Membership;
use App\Models\PollingUnit;
use Database\Factories\MembershipFactory;

function registrationPayload(PollingUnit $unit): array
{
    return ['name' => 'Test Member', 'phone' => '08031234567', 'gender' => 'female', 'ageRange' => '25-34', 'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit' => $unit->id, 'same_address' => 'yes', 'support' => 'no', 'contact_consent' => 'yes'];
}

test('registration validates and stores members in both flows', function (string $route, bool $card) {
    $unit = PollingUnit::factory()->create(['next_member_number' => 7]);
    $payload = registrationPayload($unit);
    $payload['has_voters_card'] = $card ? 'no' : 'yes';

    $response = $this->from('/')->post(route($route), $payload);

    $response->assertRedirect('/')->assertSessionHasNoErrors()->assertSessionHas($card ? 'data' : 'dataTwo');
    $this->assertDatabaseCount('memberships', 1);
    $this->assertDatabaseHas('memberships', ['name' => 'Test Member', 'phone' => '08031234567', 'email' => null, 'lga' => $unit->ward->lga_id, 'ward' => $card ? $unit->ward_id : null, 'pu' => $card ? $unit->id : null, 'same_address' => $card ? 'yes' : null, 'cbm_delimitation_code' => $card ? $unit->delimitation_code.'/007' : null]);
    expect($unit->fresh()->next_member_number)->toBe($card ? 8 : 7);
})->with([['cbm.register', true], ['nc.register', false]]);

test('registration rejects invalid input without creating a member or consuming a number', function (string $route, array $changes, string $field) {
    $unit = PollingUnit::factory()->create();

    $response = $this->from('/')->post(route($route), array_replace(registrationPayload($unit), $changes));

    $response->assertRedirect('/')->assertSessionHasErrors($field);
    $this->assertDatabaseCount('memberships', 0);
    expect($unit->fresh()->next_member_number)->toBe(1);
})->with(['cbm.register', 'nc.register'])->with([
    'empty name' => [['name' => ''], 'name'],
    'long name' => [['name' => str_repeat('a', 31)], 'name'],
    'invalid phone' => [['phone' => '1234567890'], 'phone'],
    'phone array' => [['phone' => ['08031234567']], 'phone'],
    'invalid email' => [['email' => 'invalid'], 'email'],
    'long email' => [['email' => str_repeat('a', 40).'@example.com'], 'email'],
    'gender' => [['gender' => 'invalid'], 'gender'],
    'age' => [['ageRange' => 'invalid'], 'ageRange'],
    'lga array' => [['lga' => ['1']], 'lga'],
    'lga missing' => [['lga' => 99999], 'lga'],
    'consent' => [['contact_consent' => 'maybe'], 'contact_consent'],
    'support' => [['support' => 'maybe'], 'support'],
]);

test('registration rejects mismatched electoral locations', function () {
    $unit = PollingUnit::factory()->create();
    $other = PollingUnit::factory()->create();
    $payload = array_replace(registrationPayload($unit), ['lga' => $other->ward->lga_id, 'polling_unit' => $other->id]);

    $this->post(route('cbm.register'), $payload)->assertSessionHasErrors(['ward', 'polling_unit']);

    $this->assertDatabaseCount('memberships', 0);
    expect($unit->fresh()->next_member_number)->toBe(1);
    expect($other->fresh()->next_member_number)->toBe(1);
});

test('voter registration requires electoral details', function () {
    $unit = PollingUnit::factory()->create();
    $payload = array_replace(registrationPayload($unit), ['ward' => null, 'polling_unit' => null, 'same_address' => null]);
    $this->post(route('cbm.register'), $payload)->assertSessionHasErrors(['ward', 'polling_unit', 'same_address']);
    $this->assertDatabaseCount('memberships', 0);
});

test('registration and editing accept supported Nigerian phone formats', function (string $phone, string $normalized) {
    $unit = PollingUnit::factory()->create();
    $this->post(route('nc.register'), array_replace(registrationPayload($unit), ['phone' => $phone]))->assertSessionHasNoErrors();
    $member = Membership::firstOrFail();
    $this->patch(route('membership.update', $member), ['name' => $member->name, 'phone' => $phone, 'gender' => 'female', 'age_range' => '25-34', 'has_voters_card' => 'no', 'lga' => $unit->ward->lga_id, 'support_us' => 'no', 'want_to_be_contacted' => 'yes'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'phone' => $normalized]);
})->with([['08031234567', '08031234567'], ['+2348031234567', '08031234567'], ['2348031234567', '08031234567'], ['012345678', '012345678']]);

test('both registration forms report existing phone and email', function (string $route) {
    $unit = PollingUnit::factory()->create();
    MembershipFactory::new()->create(['phone' => '08031234567', 'email' => 'member@example.com']);
    $this->post(route($route), array_replace(registrationPayload($unit), ['email' => 'member@example.com']))->assertSessionHasErrors(['phone', 'email']);
    $this->assertDatabaseCount('memberships', 1);
})->with(['cbm.register', 'nc.register']);

test('unused legacy submission endpoint returns not found', function () {
    $this->post('/new/submit', [])->assertNotFound();
});

test('phone widening preserves member data and refuses a destructive rollback', function () {
    $member = MembershipFactory::new()->create(['phone' => '+2348031234567']);
    $migration = require database_path('migrations/2026_09_25_213931_widen_membership_phone_column.php');

    $migration->up();

    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'phone' => '+2348031234567']);
    expect(fn () => $migration->down())->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'phone' => '+2348031234567']);
});
