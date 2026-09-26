<?php

use App\Models\Membership;
use App\Models\PollingUnit;
use Database\Factories\MembershipFactory;

function duplicateRegistrationPayload(PollingUnit $unit): array
{
    return ['name' => 'New member', 'phone' => '08031234567', 'email' => 'new@example.com', 'gender' => 'female', 'ageRange' => '25-34', 'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit' => $unit->id, 'same_address' => 'yes', 'support' => 'no', 'contact_consent' => 'yes'];
}

test('registration detects equivalent legacy phone formats', function (string $route, string $stored, string $submitted) {
    $unit = PollingUnit::factory()->create();
    $existing = MembershipFactory::new()->create(['phone' => $stored]);

    $response = $this->post(route($route), array_replace(duplicateRegistrationPayload($unit), ['phone' => $submitted]));

    $response->assertSessionHasErrors(['phone' => 'This phone number is already registered, possibly in another format.']);
    $this->assertDatabaseCount('memberships', 1);
    expect($existing->fresh()->phone)->toBe($stored);
    expect($unit->fresh()->next_member_number)->toBe(1);
})->with(['cbm.register', 'nc.register'])->with([
    ['08031234567', '+2348031234567'],
    ['+2348031234567', '08031234567'],
    ['2348031234567', '0803 123 4567'],
    ['+234 (803)1234567', '0803-123-4567'],
]);

test('both registration flows store normalized contact details', function (string $route) {
    $unit = PollingUnit::factory()->create();

    $this->post(route($route), array_replace(duplicateRegistrationPayload($unit), ['phone' => ' +234 (803) 123-4567 ', 'email' => ' Member@Example.COM ']))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('memberships', ['phone' => '08031234567', 'email' => 'member@example.com']);
})->with(['cbm.register', 'nc.register']);

test('registration detects case and whitespace variants of legacy email', function (string $route) {
    $unit = PollingUnit::factory()->create();
    MembershipFactory::new()->create(['email' => ' Member@Example.COM ']);

    $this->post(route($route), array_replace(duplicateRegistrationPayload($unit), ['email' => 'member@example.com']))->assertSessionHasErrors(['email' => 'This email address is already registered.']);

    $this->assertDatabaseCount('memberships', 1);
})->with(['cbm.register', 'nc.register']);

test('editing normalizes own contact values without flagging itself', function () {
    $unit = PollingUnit::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $unit->ward->lga_id, 'phone' => '+2348031234567', 'email' => 'Member@Example.COM']);

    $this->patch(route('membership.update', $member), ['name' => $member->name, 'phone' => '0803-123-4567', 'email' => ' MEMBER@example.com ', 'gender' => 'female', 'age_range' => '25-34', 'has_voters_card' => 'no', 'lga' => $member->lga, 'support_us' => 'yes', 'want_to_be_contacted' => 'yes'])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'phone' => '08031234567', 'email' => 'member@example.com']);
});

test('editing cannot claim another members equivalent contacts', function () {
    $unit = PollingUnit::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $unit->ward->lga_id]);
    $other = MembershipFactory::new()->create(['phone' => '+2348031234567', 'email' => 'Other@Example.COM']);

    $this->patch(route('membership.update', $member), ['name' => 'Changed', 'phone' => '08031234567', 'email' => ' other@example.com ', 'gender' => 'female', 'age_range' => '25-34', 'has_voters_card' => 'no', 'lga' => $member->lga, 'support_us' => 'yes', 'want_to_be_contacted' => 'yes', 'id' => $other->id])->assertSessionHasErrors(['phone', 'email']);

    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'phone' => $member->phone, 'name' => $member->name]);
});

test('blank emails remain optional for multiple members', function () {
    $unit = PollingUnit::factory()->create();
    foreach (['08031234567', '08031234568'] as $phone) {
        $this->post(route('nc.register'), array_replace(duplicateRegistrationPayload($unit), ['phone' => $phone, 'email' => '  ']))->assertSessionHasNoErrors();
    }
    expect(Membership::whereNull('email')->count())->toBe(2);
});
