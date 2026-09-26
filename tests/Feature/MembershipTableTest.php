<?php

use Database\Factories\MembershipFactory;

test('table sorts the complete selection with stable pagination and exports', function (string $direction, array $names) {
    foreach (['Charlie', 'Alpha', 'Bravo'] as $name) {
        MembershipFactory::new()->create(['name' => $name, 'lga' => '1']);
    }
    MembershipFactory::new()->create(['name' => 'Outside', 'lga' => '2']);
    $filters = ['lga' => '1', 'sort' => 'name', 'direction' => $direction, 'per_page' => 25];

    $this->get(route('membership.index', $filters))->assertViewHas('members', fn ($members) => $members->pluck('name')->all() === $names && $members->perPage() === 25);
    $export = $this->get(route('membership.export', ['type' => 'json', ...$filters, 'page' => 2]));
    expect(array_column(json_decode($export->streamedContent(), true), 'name'))->toBe($names);
})->with([['asc', ['Alpha', 'Bravo', 'Charlie']], ['desc', ['Charlie', 'Bravo', 'Alpha']]]);

test('page size limits only visible rows and retains total export selection', function (int $size) {
    MembershipFactory::new()->count(26)->create();
    $this->get(route('membership.index', ['per_page' => $size]))->assertViewHas('members', fn ($members) => $members->count() === min($size, 26) && $members->total() === 26);
    $export = $this->get(route('membership.export', ['type' => 'json', 'per_page' => $size, 'page' => 2]));
    expect(json_decode($export->streamedContent(), true))->toHaveCount(26);
})->with([10, 25, 50, 100]);

test('invalid table controls cannot become query expressions', function (string $route, array $controls, string $error) {
    $this->getJson(route($route, ['type' => 'json', ...$controls]))->assertUnprocessable()->assertJsonValidationErrors($error);
})->with(['membership.index', 'membership.export'])->with([
    [['sort' => 'name desc; drop table memberships'], 'sort'],
    [['direction' => 'invalid'], 'direction'],
    [['per_page' => 999999], 'per_page'],
    [['per_page' => -1], 'per_page'],
]);

test('removing parent filter chips clears dependent filters and pagination', function () {
    $filters = ['lga' => '1', 'ward' => '2', 'pu' => '3', 'q' => 'Selected', 'period' => 'custom', 'date_from' => '2026-09-01', 'date_to' => '2026-09-25', 'sort' => 'name', 'direction' => 'asc', 'per_page' => '25', 'page' => '2'];
    $response = $this->get(route('membership.index', $filters));
    $chips = $response->viewData('filterChips');
    parse_str(parse_url($chips[0]['url'], PHP_URL_QUERY), $remaining);
    expect($remaining)->not->toHaveKeys(['lga', 'ward', 'pu', 'page'])->toHaveKeys(['q', 'period', 'sort', 'direction', 'per_page']);
    parse_str(parse_url($chips[1]['url'], PHP_URL_QUERY), $remaining);
    expect($remaining)->not->toHaveKeys(['ward', 'pu', 'page'])->toHaveKey('lga', '1');
    parse_str(parse_url($chips[4]['url'], PHP_URL_QUERY), $remaining);
    expect($remaining)->not->toHaveKeys(['period', 'date_from', 'date_to', 'page'])->toHaveKey('sort', 'name');
});

test('table selections survive member navigation and pagination', function () {
    $members = MembershipFactory::new()->count(26)->create(['lga' => '1']);
    $filters = ['lga' => '1', 'sort' => 'created_at', 'direction' => 'desc', 'per_page' => '25'];
    $this->get(route('membership.index', $filters))->assertSee(route('membership.index', [...$filters, 'page' => 2]))
        ->assertSee(route('membership.show', ['membership' => $members->last(), 'filters' => $filters]));
    $this->get(route('membership.show', ['membership' => $members->last(), 'filters' => $filters]))->assertViewHas('filters', $filters);
    $this->get(route('membership.edit', ['membership' => $members->last(), 'filters' => $filters]))->assertViewHas('filters', $filters);
});
