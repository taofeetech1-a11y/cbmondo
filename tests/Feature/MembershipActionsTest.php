<?php

use App\Models\Lga;
use App\Models\Membership;
use App\Models\PollingUnit;
use Database\Factories\MembershipFactory;

function memberUpdatePayload(Membership $member): array
{
    return [
        'name' => $member->name, 'phone' => $member->phone, 'email' => $member->email,
        'gender' => $member->gender, 'age_range' => $member->age_range,
        'has_voters_card' => $member->ward !== null ? 'yes' : 'no',
        'lga' => $member->lga, 'ward' => $member->ward, 'pu' => $member->pu,
        'support_us' => 'yes', 'want_to_be_contacted' => 'yes', 'same_address' => 'yes',
    ];
}

test('each member has view and update links that retain dashboard filters', function () {
    $member = MembershipFactory::new()->create();
    $filters = ['lga' => '1', 'q' => $member->name];

    $response = $this->get(route('membership.index', $filters));

    $response->assertSee(route('membership.show', ['membership' => $member, 'filters' => $filters]))
        ->assertSee(route('membership.edit', ['membership' => $member, 'filters' => $filters]));
});

test('member screens show the selected member and escape their name', function (string $route) {
    $member = MembershipFactory::new()->create(['name' => '<script>alert(1)</script>']);

    $response = $this->get(route($route, $member));

    $response->assertSee($member->name)->assertDontSee($member->name, false);
})->with(['membership.show', 'membership.edit']);

test('unknown members return not found', function (string $route) {
    $this->get(route($route, 99999))->assertNotFound();
})->with(['membership.show', 'membership.edit']);

test('updates only the selected member and preserves identifiers and filters', function () {
    $unit = PollingUnit::factory()->create();
    $member = MembershipFactory::new()->create([
        'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id,
        'delimitation_code' => $unit->delimitation_code, 'cbm_delimitation_code' => 'original-code',
    ]);
    $other = MembershipFactory::new()->create(['name' => 'Other member']);
    $filters = ['lga' => (string) $member->lga, 'page' => '2'];
    $payload = array_merge(memberUpdatePayload($member), ['name' => 'Updated member', 'cbm_id' => 'tampered', 'id' => $other->id, 'cbm_delimitation_code' => 'tampered']);

    $response = $this->patch(route('membership.update', ['membership' => $member, 'filters' => $filters]), $payload);

    $response->assertSessionHasNoErrors()->assertRedirect(route('membership.index', $filters))->assertSessionHas('status');
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'name' => 'Updated member', 'cbm_id' => $member->cbm_id, 'cbm_delimitation_code' => 'original-code']);
    $this->assertDatabaseHas('memberships', ['id' => $other->id, 'name' => 'Other member']);
    $this->assertDatabaseHas('polling_units', ['id' => $unit->id, 'next_member_number' => 1]);
});

test('moving polling unit allocates its next location code and preserves CBM ID', function () {
    $unit = PollingUnit::factory()->create(['next_member_number' => 7]);
    $member = MembershipFactory::new()->create();
    $payload = array_merge(memberUpdatePayload($member), ['has_voters_card' => 'yes', 'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id]);

    $response = $this->patch(route('membership.update', $member), $payload);

    $response->assertSessionHasNoErrors()->assertRedirect(route('membership.index'));
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'cbm_id' => $member->cbm_id, 'pu' => $unit->id, 'delimitation_code' => $unit->delimitation_code, 'cbm_delimitation_code' => $unit->delimitation_code.'/007']);
    $this->assertDatabaseHas('polling_units', ['id' => $unit->id, 'next_member_number' => 8]);
});

test('removing voter card clears electoral fields', function () {
    $unit = PollingUnit::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id, 'delimitation_code' => 'code', 'cbm_delimitation_code' => 'code/001']);

    $response = $this->patch(route('membership.update', $member), array_merge(memberUpdatePayload($member), ['has_voters_card' => 'no']));

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'ward' => null, 'pu' => null, 'same_address' => null, 'delimitation_code' => null, 'cbm_delimitation_code' => null, 'cbm_id' => $member->cbm_id]);
});

test('invalid member details are rejected without saving', function (array $changes, array $errors) {
    $lga = Lga::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $lga->id, 'name' => 'Original member']);

    $response = $this->from(route('membership.edit', $member))->patch(route('membership.update', $member), array_merge(memberUpdatePayload($member), $changes));

    $response->assertSessionHasErrors($errors)->assertRedirect(route('membership.edit', $member));
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'name' => 'Original member']);
})->with([
    'required fields' => [['name' => '', 'phone' => '', 'gender' => 'invalid', 'age_range' => 'invalid'], ['name', 'phone', 'gender', 'age_range']],
    'invalid contact' => [['name' => 'Changed', 'phone' => 'not-a-phone', 'email' => 'not-email'], ['phone', 'email']],
    'missing electoral details' => [['has_voters_card' => 'yes', 'ward' => null, 'pu' => null], ['ward', 'pu']],
]);

test('duplicate phone and email belong to only one member', function () {
    $lga = Lga::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $lga->id]);
    $other = MembershipFactory::new()->create(['email' => 'other@example.com']);

    $response = $this->patch(route('membership.update', $member), array_merge(memberUpdatePayload($member), ['phone' => $other->phone, 'email' => $other->email]));

    $response->assertSessionHasErrors(['phone', 'email']);
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'phone' => $member->phone, 'email' => null]);
});

test('location selections must belong to their parents', function () {
    $unit = PollingUnit::factory()->create();
    $otherUnit = PollingUnit::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $otherUnit->ward->lga_id]);
    $payload = array_merge(memberUpdatePayload($member), ['has_voters_card' => 'yes', 'ward' => $unit->ward_id, 'pu' => $otherUnit->id]);

    $response = $this->patch(route('membership.update', $member), $payload);

    $response->assertSessionHasErrors(['ward', 'pu']);
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'ward' => null, 'pu' => null]);
});
