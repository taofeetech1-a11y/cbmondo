@props(['trend'])
@php
    $labels = $trend['labels'];
    $counts = $trend['counts'];
    $maximum = max(4, (int) (ceil(max($counts ?: [0]) / 4) * 4));
    $points = [];
    foreach ($counts as $index => $count) {
        $points[] = (44 + $index * 620 / max(1, count($counts) - 1)).','.(220 - $count * 192 / $maximum);
    }
    $line = implode(' ', $points);
@endphp
<section class="canvass-trend" aria-label="Daily membership registration trend">
    <div class="canvass-trend-heading"><h3>Registration trends</h3><span>Last 30 days</span></div>
    <p><strong>{{ number_format(array_sum($counts)) }}</strong> registrations · {{ \Carbon\CarbonImmutable::parse($labels[0])->format('d M') }}–{{ \Carbon\CarbonImmutable::parse(end($labels))->format('d M Y') }}</p>
    <div class="canvass-trend-plot">
        <svg viewBox="0 0 680 274" role="img" aria-label="Daily membership registrations during the last 30 days. {{ array_sum($counts) }} registrations in total. Exact daily counts are listed below.">
            <defs><linearGradient id="ward-trend-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#52ad77" stop-opacity=".22"/><stop offset="100%" stop-color="#52ad77" stop-opacity=".02"/></linearGradient></defs>
            @for($tick = 0; $tick <= 4; $tick++)
                <line x1="44" x2="664" y1="{{ 220 - $tick * 48 }}" y2="{{ 220 - $tick * 48 }}" class="canvass-chart-grid"/>
                <text x="32" y="{{ 225 - $tick * 48 }}" text-anchor="end">{{ $maximum * $tick / 4 }}</text>
            @endfor
            <polygon points="44,220 {{ $line }} 664,220" fill="url(#ward-trend-fill)"/>
            <polyline points="{{ $line }}" fill="none" stroke="#32945e" stroke-width="3" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
            @foreach($counts as $index => $count)
                <circle cx="{{ 44 + $index * 620 / max(1, count($counts) - 1) }}" cy="{{ 220 - $count * 192 / $maximum }}" r="4" fill="#32945e"><title>{{ $labels[$index] }}: {{ $count }} registrations</title></circle>
                @if(in_array($index, [0, 7, 14, 21, 29], true))<text x="{{ 44 + $index * 620 / max(1, count($counts) - 1) }}" y="253" text-anchor="{{ $index === 0 ? 'start' : ($index === 29 ? 'end' : 'middle') }}">{{ \Carbon\CarbonImmutable::parse($labels[$index])->format('d M') }}</text>@endif
            @endforeach
        </svg>
    </div>
    @if(array_sum($counts) === 0)<p class="canvass-footnote">No registrations were recorded during this period. Zero-registration days are included.</p>@endif
    <p class="canvass-footnote">Daily counts use saved membership registration dates, including imported records. Timezone: {{ config('app.timezone') }}.</p>
    <details class="canvass-trend-data"><summary>View daily counts</summary><dl>@foreach($labels as $index => $label)<div><dt>{{ \Carbon\CarbonImmutable::parse($label)->format('d M Y') }}</dt><dd>{{ number_format($counts[$index]) }}</dd></div>@endforeach</dl></details>
</section>
