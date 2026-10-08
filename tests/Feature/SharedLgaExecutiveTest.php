<?php

use App\Models\ExecutiveAssignment;
use App\Models\ExecutivePosition;
use App\Models\Lga;
use App\Models\User;
use Database\Factories\MembershipFactory;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

test('LGA position creation and renaming apply to every LGA', function () {
    $first = Lga::factory()->create();
    $second = Lga::factory()->create();
    $this->post(route('executive-positions.store'), ['level' => 'lga', 'name' => 'Chairperson'])->assertSessionHasNoErrors();
    $position = ExecutivePosition::sole();
    $this->assertDatabaseHas('executive_positions', ['id' => $position->id, 'lga_id' => null, 'location_key' => 0]);
    $this->post(route('executive-positions.store'), ['level' => 'lga', 'name' => 'CHAIRPERSON', 'lga' => $second->id])->assertSessionHasErrors('name');
    $this->patch(route('executive-positions.update', $position), ['name' => 'President'])->assertSessionHasNoErrors();
    foreach ([$first, $second, Lga::factory()->create()] as $lga) {
        $member = MembershipFactory::new()->create(['lga' => $lga->id]);
        $this->get(route('executives.create', ['cbm_id' => $member->cbm_id]))->assertOk()->assertSee('President');
        $this->get(route('executive-positions.index', ['level' => 'lga', 'lga' => $lga->id]))->assertViewHas('positions', fn ($positions) => $positions->total() === 1);
    }
});

test('a shared LGA position has independent occupants and availability per LGA', function () {
    $first = Lga::factory()->create();
    $second = Lga::factory()->create();
    $position = ExecutivePosition::factory()->create(['level' => 'lga']);
    $member = MembershipFactory::new()->create(['lga' => $first->id]);
    $other = MembershipFactory::new()->create(['lga' => $second->id]);
    $sameLga = MembershipFactory::new()->create(['lga' => $first->id]);
    $this->post(route('executives.store'), ['membership_id' => $member->id, 'executive_position_id' => $position->id, 'scope_key' => $second->id])->assertSessionHasNoErrors();
    $this->get(route('executives.create', ['cbm_id' => $other->cbm_id]))->assertOk()->assertSee('— Vacant');
    $this->get(route('executives.create', ['cbm_id' => $sameLga->cbm_id]))->assertOk()->assertSee('— Occupied');
    $this->post(route('executives.store'), ['membership_id' => $sameLga->id, 'executive_position_id' => $position->id])->assertSessionHasErrors('executive_position_id');
    $this->post(route('executives.store'), ['membership_id' => $other->id, 'executive_position_id' => $position->id])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('executive_assignments', ['membership_id' => $member->id, 'scope_key' => $first->id]);
    $this->assertDatabaseHas('executive_assignments', ['membership_id' => $other->id, 'scope_key' => $second->id]);
    $this->get(route('executives.index', ['lga' => $first->id]))->assertViewHas('stats', ['assignments' => 1, 'members' => 1, 'state' => 0, 'local' => 1])->assertSee($first->name);
    $this->delete(route('executive-positions.destroy', $position))->assertSessionHasErrors('position');
    $this->delete(route('executives.destroy', $member->executiveAssignments()->sole()))->assertRedirect();
    $this->assertDatabaseCount('executive_assignments', 1);
    $this->post(route('executives.store'), ['membership_id' => $sameLga->id, 'executive_position_id' => $position->id])->assertSessionHasNoErrors();
});

test('a shared LGA assignment prevents moving the member to a different LGA', function () {
    $lga = Lga::factory()->create();
    $other = Lga::factory()->create();
    $member = MembershipFactory::new()->create(['lga' => $lga->id]);
    $position = ExecutivePosition::factory()->create(['level' => 'lga']);
    ExecutiveAssignment::factory()->create(['membership_id' => $member->id, 'executive_position_id' => $position->id, 'assignment_group' => 'local']);
    $payload = ['name' => $member->name, 'phone' => $member->phone, 'email' => null, 'age_range' => '25-34', 'gender' => 'female', 'has_voters_card' => 'no', 'lga' => $other->id, 'support_us' => 'yes', 'want_to_be_contacted' => 'yes', 'same_address' => 'no'];
    $this->patch(route('membership.update', $member), $payload)->assertSessionHasErrors('lga');
    $this->assertDatabaseHas('memberships', ['id' => $member->id, 'lga' => $lga->id]);
});

test('database rejects a second occupant in the same LGA slot', function () {
    $lga = Lga::factory()->create();
    $position = ExecutivePosition::factory()->create(['level' => 'lga']);
    $member = MembershipFactory::new()->create(['lga' => $lga->id]);
    ExecutiveAssignment::factory()->create(['membership_id' => $member->id, 'executive_position_id' => $position->id, 'assignment_group' => 'local']);
    $other = MembershipFactory::new()->create(['lga' => $lga->id]);
    expect(fn () => ExecutiveAssignment::factory()->create(['membership_id' => $other->id, 'executive_position_id' => $position->id, 'assignment_group' => 'local']))->toThrow(UniqueConstraintViolationException::class);
});

test('migration merges legacy LGA lists and preserves every occupant through rollback', function () {
    $migration = require database_path('migrations/2026_10_05_164317_share_lga_executive_positions.php');
    $migration->down();
    $first = Lga::factory()->create();
    $second = Lga::factory()->create();
    $members = [];
    foreach ([$first, $second] as $lga) {
        $member = MembershipFactory::new()->create(['lga' => $lga->id]);
        $members[] = $member;
        $position = ExecutivePosition::factory()->create(['level' => 'lga', 'lga_id' => $lga->id, 'location_key' => $lga->id]);
        DB::table('executive_assignments')->insert(['executive_position_id' => $position->id, 'membership_id' => $member->id, 'assignment_group' => 'local']);
    }
    $migration->up();
    $this->assertDatabaseCount('executive_positions', 1);
    $this->assertDatabaseCount('executive_assignments', 2);
    foreach ($members as $member) {
        $this->assertDatabaseHas('executive_assignments', ['membership_id' => $member->id, 'scope_key' => $member->lga, 'executive_position_id' => ExecutivePosition::sole()->id]);
    }
    $migration->down();
    $this->assertDatabaseCount('executive_positions', 2);
    foreach ($members as $member) {
        $assignment = ExecutiveAssignment::where('membership_id', $member->id)->sole();
        expect((int) $assignment->position->lga_id)->toBe((int) $member->lga);
    }
    $migration->up();
    $this->assertDatabaseCount('executive_assignments', 2);
});
