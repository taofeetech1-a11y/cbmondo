<?php

use App\Models\User;
use Database\Factories\MembershipFactory;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

test('membership KPIs reflect the complete filtered selection', function (array $filters, int $total, int $withoutCard, int $percentage) {
    MembershipFactory::new()->count(6)->sequence(
        ['lga' => '1', 'ward' => '11', 'pu' => '111', 'name' => 'Selected voter'],
        ['lga' => '1', 'ward' => '11', 'pu' => '112', 'name' => 'Second voter'],
        ['lga' => '1', 'ward' => '12', 'pu' => '121', 'name' => 'Third voter'],
        ['lga' => '1', 'ward' => null, 'pu' => null, 'name' => 'Selected nonvoter'],
        ['lga' => '2', 'ward' => '21', 'pu' => '211', 'name' => 'Selected outside voter'],
        ['lga' => '2', 'ward' => null, 'pu' => null, 'name' => 'Selected outside nonvoter'],
    )->create();

    $response = $this->get(route('membership.index', $filters));

    $response->assertOk()
        ->assertViewHas('stats', ['total' => $total, 'votersCard' => $withoutCard, 'vCP' => $percentage])
        ->assertViewHas('members', fn (LengthAwarePaginator $members): bool => $members->total() === $total)
        ->assertSee('Totals for current filters and search');
})->with([
    'all locations' => [[], 6, 2, 33],
    'selected LGA' => [['lga' => 1], 4, 1, 25],
    'selected ward' => [['lga' => 1, 'ward' => 11], 2, 0, 0],
    'selected polling unit' => [['lga' => 1, 'ward' => 11, 'pu' => 111], 1, 0, 0],
    'voter card holders' => [['lga' => 1, 'has_voters_card' => 'yes'], 3, 0, 0],
    'members without cards' => [['lga' => 1, 'has_voters_card' => 'no'], 1, 1, 100],
    'search within LGA' => [['lga' => 1, 'q' => 'Selected'], 2, 1, 50],
    'no matches' => [['lga' => 1, 'q' => 'Missing member'], 0, 0, 0],
    'incompatible locations' => [['lga' => 2, 'ward' => 11], 0, 0, 0],
]);

test('membership KPIs count all matches beyond the current page', function () {
    MembershipFactory::new()->count(12)->create(['lga' => '1', 'ward' => '11', 'pu' => '111']);
    MembershipFactory::new()->count(3)->create(['lga' => '1']);
    MembershipFactory::new()->create(['lga' => '2']);

    $response = $this->get(route('membership.index', ['lga' => 1, 'page' => 2]));

    $response->assertOk()
        ->assertViewHas('stats', ['total' => 15, 'votersCard' => 3, 'vCP' => 20])
        ->assertViewHas('members', fn (LengthAwarePaginator $members): bool => $members->count() === 5 && $members->total() === 15);
});

test('empty membership summaries display zero without dividing by zero', function () {
    $response = $this->get(route('membership.index'));

    $response->assertOk()
        ->assertViewHas('stats', ['total' => 0, 'votersCard' => 0, 'vCP' => 0])
        ->assertSee('No registrations match the current filters and search.');
});
