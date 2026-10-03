<?php

use App\Models\Lga;
use Database\Factories\MembershipFactory;

test('gender filters synchronize members KPIs charts and exports', function (string $gender, int $count, int $withoutCard) {
    $lga = Lga::factory()->create();
    MembershipFactory::new()->create(['lga' => $lga->id, 'gender' => 'male', 'ward' => '11']);
    MembershipFactory::new()->create(['lga' => $lga->id, 'gender' => 'male', 'ward' => null]);
    MembershipFactory::new()->create(['lga' => $lga->id, 'gender' => 'female', 'ward' => null]);
    MembershipFactory::new()->create(['lga' => '999', 'gender' => 'female']);
    $filters = ['gender' => $gender, 'lga' => $lga->id];

    $response = $this->get(route('membership.index', $filters));
    $response->assertOk()
        ->assertViewHas('stats', fn ($stats) => $stats['total'] === $count && $stats['votersCard'] === $withoutCard)
        ->assertViewHas('members', fn ($members) => $members->total() === $count)
        ->assertViewHas('breakdown', fn ($rows) => $rows->sum('count') === $count)
        ->assertViewHas('trendData', fn ($trend) => array_sum($trend['counts']) === $count);
    $export = $this->get(route('membership.export', ['type' => 'json', ...$filters, 'page' => 2]));
    $records = json_decode($export->streamedContent(), true);
    expect($records)->toHaveCount($count);
    if ($gender !== '') {
        expect(array_unique(array_column($records, 'gender')))->toBe([$gender]);
        $response->assertSee('Remove Gender: '.ucfirst($gender));
    }
})->with([['male', 2, 1], ['female', 1, 1], ['', 3, 2]]);

test('gender combines with voter card filter and supports empty results', function () {
    MembershipFactory::new()->create(['gender' => 'male', 'ward' => null]);
    MembershipFactory::new()->create(['gender' => 'female', 'ward' => '11']);

    $this->get(route('membership.index', ['gender' => 'male', 'has_voters_card' => 'yes']))
        ->assertViewHas('members', fn ($members) => $members->total() === 0)
        ->assertViewHas('stats', ['total' => 0, 'votersCard' => 0, 'vCP' => 0]);
});

test('gender is preserved in member navigation and removed independently by its chip', function () {
    $member = MembershipFactory::new()->create(['gender' => 'male']);
    $filters = ['gender' => 'male', 'q' => $member->name];
    $this->get(route('membership.index', $filters))
        ->assertSee(route('membership.show', ['membership' => $member, 'filters' => $filters]))
        ->assertViewHas('filterChips', fn ($chips) => collect($chips)->firstWhere('label', 'Gender: Male')['url'] === route('membership.index', ['q' => $member->name]));
    $this->get(route('membership.show', ['membership' => $member, 'filters' => $filters]))
        ->assertViewHas('filters', $filters);
});

test('invalid gender filters are rejected on dashboard export and bulk cards', function (mixed $gender) {
    $this->getJson(route('membership.index', ['gender' => $gender]))->assertUnprocessable()->assertJsonValidationErrors('gender');
    $this->getJson(route('membership.export', ['type' => 'json', 'gender' => $gender]))->assertUnprocessable()->assertJsonValidationErrors('gender');
    $this->postJson(route('membership.cards.download', ['gender' => $gender]), ['scope' => 'filtered', 'format' => 'zip'])->assertUnprocessable()->assertJsonValidationErrors('gender');
})->with(['unknown value' => ['unknown'], 'array input' => [['male']]]);
