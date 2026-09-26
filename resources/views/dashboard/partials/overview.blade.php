<div class="section-heading">
    <div><span class="eyebrow">MEMBERSHIP AT A GLANCE</span><h2>Every member counts.</h2></div>
    <span class="scope-note">Totals for current filters and search</span>
</div>
<div class="stats">
    <article class="stat-card stat-total">
        <div class="stat-heading"><span>Total registrations</span><span class="stat-symbol" aria-hidden="true">↗</span></div>
        <div class="stat-val">{{ number_format($stats['total']) }}</div>
        <p class="stat-sub">Members matching your selection</p>
    </article>
    <article class="stat-card stat-green">
        <div class="stat-heading"><span>With voter cards</span><span class="stat-symbol" aria-hidden="true">✓</span></div>
        <div class="stat-val">{{ number_format($stats['total'] - $stats['votersCard']) }}</div>
        <p class="stat-sub">{{ $stats['total'] ? 100 - $stats['vCP'] : 0 }}% of matching registrations</p>
    </article>
    <article class="stat-card stat-amber">
        <div class="stat-heading"><span>Without voter cards</span><span class="stat-symbol" aria-hidden="true">◷</span></div>
        <div class="stat-val">{{ number_format($stats['votersCard']) }}</div>
        <p class="stat-sub">{{ $stats['vCP'] }}% of matching registrations</p>
    </article>
    <article class="stat-card stat-violet">
        <div class="stat-heading"><span>Matching records</span><span class="stat-symbol" aria-hidden="true">≡</span></div>
        <div class="stat-val">{{ number_format($members->total()) }}</div>
        <p class="stat-sub">Current filters and search</p>
    </article>
</div>
<div class="insights-grid">
    <section class="panel breakdown-panel">
        <div class="panel-head">
            <div><span class="eyebrow">GEOGRAPHIC OVERVIEW</span><h3>Registrations by {{ $breakdownLabel }}</h3></div>
            <button type="button" id="export-chart" class="btn btn-outline" disabled>Download PNG ↓</button>
        </div>
        <p class="panel-description">Registrations matching all current filters and search.</p>
        @if ($breakdown->isEmpty())
            <div class="empty-state"><span aria-hidden="true">◎</span><h4>No registrations to show</h4><p>Try another location or voter-card selection.</p></div>
        @else
            <div class="chart-scroll" tabindex="0" role="region" aria-label="Registration chart, scroll for more locations">
                <div class="chart-box" style="height:{{ max(220, $breakdown->count() * 36) }}px;">
                    <canvas id="breakdownChart" role="img" aria-label="Registration counts by {{ $breakdownLabel }}"></canvas>
                </div>
            </div>
            <details class="chart-details"><summary>View chart data</summary><dl>
                @foreach ($breakdown as $row)
                    <div><dt>{{ $row['name'] }}</dt><dd>{{ number_format($row['count']) }}</dd></div>
                @endforeach
            </dl></details>
        @endif
    </section>
    <section class="panel voter-panel">
        <div class="panel-head"><div><span class="eyebrow">CURRENT SELECTION</span><h3>Voter-card status</h3></div></div>
        <div class="voter-ring {{ $stats['total'] === 0 ? 'is-empty' : '' }}" style="--card-share: {{ $stats['total'] ? 100 - $stats['vCP'] : 0 }}%;">
            <div><strong>{{ $stats['total'] ? 100 - $stats['vCP'] : 0 }}<small>%</small></strong><span>with voter cards</span></div>
        </div>
        <div class="status-legend">
            <div><span><i class="dot-green"></i>With voter cards</span><b>{{ number_format($stats['total'] - $stats['votersCard']) }}</b></div>
            <div><span><i class="dot-amber"></i>Without voter cards</span><b>{{ number_format($stats['votersCard']) }}</b></div>
        </div>
        <p class="snapshot-note">{{ $stats['total'] ? 'Voter-card status for the current filters and search.' : 'No registrations match the current filters and search.' }}</p>
    </section>
</div>
