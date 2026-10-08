<?php

use App\Models\ExecutiveAssignment;
use App\Models\ExecutivePosition;
use App\Models\Membership;
use App\Models\PollingUnit;
use App\Models\User;
use Database\Factories\MembershipFactory;
use Illuminate\Database\UniqueConstraintViolationException;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

function executivePositionAt(string $level, PollingUnit $unit, string $name = 'Chairperson'): ExecutivePosition
{
    return ExecutivePosition::factory()->create([
        'name' => $name, 'name_key' => strtolower($name), 'level' => $level,
        'location_key' => match ($level) {
            'state', 'lga' => 0, 'ward' => $unit->ward_id, 'polling_unit' => $unit->id
        },
        'lga_id' => in_array($level, ['state', 'lga']) ? null : $unit->ward->lga_id,
        'ward_id' => in_array($level, ['ward', 'polling_unit']) ? $unit->ward_id : null,
        'polling_unit_id' => $level === 'polling_unit' ? $unit->id : null,
    ]);
}

function executiveMemberAt(PollingUnit $unit, string $gender = 'female'): Membership
{
    return MembershipFactory::new()->create(['lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id, 'gender' => $gender]);
}

test('executive pages render empty states and navigation', function (string $route) {
    $this->get(route($route))->assertOk()->assertSee('Manage positions')->assertSee('Assign executive');
})->with(['executives.index', 'executives.create', 'executive-positions.index']);

test('positions can be added separately for each level and location', function (string $level) {
    $unit = PollingUnit::factory()->create();
    $this->post(route('executive-positions.store'), ['level' => $level, 'name' => 'Chairperson', 'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id])->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('executive_positions', ['level' => $level, 'name' => 'Chairperson', 'lga_id' => in_array($level, ['state', 'lga']) ? null : $unit->ward->lga_id]);
})->with(['state', 'lga', 'ward', 'polling_unit']);

test('duplicate position names are rejected within a location but allowed elsewhere', function () {
    $unit = PollingUnit::factory()->create();
    executivePositionAt('ward', $unit);
    $this->post(route('executive-positions.store'), ['level' => 'ward', 'name' => ' CHAIRPERSON ', 'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id])->assertSessionHasErrors('name');
    $other = PollingUnit::factory()->create();
    $this->post(route('executive-positions.store'), ['level' => 'ward', 'name' => 'Chairperson', 'lga' => $other->ward->lga_id, 'ward' => $other->ward_id])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('executive_positions', 2);
});

test('position locations must belong to the selected parents', function () {
    $unit = PollingUnit::factory()->create();
    $other = PollingUnit::factory()->create();
    $this->post(route('executive-positions.store'), ['level' => 'polling_unit', 'name' => 'Chairperson', 'lga' => $other->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $other->id])->assertSessionHasErrors(['ward', 'pu']);
    $this->assertDatabaseCount('executive_positions', 0);
});

test('CBM lookup shows only positions in the members own locations and state', function () {
    $unit = PollingUnit::factory()->create();
    $member = executiveMemberAt($unit);
    executivePositionAt('state', $unit, 'State Chair');
    executivePositionAt('ward', $unit, 'My Ward Chair');
    executivePositionAt('ward', PollingUnit::factory()->create(), 'Other Ward Chair');
    $this->get(route('executives.create', ['cbm_id' => $member->cbm_id]))->assertSee($member->name)->assertSee('State Chair')->assertSee('My Ward Chair')->assertDontSee('Other Ward Chair');
    $this->get(route('executives.create', ['cbm_id' => 'missing']))->assertSee('No member found');
});

test('members may hold one local and one state position at the same time', function (string $localLevel) {
    $unit = PollingUnit::factory()->create();
    $member = executiveMemberAt($unit);
    $local = executivePositionAt($localLevel, $unit);
    $state = executivePositionAt('state', $unit);
    foreach ([$local, $state] as $position) {
        $this->post(route('executives.store'), ['membership_id' => $member->id, 'executive_position_id' => $position->id])->assertSessionHasNoErrors()->assertRedirect(route('executives.index'));
    }
    $this->assertDatabaseCount('executive_assignments', 2);
    $this->get(route('executives.index'))->assertViewHas('stats', ['assignments' => 2, 'members' => 1, 'state' => 1, 'local' => 1]);
})->with(['lga', 'ward', 'polling_unit']);

test('occupied positions and additional same group appointments are rejected', function (string $level) {
    $unit = PollingUnit::factory()->create();
    $member = executiveMemberAt($unit);
    $other = executiveMemberAt($unit);
    $position = executivePositionAt($level, $unit);
    $second = executivePositionAt($level === 'state' ? 'state' : 'ward', $unit, 'Secretary');
    ExecutiveAssignment::factory()->create(['membership_id' => $member->id, 'executive_position_id' => $position->id, 'assignment_group' => $level === 'state' ? 'state' : 'local']);
    $this->post(route('executives.store'), ['membership_id' => $other->id, 'executive_position_id' => $position->id])->assertSessionHasErrors('executive_position_id');
    $this->post(route('executives.store'), ['membership_id' => $member->id, 'executive_position_id' => $second->id])->assertSessionHasErrors('executive_position_id');
    $this->assertDatabaseCount('executive_assignments', 1);
})->with(['state', 'lga']);

test('forged out of area appointments are rejected server side', function (string $level) {
    $unit = PollingUnit::factory()->create();
    $member = executiveMemberAt($unit);
    $position = executivePositionAt($level, PollingUnit::factory()->create());
    $this->post(route('executives.store'), ['membership_id' => $member->id, 'executive_position_id' => $position->id, 'lga' => $position->lga_id])->assertSessionHasErrors('executive_position_id');
    $this->assertDatabaseCount('executive_assignments', 0);
})->with(['ward', 'polling_unit']);

test('members with no electoral details cannot take ward or polling unit positions', function () {
    $unit = PollingUnit::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $unit->ward->lga_id]);
    $position = executivePositionAt('ward', $unit);
    $this->post(route('executives.store'), ['membership_id' => $member->id, 'executive_position_id' => $position->id])->assertSessionHasErrors('executive_position_id');
});

test('executive filters affect appointments and totals', function (string $filter) {
    $unit = PollingUnit::factory()->create();
    $member = executiveMemberAt($unit, 'male');
    $position = executivePositionAt('ward', $unit);
    ExecutiveAssignment::factory()->create(['membership_id' => $member->id, 'executive_position_id' => $position->id, 'assignment_group' => 'local']);
    ExecutiveAssignment::factory()->create(['membership_id' => executiveMemberAt(PollingUnit::factory()->create())->id]);
    $value = $filter === 'level' ? 'ward' : $member->$filter;
    $this->get(route('executives.index', [$filter => $value]))->assertViewHas('stats', ['assignments' => 1, 'members' => 1, 'state' => 0, 'local' => 1]);
})->with(['lga', 'ward', 'pu', 'gender', 'level']);

test('removal frees only the selected position and preserves member and other role', function () {
    $unit = PollingUnit::factory()->create();
    $member = executiveMemberAt($unit);
    $local = ExecutiveAssignment::factory()->create(['membership_id' => $member->id, 'executive_position_id' => executivePositionAt('lga', $unit)->id, 'assignment_group' => 'local']);
    $state = ExecutiveAssignment::factory()->create(['membership_id' => $member->id]);
    $this->delete(route('executives.destroy', $local))->assertRedirect(route('executives.index'));
    $this->assertModelMissing($local);
    $this->assertModelExists($state);
    $this->assertModelExists($member);
    $this->post(route('executives.store'), ['membership_id' => $member->id, 'executive_position_id' => $local->executive_position_id])->assertSessionHasNoErrors();
});

test('occupied positions cannot be deleted but may be renamed without changing location', function () {
    $assignment = ExecutiveAssignment::factory()->create();
    $position = $assignment->position;
    $this->delete(route('executive-positions.destroy', $position))->assertSessionHasErrors('position');
    $this->patch(route('executive-positions.update', $position), ['name' => 'President', 'level' => 'ward'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('executive_positions', ['id' => $position->id, 'name' => 'President', 'level' => 'state']);
    $this->assertModelExists($assignment);
    $this->delete(route('executives.destroy', $assignment));
    $this->delete(route('executive-positions.destroy', $position))->assertSessionHasNoErrors();
    $this->assertModelMissing($position);
});

test('location updates cannot strand a local executive in a different area', function () {
    $unit = PollingUnit::factory()->create();
    $member = executiveMemberAt($unit);
    $position = executivePositionAt('ward', $unit);
    ExecutiveAssignment::factory()->create(['membership_id' => $member->id, 'executive_position_id' => $position->id, 'assignment_group' => 'local']);
    $payload = ['name' => $member->name, 'phone' => $member->phone, 'email' => null, 'age_range' => '25-34', 'gender' => 'female', 'has_voters_card' => 'no', 'lga' => $member->lga, 'support_us' => 'yes', 'want_to_be_contacted' => 'yes', 'same_address' => 'no'];
    $this->patch(route('membership.update', $member), $payload)->assertSessionHasErrors('lga');
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'ward' => $unit->ward_id]);
});

test('invalid assignment inputs are rejected without writes', function () {
    $this->postJson(route('executives.store'), ['membership_id' => 99999, 'executive_position_id' => 99999])->assertUnprocessable()->assertJsonValidationErrors(['membership_id', 'executive_position_id']);
    $this->assertDatabaseCount('executive_assignments', 0);
});

test('database constraint prevents duplicate occupants', function () {
    $assignment = ExecutiveAssignment::factory()->create();
    expect(fn () => ExecutiveAssignment::factory()->create(['executive_position_id' => $assignment->executive_position_id]))->toThrow(UniqueConstraintViolationException::class);
});
