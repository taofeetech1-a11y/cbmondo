<?php

use App\Models\CoordinatorAgent;
use App\Models\Membership;
use App\Models\PollingUnit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\MembershipFactory;

function wardMembershipAgent(PollingUnit $unit): CoordinatorAgent
{
    return CoordinatorAgent::factory()->for(MembershipFactory::new()->state([
        'name' => 'Ward Coordinator', 'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id, 'created_at' => '2020-01-01 00:00:00',
    ]), 'membership')->create();
}

function wardMembershipRecord(PollingUnit $unit, array $attributes = []): Membership
{
    return MembershipFactory::new()->create(array_replace(['lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id], $attributes));
}

it('shows members across the assigned ward with phones but excludes other and unassigned wards', function () {
    $unit = PollingUnit::factory()->create();
    $secondUnit = PollingUnit::factory()->for($unit->ward, 'ward')->create();
    $outside = PollingUnit::factory()->create();
    $own = wardMembershipRecord($unit, ['name' => 'Own unit member']);
    $sameWard = wardMembershipRecord($secondUnit, ['name' => 'Same ward other unit']);
    $other = wardMembershipRecord($outside, ['name' => 'Outside ward member']);
    $unassigned = MembershipFactory::new()->create(['name' => 'Ward not recorded', 'lga' => $unit->ward->lga_id]);
    MembershipFactory::new()->create(['name' => 'Inconsistent LGA', 'lga' => $outside->ward->lga_id, 'ward' => $unit->ward_id]);
    $agent = wardMembershipAgent($unit);
    $this->actingAs($agent, 'coordinator')->get(route('coordinator.memberships', ['ward' => $outside->ward_id, 'lga' => $outside->ward->lga_id]))
        ->assertOk()->assertSee($own->name)->assertSee($own->phone)->assertSee($sameWard->name)->assertSee($sameWard->phone)
        ->assertDontSee($other->name)->assertDontSee($other->phone)->assertDontSee($unassigned->name)->assertDontSee('Inconsistent LGA')
        ->assertSee($secondUnit->name)->assertDontSee($outside->name)
        ->assertViewHas('members', fn ($members) => $members->total() === 3)
        ->assertViewHas('membershipTrend', fn ($trend) => array_sum($trend['counts']) === 2)
        ->assertHeader('Pragma', 'no-cache')->assertHeader('X-Frame-Options', 'DENY');
    $this->get(route('membership.show', $other))->assertRedirect(route('login'));
    $this->get(route('membership.edit', $own))->assertRedirect(route('login'));
    $this->get(route('coordinator.dashboard'))->assertOk()->assertSee('Registration trends')->assertSee('View ward members and phone numbers')
        ->assertViewHas('wardMemberTotal', 3)->assertViewHas('membershipTrend', fn ($trend) => array_sum($trend['counts']) === 2);
});

it('requires an active coordinator with an assigned polling unit for ward membership access', function () {
    $this->get(route('coordinator.memberships'))->assertRedirect(route('coordinator.login'));
    foreach (['super_admin', 'admin', 'it'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]), 'web')->get(route('coordinator.memberships'))->assertRedirect(route('coordinator.login'));
    }
    auth('web')->logout();
    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')->get(route('coordinator.memberships'))->assertForbidden();
    auth('coordinator')->logout();
    $unit = PollingUnit::factory()->create();
    $inactive = wardMembershipAgent($unit);
    $inactive->forceFill(['is_active' => false])->save();
    $this->actingAs($inactive, 'coordinator')->get(route('coordinator.memberships'))->assertForbidden();
});

it('fills all 30 calendar days and applies inclusive start and exclusive end boundaries', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-23 12:00:00'));
    $unit = PollingUnit::factory()->create();
    $agent = wardMembershipAgent($unit);
    foreach (['2026-09-23 23:59:59', '2026-09-24 00:00:00', '2026-10-22 01:00:00', '2026-10-22 23:59:59', '2026-10-23 23:59:59', '2026-10-24 00:00:00'] as $date) {
        wardMembershipRecord($unit, ['created_at' => $date]);
    }
    wardMembershipRecord($unit, ['created_at' => null]);
    wardMembershipRecord(PollingUnit::factory()->create(), ['created_at' => '2026-10-23 11:00:00']);
    $this->actingAs($agent, 'coordinator')->get(route('coordinator.memberships'))
        ->assertOk()->assertViewHas('membershipTrend', fn ($trend) => count($trend['labels']) === 30
            && $trend['labels'][0] === '2026-09-24' && $trend['labels'][29] === '2026-10-23'
            && $trend['counts'][0] === 1 && $trend['counts'][1] === 0 && $trend['counts'][28] === 2 && $trend['counts'][29] === 1
            && array_sum($trend['counts']) === 4);
});

it('searches ward memberships by name reference and phone with a scoped trend', function (string $field) {
    $unit = PollingUnit::factory()->create();
    $member = wardMembershipRecord($unit, ['name' => 'Searchable Member']);
    wardMembershipRecord(PollingUnit::factory()->create(), ['name' => 'Searchable Member']);
    $this->actingAs(wardMembershipAgent($unit), 'coordinator')->get(route('coordinator.memberships', ['q' => $member->$field]))
        ->assertOk()->assertViewHas('members', fn ($members) => $members->total() === 1 && $members->first()->id === $member->id)
        ->assertViewHas('membershipTrend', fn ($trend) => array_sum($trend['counts']) === 1);
})->with(['name', 'cbm_id', 'phone']);

it('filters polling units only within the ward and rejects a foreign polling unit', function () {
    $unit = PollingUnit::factory()->create();
    $second = PollingUnit::factory()->for($unit->ward, 'ward')->create();
    $outside = PollingUnit::factory()->create();
    $member = wardMembershipRecord($second);
    wardMembershipRecord($unit);
    $this->actingAs(wardMembershipAgent($unit), 'coordinator')->get(route('coordinator.memberships', ['polling_unit_id' => $second->id]))
        ->assertOk()->assertViewHas('members', fn ($members) => $members->total() === 1 && $members->first()->id === $member->id)
        ->assertViewHas('membershipTrend', fn ($trend) => array_sum($trend['counts']) === 1);
    $this->get(route('coordinator.memberships', ['polling_unit_id' => $outside->id]))->assertSessionHasErrors('polling_unit_id');
    $this->get(route('coordinator.memberships', ['q' => str_repeat('a', 101)]))->assertSessionHasErrors('q');
});

it('preserves filters while paging and escapes names without rendering edit or export actions', function () {
    $unit = PollingUnit::factory()->create();
    MembershipFactory::new()->count(21)->create(['name' => '<script>alert(1)</script>', 'lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'pu' => $unit->id]);
    $this->actingAs(wardMembershipAgent($unit), 'coordinator')->get(route('coordinator.memberships', ['q' => '<script>', 'polling_unit_id' => $unit->id]))
        ->assertOk()->assertSee('<script>alert(1)</script>')->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('Edit member')->assertDontSee('Export CSV')
        ->assertViewHas('members', fn ($members) => $members->total() === 21 && $members->count() === 20 && str_contains($members->nextPageUrl(), 'polling_unit_id='.$unit->id) && str_contains($members->nextPageUrl(), 'q='));
});

it('shows an empty line chart with zero days when no registrations match', function () {
    $unit = PollingUnit::factory()->create();
    $this->actingAs(wardMembershipAgent($unit), 'coordinator')->get(route('coordinator.memberships', ['q' => 'Nobody matches']))
        ->assertOk()->assertSee('No members found')->assertSee('No registrations were recorded during this period')
        ->assertViewHas('membershipTrend', fn ($trend) => count($trend['labels']) === 30 && array_sum($trend['counts']) === 0);
});
