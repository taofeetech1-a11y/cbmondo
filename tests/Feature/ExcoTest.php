<?php

use App\Models\Exco;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\User;
use Database\Factories\MembershipFactory;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

test('excos dashboard includes only linked members and applies location and gender filters', function (string $field) {
    $unit = PollingUnit::factory()->create();
    $target = MembershipFactory::new()->create(['name' => 'Target executive', 'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id, 'gender' => 'male']);
    Exco::factory()->create(['membership_id' => $target->id]);
    $otherUnit = PollingUnit::factory()->create();
    $other = MembershipFactory::new()->create(['name' => 'Other executive', 'lga' => $otherUnit->ward->lga_id, 'ward' => $otherUnit->ward_id, 'pu' => $otherUnit->id, 'gender' => 'female']);
    Exco::factory()->create(['membership_id' => $other->id]);
    MembershipFactory::new()->create(['name' => 'Ordinary member', 'lga' => $target->lga, 'ward' => $target->ward, 'pu' => $target->pu, 'gender' => 'male']);

    $this->get(route('excos.index', [$field => $target->$field]))
        ->assertSee('Target executive')->assertDontSee('Other executive')->assertDontSee('Ordinary member')
        ->assertViewHas('stats', ['total' => 1, 'male' => 1, 'female' => 0])
        ->assertViewHas('members', fn ($members) => $members->total() === 1);
})->with(['lga', 'ward', 'pu', 'gender']);

test('member lookup accepts complete CBM and location member IDs without creating an exco', function (string $field) {
    $member = MembershipFactory::new()->create(['cbm_delimitation_code' => '28/18/01/010/001']);

    $this->get(route('excos.create', ['member_id' => $member->$field]))->assertSee($member->name)->assertSee('Add '.$member->name.' to Excos');
    $this->assertDatabaseCount('excos', 0);
})->with(['cbm_id', 'cbm_delimitation_code']);

test('member lookup handles missing and already added members', function () {
    $exco = Exco::factory()->create();

    $this->get(route('excos.create', ['member_id' => 'missing-id']))->assertSee('No member found');
    $this->get(route('excos.create', ['member_id' => $exco->membership->cbm_id]))->assertSee('This member is already an Exco.');
});

test('adding a member creates only one linked exco even for repeated submissions', function () {
    $member = MembershipFactory::new()->create();

    $this->post(route('excos.store'), ['membership_id' => $member->id, 'name' => 'Tampered name'])->assertRedirect(route('excos.index'));
    $this->post(route('excos.store'), ['membership_id' => $member->id])->assertSessionHas('status', 'This member is already an Exco.');
    $this->assertDatabaseCount('excos', 1);
    $this->assertDatabaseCount('memberships', 1);
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'name' => $member->name]);
    expect($member->exco->membership->id)->toBe($member->id);
});

test('invalid membership references cannot be added', function (mixed $id) {
    $this->postJson(route('excos.store'), ['membership_id' => $id])->assertUnprocessable()->assertJsonValidationErrors('membership_id');
    $this->assertDatabaseCount('excos', 0);
})->with(['missing' => [null], 'unknown' => [99999], 'array' => [[1]], 'text' => ['invalid']]);

test('removing an exco preserves the member and other excos', function () {
    $exco = Exco::factory()->create();
    $member = $exco->membership;
    $other = Exco::factory()->create();

    $this->delete(route('excos.destroy', $exco))->assertRedirect(route('excos.index'));
    $this->assertModelMissing($exco);
    $this->assertModelExists($member);
    $this->assertModelExists($other);
    $this->delete(route('excos.destroy', $exco))->assertNotFound();
});

test('editing an exco uses membership validation and returns to the filtered excos directory', function () {
    $lga = Lga::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $lga->id]);
    Exco::factory()->create(['membership_id' => $member->id]);
    $context = ['membership' => $member, 'filters' => ['gender' => 'female'], 'from' => 'excos'];
    $payload = ['name' => 'Updated Exco', 'phone' => $member->phone, 'email' => null, 'gender' => 'female', 'age_range' => '25-34', 'has_voters_card' => 'no', 'lga' => $lga->id, 'support_us' => 'yes', 'want_to_be_contacted' => 'yes', 'same_address' => 'no'];

    $this->get(route('membership.edit', $context))->assertSee(route('membership.update', $context))->assertSee('Back to Excos');
    $this->patch(route('membership.update', $context), $payload)->assertSessionHasNoErrors()->assertRedirect(route('excos.index', ['gender' => 'female']));
    $this->get(route('excos.index'))->assertSee('Updated Exco');
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'name' => 'Updated Exco']);
    $this->assertDatabaseHas('excos', ['membership_id' => $member->id]);
});

test('invalid filters are rejected and an empty directory is usable', function () {
    $this->get(route('excos.index'))->assertSee('No Excos found')->assertSee(route('excos.create'));
    $this->getJson(route('excos.index', ['gender' => 'invalid', 'lga' => 99999]))->assertUnprocessable()->assertJsonValidationErrors(['gender', 'lga']);
});

test('deleting a membership removes its exco reference', function () {
    $exco = Exco::factory()->create();

    $exco->membership->delete();

    $this->assertModelMissing($exco);
});
