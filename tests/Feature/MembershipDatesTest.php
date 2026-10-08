<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\MembershipFactory;

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
});

test('date presets filter KPIs charts table and exports identically', function (array $filters, int $expected) {
    CarbonImmutable::setTestNow('2026-09-25 12:00:00');
    try {
        foreach (['2026-08-31 23:59:59', '2026-09-01 00:00:00', '2026-09-20 23:59:59', '2026-09-21 00:00:00', '2026-09-25 00:00:00', '2026-09-25 23:59:59', '2026-09-26 00:00:00', '2026-09-28 00:00:00', '2026-10-01 00:00:00'] as $date) {
            MembershipFactory::new()->create(['created_at' => $date, 'lga' => '1', 'name' => 'Selected']);
        }
        MembershipFactory::new()->create(['created_at' => '2026-09-25 10:00:00', 'lga' => '2']);
        $filters = [...$filters, 'lga' => '1', 'q' => 'Selected', 'has_voters_card' => 'no'];

        $response = $this->get(route('membership.index', $filters));

        $response->assertOk()->assertViewHas('stats', fn ($stats) => $stats['total'] === $expected && $stats['votersCard'] === $expected)
            ->assertViewHas('members', fn ($members) => $members->total() === $expected)
            ->assertViewHas('breakdown', fn ($rows) => $rows->sum('count') === $expected)
            ->assertViewHas('trendData', fn ($trend) => array_sum($trend['counts']) === $expected);
        $export = $this->get(route('membership.export', ['type' => 'json', ...$filters, 'page' => 2]));
        expect(json_decode($export->streamedContent(), true))->toHaveCount($expected);
    } finally {
        CarbonImmutable::setTestNow();
    }
})->with([
    'all' => [[], 9],
    'today inclusive midnight' => [['period' => 'today'], 2],
    'week Monday to Sunday' => [['period' => 'week'], 4],
    'month' => [['period' => 'month'], 7],
    'custom includes complete final day' => [['period' => 'custom', 'date_from' => '2026-09-21', 'date_to' => '2026-09-25'], 3],
    'empty' => [['period' => 'custom', 'date_from' => '2026-01-01', 'date_to' => '2026-01-01'], 0],
]);

test('trend fills missing days and aggregates months without changing totals', function (string $interval, array $labels, array $counts) {
    MembershipFactory::new()->create(['created_at' => '2026-01-31 12:00:00']);
    MembershipFactory::new()->count(2)->create(['created_at' => '2026-02-02 12:00:00']);

    $this->get(route('membership.index', ['period' => 'custom', 'date_from' => '2026-01-31', 'date_to' => '2026-02-02', 'trend' => $interval]))
        ->assertViewHas('trendData', ['labels' => $labels, 'counts' => $counts, 'interval' => $interval]);
})->with([
    ['daily', ['2026-01-31', '2026-02-01', '2026-02-02'], [1, 0, 2]],
    ['monthly', ['2026-01', '2026-02'], [1, 2]],
]);

test('invalid date filters are rejected on dashboard and export', function (string $route, array $filters, string $error) {
    $this->getJson(route($route, ['type' => 'json', ...$filters]))->assertUnprocessable()->assertJsonValidationErrors($error);
})->with(['membership.index', 'membership.export'])->with([
    [['period' => 'bad'], 'period'],
    [['period' => 'custom'], 'date_from'],
    [['period' => 'custom', 'date_from' => '2026-02-30', 'date_to' => '2026-03-01'], 'date_from'],
    [['period' => 'custom', 'date_from' => '2026-03-02', 'date_to' => '2026-03-01'], 'date_to'],
    [['trend' => 'bad'], 'trend'],
]);

test('date selections survive pagination member navigation and exports', function () {
    $members = MembershipFactory::new()->count(12)->create(['created_at' => '2026-09-25 12:00:00']);
    $filters = ['period' => 'custom', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'trend' => 'monthly'];
    $this->get(route('membership.index', $filters))
        ->assertSee(route('membership.show', ['membership' => $members->last(), 'filters' => $filters]))
        ->assertSee(route('membership.export', ['type' => 'xlsx', ...$filters]))
        ->assertSee(route('membership.index', [...$filters, 'page' => 2]));
    $this->get(route('membership.show', ['membership' => $members->first(), 'filters' => $filters]))->assertViewHas('filters', $filters);
    $this->get(route('membership.edit', ['membership' => $members->first(), 'filters' => $filters]))->assertViewHas('filters', $filters);
});

test('long daily ranges use monthly buckets and retain zero months', function () {
    MembershipFactory::new()->create(['created_at' => '2024-02-29 23:59:59']);
    $this->get(route('membership.index', ['period' => 'custom', 'date_from' => '2024-01-01', 'date_to' => '2025-02-28', 'trend' => 'daily']))
        ->assertViewHas('trendData', fn ($trend) => $trend['interval'] === 'monthly' && count($trend['labels']) === 14 && $trend['counts'][1] === 1 && array_sum($trend['counts']) === 1);
});

test('leap day custom range includes the final second only within that day', function () {
    MembershipFactory::new()->create(['created_at' => '2024-02-29 23:59:59']);
    MembershipFactory::new()->create(['created_at' => '2024-03-01 00:00:00']);
    $this->get(route('membership.index', ['period' => 'custom', 'date_from' => '2024-02-29', 'date_to' => '2024-02-29']))
        ->assertViewHas('trendData', ['labels' => ['2024-02-29'], 'counts' => [1], 'interval' => 'daily']);
});
