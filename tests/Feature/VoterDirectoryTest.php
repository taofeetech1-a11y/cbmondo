<?php

use App\Models\CoordinatorAgent;
use App\Models\PollingUnit;
use App\Models\Voter;

function createDirectoryVoter(PollingUnit $unit, string $vin, string $name): Voter
{
    return $unit->voters()->create([
        'vin' => $vin, 'full_name' => $name, 'surname' => $name,
        'gender' => 'Female', 'age' => 30, 'serial' => 1,
    ]);
}

it('filters voters and upload summaries by location', function (string $field) {
    $unit = PollingUnit::factory()->create();
    $other = PollingUnit::factory()->create();
    createDirectoryVoter($unit, 'MATCH-VIN', 'Matching Voter');
    createDirectoryVoter($other, 'OTHER-VIN', 'Other Voter');
    $location = ['lga' => $unit->ward->lga_id, 'ward' => $unit->ward_id, 'polling_unit_id' => $unit->id];

    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('voters.index', [$field => $location[$field]]))->assertOk()
        ->assertSee('Matching Voter')->assertDontSee('Other Voter')
        ->assertViewHas('totalUnits', 1)->assertViewHas('uploadedUnits', 1)
        ->assertViewHas('pendingUnits', 0);
})->with(['lga', 'ward', 'polling_unit_id']);

it('searches by name or VIN without leaking voters from other locations', function (string $search) {
    $unit = PollingUnit::factory()->create();
    createDirectoryVoter($unit, 'MATCH-VIN', 'Matching Voter');
    createDirectoryVoter($unit, 'UNRELATED', 'Unrelated Voter');
    createDirectoryVoter(PollingUnit::factory()->create(), 'OUTSIDE-MATCH-VIN', 'Outside Matching Voter');

    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('voters.index', ['lga' => $unit->ward->lga_id, 'q' => $search]))
        ->assertOk()->assertSee('Matching Voter')->assertDontSee('Unrelated Voter')
        ->assertDontSee('Outside Matching Voter')
        ->assertViewHas('voters', fn ($voters): bool => $voters->total() === 1);
})->with(['Matching', 'MATCH-VIN']);

it('distinguishes pending units from uploaded empty and older linked registers', function () {
    $legacy = PollingUnit::factory()->create(['name' => 'Legacy uploaded unit']);
    createDirectoryVoter($legacy, 'LEGACY', 'Legacy Voter');
    PollingUnit::factory()->create(['name' => 'Empty uploaded unit', 'register_uploaded_at' => now()]);
    PollingUnit::factory()->create(['name' => 'Pending unit']);

    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('voters.index'))->assertOk()
        ->assertViewHas('uploadedUnits', 2)->assertViewHas('pendingUnits', 1)
        ->assertSee('Legacy uploaded unit')->assertSee('Empty uploaded unit')->assertSee('Pending unit');

    $this->get(route('voters.index', ['upload_status' => 'pending']))->assertOk()
        ->assertViewHas('unitStatuses', fn ($units): bool => $units->total() === 1)
        ->assertSee('Pending unit')->assertDontSee('Legacy uploaded unit')->assertDontSee('Empty uploaded unit');

    $this->get(route('voters.index', ['upload_status' => 'uploaded']))->assertOk()
        ->assertViewHas('unitStatuses', fn ($units): bool => $units->total() === 2)
        ->assertDontSee('Pending unit')->assertSee('Legacy uploaded unit')->assertSee('Empty uploaded unit');
});

it('rejects unrelated location selections and unsupported upload statuses', function () {
    $unit = PollingUnit::factory()->create();
    $other = PollingUnit::factory()->create();

    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('voters.index', ['lga' => $unit->ward->lga_id, 'ward' => $other->ward_id]))
        ->assertSessionHasErrors('ward');
    $this->get(route('voters.index', ['ward' => $unit->ward_id, 'polling_unit_id' => $other->id]))
        ->assertSessionHasErrors('polling_unit_id');
    $this->get(route('voters.index', ['upload_status' => 'unknown']))->assertSessionHasErrors('upload_status');
});

it('preserves filters across voter and polling unit pagination', function () {
    $unit = PollingUnit::factory()->create();
    PollingUnit::factory()->count(6)->for($unit->ward)->create();
    for ($number = 1; $number <= 25; $number++) {
        createDirectoryVoter($unit, 'VIN-'.$number, 'Pagination Voter '.$number);
    }

    $this->actingAs(CoordinatorAgent::factory()->create(), 'coordinator')
        ->get(route('voters.index', ['lga' => $unit->ward->lga_id, 'q' => 'Pagination']))->assertOk()
        ->assertViewHas('voters', fn ($voters): bool => $voters->count() === 24 && str_contains($voters->nextPageUrl(), 'q=Pagination'))
        ->assertViewHas('unitStatuses', fn ($units): bool => $units->count() === 6 && str_contains($units->nextPageUrl(), 'unit_page=2'));
});
