<?php

namespace App\Support;

use App\Models\Membership;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MembershipDates
{
    /** @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable} */
    public static function range(Request $request): array
    {
        $request->validate([
            'period' => ['nullable', 'in:all,today,week,month,custom'],
            'date_from' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:2100-12-31'],
            'date_to' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'after_or_equal:date_from', 'before_or_equal:2100-12-31'],
            'trend' => ['nullable', 'in:daily,monthly'],
        ]);
        $today = CarbonImmutable::today();

        return match ($request->input('period')) {
            'today' => [$today, $today->addDay()],
            'week' => [$today->startOfWeek(1), $today->startOfWeek(1)->addWeek()],
            'month' => [$today->startOfMonth(), $today->startOfMonth()->addMonth()],
            'custom' => [CarbonImmutable::parse($request->date_from)->startOfDay(), CarbonImmutable::parse($request->date_to)->startOfDay()->addDay()],
            default => [null, null],
        };
    }

    /**
     * @param  Builder<Membership>  $query
     * @return array{labels: list<string>, counts: list<int>, interval: string}
     */
    public static function trend(Builder $query, Request $request): array
    {
        [$start, $end] = self::range($request);
        $days = (clone $query)->whereNotNull('created_at')->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->orderBy('day')->pluck('total', 'day');
        if ($days->isEmpty()) {
            return ['labels' => [], 'counts' => [], 'interval' => $request->input('trend') ?: 'daily'];
        }
        $start ??= CarbonImmutable::parse($days->keys()->first());
        $last = $end?->subDay() ?? CarbonImmutable::parse($days->keys()->last());
        $interval = $request->input('trend') ?: ($start->diffInDays($last) > 90 ? 'monthly' : 'daily');
        if ($start->diffInDays($last) > 366) {
            $interval = 'monthly';
        }
        $format = $interval === 'monthly' ? 'Y-m' : 'Y-m-d';
        $totals = [];
        foreach ($days as $day => $count) {
            $key = CarbonImmutable::parse($day)->format($format);
            $totals[$key] = ($totals[$key] ?? 0) + (int) $count;
        }
        $cursor = $interval === 'monthly' ? $start->startOfMonth() : $start;
        $labels = [];
        $counts = [];
        while ($cursor <= $last) {
            $key = $cursor->format($format);
            $labels[] = $key;
            $counts[] = $totals[$key] ?? 0;
            $cursor = $interval === 'monthly' ? $cursor->addMonth() : $cursor->addDay();
        }

        return compact('labels', 'counts', 'interval');
    }
}
