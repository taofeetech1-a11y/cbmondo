@extends('dashboard.app')

@section('title', 'Membership Dashboard — CBM Ondo')

@section('content')

    <div class="page-head">
        <div>
            <span class="eyebrow">CITY BOY MOVEMENT · ONDO STATE</span><h1>Membership Dashboard</h1>
            <p>View and analyse City Boys Movement registrations across Ondo State.</p>
        </div>
        <div class="date">{{ now()->format('D, j M Y') }}</div>
    </div>

    @include('dashboard.partials.overview')
    <section class="panel trend-panel">
        <div class="panel-head"><div><span class="eyebrow">REGISTRATION ACTIVITY</span><h3>Registration trends</h3></div>@can('export-data')<button type="button" class="btn btn-outline" id="export-trend" disabled>Download trend PNG ↓</button>@endcan</div>
        <p class="panel-description">{{ $dateScope }} · {{ config('app.timezone') }} · {{ ucfirst($trendData['interval']) }} totals for current filters and search. Ranges over 366 days use monthly totals.</p>
        @if (count($trendData['labels']))
            <div style="height:300px;position:relative"><canvas id="trendChart" role="img" aria-label="{{ ucfirst($trendData['interval']) }} registration counts"></canvas></div>
            <details class="chart-details"><summary>View trend data</summary><dl>@foreach ($trendData['labels'] as $index => $label)<div><dt>{{ $label }}</dt><dd>{{ number_format($trendData['counts'][$index]) }}</dd></div>@endforeach</dl></details>
        @else
            <div class="empty-state"><h4>No registrations in this selection</h4><p>Try a different date range or filter.</p></div>
        @endif
    </section>
    @if ($errors->any())
        <div class="member-notice member-error" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif
    @if (session('status'))
        <div class="member-notice" role="status">{{ session('status') }}</div>
    @endif
    <div class="section-heading filter-heading"><div><span class="eyebrow">EXPLORE YOUR MEMBERSHIP</span><h2>Find the people behind the numbers.</h2></div></div>
    {{-- Filters --}}
    <form class="filters" method="GET" action="{{ route('membership.index') }}">
        <input type="hidden" name="q" value="{{ request('q') }}">
        @foreach (['sort', 'direction', 'per_page'] as $control)
            @if(request()->filled($control))<input type="hidden" name="{{ $control }}" value="{{ request($control) }}">@endif
        @endforeach
        <div class="field"><label for="period">Registration dates</label><select name="period" id="period">
            @foreach (['all' => 'All dates', 'today' => 'Today', 'week' => 'This week (Monday–Sunday)', 'month' => 'This month', 'custom' => 'Custom range'] as $value => $label)<option value="{{ $value }}" @selected(request('period', 'all') === $value)>{{ $label }}</option>@endforeach
        </select></div>
        <div class="field date-range-field" @if(request('period') !== 'custom') hidden @endif><label for="date_from">From</label><input type="date" id="date_from" name="date_from" min="1900-01-01" max="2100-12-31" value="{{ request('date_from') }}" @disabled(request('period') !== 'custom') required></div>
        <div class="field date-range-field" @if(request('period') !== 'custom') hidden @endif><label for="date_to">Through</label><input type="date" id="date_to" name="date_to" min="1900-01-01" max="2100-12-31" value="{{ request('date_to') }}" @disabled(request('period') !== 'custom') required></div>
        <div class="field"><label for="trend">Trend grouping</label><select id="trend" name="trend"><option value="">Automatic</option><option value="daily" @selected(request('trend') === 'daily')>Daily</option><option value="monthly" @selected(request('trend') === 'monthly')>Monthly</option></select></div>
        <div class="field">
            <label>State</label>
            <select disabled>
                <option>Ondo State</option>
            </select>
        </div>
        <div class="field">
            <label for="lga">Local Government (LGA)</label>
            <select id="lga" name="lga" onchange="this.form.submit()">
                <option value="">All LGAs</option>
                @foreach ($lgas as $lga)
                    <option value="{{ $lga->id }}" @selected((int) request('lga') === $lga->id)>{{ $lga->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="ward">Ward</label>
            <select id="ward" name="ward" onchange="this.form.submit()" @disabled($wards->isEmpty())>
                <option value="">All Wards</option>
                @foreach ($wards as $ward)
                    <option value="{{ $ward->id }}" @selected((int) request('ward') === $ward->id)>{{ $ward->code . ' ' . $ward->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="pu">Polling Unit</label>
            <select id="pu" name="pu" @disabled($pollingUnits->isEmpty())>
                <option value="">All Units</option>
                @foreach ($pollingUnits as $pu)
                    <option value="{{ $pu->id }}" @selected((int) request('pu') === $pu->id)>{{ $pu->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" onchange="this.form.submit()">
                <option value="">All genders</option>
                <option value="male" @selected(request('gender') === 'male')>Male</option>
                <option value="female" @selected(request('gender') === 'female')>Female</option>
            </select>
        </div>
        <div class="field">
            <label for="has_voters_card">Voter's Card</label>
            <select id="has_voters_card" name="has_voters_card" onchange="this.form.submit()">
                <option value="">All Members</option>
                <option value="yes" @selected(request('has_voters_card') === 'yes')>Has Voter's Card</option>
                <option value="no" @selected(request('has_voters_card') === 'no')>No Voter's Card</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
            </svg>
            Apply Filter
        </button>
        <a href="{{ route('membership.index') }}" class="btn btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10" />
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10" />
            </svg>
            Reset
        </a>
    </form>

    {{-- Table --}}
    @if (count($filterChips))
        <nav class="filter-chips" aria-label="Active filters">
            @foreach ($filterChips as $chip)<a href="{{ $chip['url'] }}" aria-label="Remove {{ $chip['label'] }}">{{ $chip['label'] }} <span aria-hidden="true">×</span></a>@endforeach
            <a href="{{ route('membership.index', request()->only(['sort', 'direction', 'per_page', 'trend'])) }}">Clear all filters</a>
        </nav>
    @endif
    <div class="table-panel">
        <div class="table-head">
            <div>
                <h3>Member directory</h3>
                <div class="sub">{{ number_format($members->total()) }} matching records · Scroll across to view all details</div>
            </div>
            <div class="table-actions">
                @can('import-members')<a class="btn btn-outline" href="{{ route('membership.import') }}">Import members ↑</a>@endcan
                @can('export-data')<button type="button" class="btn btn-primary" popovertarget="member-export-menu" data-member-actions>Export members <span aria-hidden="true">↓</span></button>
                <div id="member-export-menu" class="member-actions-menu" popover>
                    <div class="member-actions-title">All {{ number_format($members->total()) }} matching members</div>
                    @foreach (['xlsx' => 'Excel (.xlsx)', 'csv' => 'CSV (.csv)', 'json' => 'JSON (.json)', 'sql' => 'MySQL (.sql)'] as $type => $label)
                        <a href="{{ route('membership.export', ['type' => $type, ...request()->only(['lga', 'ward', 'pu', 'has_voters_card', 'gender', 'period', 'date_from', 'date_to', 'trend', 'sort', 'direction', 'per_page', 'q'])]) }}">{{ $label }} <span aria-hidden="true">↓</span></a>
                    @endforeach
                </div>@endcan
            </div>
        </div>

        @can('download-cards')<form id="bulk-card-form" method="POST" action="{{ route('membership.cards.download', request()->only(['lga', 'ward', 'pu', 'has_voters_card', 'gender', 'period', 'date_from', 'date_to', 'trend', 'q', 'sort', 'direction'])) }}" class="bulk-card-controls" data-selection-key="{{ json_encode(request()->only(['lga', 'ward', 'pu', 'has_voters_card', 'gender', 'period', 'date_from', 'date_to', 'q'])) }}">
            @csrf
            <div><strong>Download ID cards</strong><p id="card-selection-count" aria-live="polite">0 members selected</p></div>
            <label>Members <select name="scope" id="card-scope"><option value="selected">Selected members</option><option value="filtered">All {{ number_format($members->total()) }} matching members</option></select></label>
            <label>Format <select name="format"><option value="zip">ZIP — individual PNG cards</option><option value="pdf">PDF — A4 print sheets</option></select></label>
            <button type="submit" class="btn btn-primary">Download cards</button>
            <div class="bulk-card-help">Select up to 500 members, or choose all matching members for ZIP. PDF: up to 200 cards, 8 per A4 page. Print at 100% size. ZIP includes all matching cards across pages; large downloads take longer.</div>
            <div id="card-download-message" class="bulk-card-help" role="status"></div>
            <div id="offpage-card-selection"></div>
        </form>
        <div class="card-selection-controls"><label><input type="checkbox" id="select-page-cards"> Select this page</label><button type="button" id="clear-card-selection" class="btn btn-outline">Clear selection</button></div>@endcan
        <div class="directory-controls">
            <form method="GET" action="{{ route('membership.index') }}" class="directory-settings">
                @foreach (request()->only(['lga', 'ward', 'pu', 'has_voters_card', 'gender', 'period', 'date_from', 'date_to', 'trend', 'q']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                <label>Sort by <select name="sort">@foreach (\App\Support\MembershipTable::SORTS as $key => $label)<option value="{{ $key }}" @selected((request('sort') ?: 'created_at') === $key)>{{ $label }}</option>@endforeach</select></label>
                <label>Order <select name="direction"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('direction') === 'asc')>Ascending</option></select></label>
                <label>Rows per page <select name="per_page">@foreach ([10, 25, 50, 100] as $size)<option value="{{ $size }}" @selected((request('per_page') ?: 10) == $size)>{{ $size }}</option>@endforeach</select></label>
                <button class="btn btn-outline" type="submit">Update table</button>
            </form>
            <button class="btn btn-outline" type="button" popovertarget="directory-columns" data-member-actions>Columns</button>
            <div id="directory-columns" class="member-actions-menu column-menu" popover>
                <div class="member-actions-title">Visible columns</div>
                <p>Name and Action stay visible. Exports include all columns.</p>
                @foreach ([0 => 'S/N', 1 => 'CBM ID', 3 => 'Delimitation Code', 4 => 'CBM Delimitation Code', 5 => 'Phone', 6 => 'Gender', 7 => 'LGA', 8 => 'Ward', 9 => 'Polling Unit', 10 => 'Support Us', 11 => 'Contact?', 12 => 'PVC Local?', 13 => 'Registered'] as $column => $label)
                    <label><input type="checkbox" data-directory-column="{{ $column }}" checked> {{ $label }}</label>
                @endforeach
                <button type="button" class="btn btn-outline" id="reset-directory-columns">Show all columns</button>
            </div>
        </div>

        <div class="table-scroll" tabindex="0" role="region" aria-label="Member records, scroll horizontally to view all columns">
            <table id="member-directory">
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th scope="col" aria-sort="{{ (request('sort') ?: 'created_at') === 'cbm_id' ? ((request('direction') ?: 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ route('membership.index', [...request()->except('page'), 'sort' => 'cbm_id', 'direction' => (request('sort') ?: 'created_at') === 'cbm_id' && (request('direction') ?: 'desc') === 'asc' ? 'desc' : 'asc']) }}">CBM ID <span aria-hidden="true">{{ (request('sort') ?: 'created_at') === 'cbm_id' ? ((request('direction') ?: 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                        <th scope="col" aria-sort="{{ (request('sort') ?: 'created_at') === 'name' ? ((request('direction') ?: 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ route('membership.index', [...request()->except('page'), 'sort' => 'name', 'direction' => (request('sort') ?: 'created_at') === 'name' && (request('direction') ?: 'desc') === 'asc' ? 'desc' : 'asc']) }}">Name <span aria-hidden="true">{{ (request('sort') ?: 'created_at') === 'name' ? ((request('direction') ?: 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                        <th>Delimitation Code</th>
                        <th>CBM Delimitation Code</th>
                        <th scope="col" aria-sort="{{ (request('sort') ?: 'created_at') === 'phone' ? ((request('direction') ?: 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ route('membership.index', [...request()->except('page'), 'sort' => 'phone', 'direction' => (request('sort') ?: 'created_at') === 'phone' && (request('direction') ?: 'desc') === 'asc' ? 'desc' : 'asc']) }}">Phone <span aria-hidden="true">{{ (request('sort') ?: 'created_at') === 'phone' ? ((request('direction') ?: 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                        <th scope="col" aria-sort="{{ (request('sort') ?: 'created_at') === 'gender' ? ((request('direction') ?: 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ route('membership.index', [...request()->except('page'), 'sort' => 'gender', 'direction' => (request('sort') ?: 'created_at') === 'gender' && (request('direction') ?: 'desc') === 'asc' ? 'desc' : 'asc']) }}">Gender <span aria-hidden="true">{{ (request('sort') ?: 'created_at') === 'gender' ? ((request('direction') ?: 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                        <th>LGA</th>
                        <th>Ward</th>
                        <th>Polling Unit</th>
                        <th scope="col" aria-sort="{{ (request('sort') ?: 'created_at') === 'support_us' ? ((request('direction') ?: 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ route('membership.index', [...request()->except('page'), 'sort' => 'support_us', 'direction' => (request('sort') ?: 'created_at') === 'support_us' && (request('direction') ?: 'desc') === 'asc' ? 'desc' : 'asc']) }}">Support Us <span aria-hidden="true">{{ (request('sort') ?: 'created_at') === 'support_us' ? ((request('direction') ?: 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                        <th>Contact?</th>
                        <th>PVC Local?</th>
                        <th scope="col" aria-sort="{{ (request('sort') ?: 'created_at') === 'created_at' ? ((request('direction') ?: 'desc') === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ route('membership.index', [...request()->except('page'), 'sort' => 'created_at', 'direction' => (request('sort') ?: 'created_at') === 'created_at' && (request('direction') ?: 'desc') === 'asc' ? 'desc' : 'asc']) }}">Registered <span aria-hidden="true">{{ (request('sort') ?: 'created_at') === 'created_at' ? ((request('direction') ?: 'desc') === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                        <th class="member-actions-cell" scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>{{ $loop->iteration + ($members->currentPage() - 1) * $members->perPage() }}</td>
                            <td>{{ $member->cbm_id }}</td>
                            <td><label class="card-member-select">@can('download-cards')<input type="checkbox" name="ids[]" value="{{ $member->id }}" form="bulk-card-form" data-card-member aria-label="Select {{ $member->name }} for ID cards">@endcan {{ $member->name }}</label></td>
                            <td>{{ $member->delimitation_code ?? '———' }}</td>
                            <td>{{ $member->cbm_delimitation_code ?? '———' }}</td>
                            <td>{{ $member->phone }}</td>
                            <td>{{ ucfirst($member->gender) }}</td>
                            <td>{{ $member->lgaInfo->name ?? '—' }}</td>
                            <td>{{ $member->wardInfo->name ?? '—' }}</td>
                            <td>{{ $member->puInfo->name ?? '—' }}</td>
                            <td><span
                                    class="badge {{ $member->support_us === 'yes' ? 'badge-yes' : 'badge-no' }}">{{ ucfirst($member->support_us) }}</span>
                            </td>
                            <td><span
                                    class="badge {{ $member->want_to_be_contacted === 'yes' ? 'badge-yes' : 'badge-no' }}">{{ ucfirst($member->want_to_be_contacted) }}</span>
                            </td>
                            <td><span
                                    class="badge {{ $member->same_address === 'yes' ? 'badge-yes' : 'badge-no' }}">{{ $member->same_address === 'yes' ? ucfirst($member->same_address) : 'No'  }}</span>
                            </td>
                            <td>{{ $member->created_at->format('d M Y') }}</td>
                            <td class="member-actions-cell">
                                <button type="button" class="member-actions-toggle" popovertarget="member-actions-{{ $member->id }}"
                                    aria-label="Actions for {{ $member->name }}" data-member-actions><span aria-hidden="true">•••</span></button>
                                <div id="member-actions-{{ $member->id }}" class="member-actions-menu" popover>
                                    <div class="member-actions-title">{{ $member->name }}</div>
                                    <a href="{{ route('membership.show', ['membership' => $member, 'filters' => request()->only(['lga', 'ward', 'pu', 'has_voters_card', 'gender', 'period', 'date_from', 'date_to', 'trend', 'sort', 'direction', 'per_page', 'q', 'page'])]) }}">View <span aria-hidden="true">↗</span></a>
                                    @can('update-members')<a href="{{ route('membership.edit', ['membership' => $member, 'filters' => request()->only(['lga', 'ward', 'pu', 'has_voters_card', 'gender', 'period', 'date_from', 'date_to', 'trend', 'sort', 'direction', 'per_page', 'q', 'page'])]) }}">Update <span aria-hidden="true">✎</span></a>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" style="text-align:center;color:var(--ink-soft);padding:1.5rem;">No members
                                match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-foot">
            <div>
                Showing {{ $members->firstItem() ?? 0 }} to {{ $members->lastItem() ?? 0 }} of
                {{ number_format($members->total()) }} records
            </div>
            @if ($members->hasPages())
                <div class="pager">
                    @if ($members->onFirstPage())
                        <button class="pager-btn wide" disabled>Previous</button>
                    @else
                        <a class="pager-btn wide" href="{{ $members->previousPageUrl() }}">Previous</a>
                    @endif

                    <span class="pager-page-info">Page {{ $members->currentPage() }} of {{ $members->lastPage() }}</span>

                    @php
                        $start = max(1, $members->currentPage() - 2);
                        $end = min($members->lastPage(), $members->currentPage() + 2);
                    @endphp

                    @if ($start > 1)
                        <a class="pager-btn page-num" href="{{ $members->url(1) }}">1</a>
                        @if ($start > 2)
                            <span class="pager-btn page-num" style="border:none;cursor:default;">&hellip;</span>
                        @endif
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        <a class="pager-btn page-num {{ $page === $members->currentPage() ? 'active' : '' }}"
                            href="{{ $members->url($page) }}">{{ $page }}</a>
                    @endfor

                    @if ($end < $members->lastPage())
                        @if ($end < $members->lastPage() - 1)
                            <span class="pager-btn page-num" style="border:none;cursor:default;">&hellip;</span>
                        @endif
                        <a class="pager-btn page-num"
                            href="{{ $members->url($members->lastPage()) }}">{{ $members->lastPage() }}</a>
                    @endif

                    @if ($members->hasMorePages())
                        <a class="pager-btn wide" href="{{ $members->nextPageUrl() }}">Next</a>
                    @else
                        <button class="pager-btn wide" disabled>Next</button>
                    @endif
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')



    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
    <script>
        document.getElementById('period').addEventListener('change', (event) => {
            document.querySelectorAll('.date-range-field').forEach(field => {
                field.hidden = event.target.value !== 'custom';
                field.querySelector('input').disabled = field.hidden;
            });
        });
    </script>
    @if (count($trendData['labels']))
        <script>
            const trendChart = new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: { labels: @json($trendData['labels']), datasets: [{ label: 'Registrations', data: @json($trendData['counts']), borderColor: '#159b78', backgroundColor: 'rgba(21,155,120,.12)', fill: true, tension: .15, pointRadius: 3 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxTicksLimit: 12 } } } },
            });
            const trendButton = document.getElementById('export-trend');
            if (trendButton) trendButton.disabled = false;
            trendButton?.addEventListener('click', () => {
                const canvas = document.createElement('canvas');
                canvas.width = 1400; canvas.height = 650 + {{ count($chartScope) * 24 }};
                const chart = new Chart(canvas, {
                    type: 'line', data: structuredClone(trendChart.data),
                    options: { responsive: false, animation: false, devicePixelRatio: 1, layout: { padding: 30 }, plugins: {
                        legend: { display: false }, title: { display: true, text: @json('CBM Ondo · '.ucfirst($trendData['interval']).' registration trends'), font: { size: 24 } },
                        subtitle: { display: true, text: @json($chartScope), padding: 18, font: { size: 16 } },
                    }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxTicksLimit: 14 } } } },
                    plugins: [{ id: 'whiteBackground', beforeDraw(chart) { const ctx = chart.ctx; ctx.save(); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, chart.width, chart.height); ctx.restore(); } }],
                });
                const link = document.createElement('a'); link.download = 'cbm-registration-trends.png'; link.href = chart.toBase64Image(); link.click(); chart.destroy();
            });
        </script>
    @endif


    @if ($breakdown->isNotEmpty())
        <script>
            const breakdownChart = new Chart(document.getElementById('breakdownChart'), {
                type: 'bar',
                data: {
                    labels: @json($breakdown->pluck('name')),
                    datasets: [{
                        data: @json($breakdown->pluck('count')),
                        backgroundColor: ['#159b78', '#4777dc', '#9870db', '#e9a23b', '#34a6af', '#df769a'],
                        borderRadius: 6,
                        maxBarThickness: 22,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: '#eef0f5'
                            },
                            ticks: {
                                color: '#5c6b85',
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#5c6b85',
                                font: {
                                    size: 11
                                }
                            }
                        },
                    },
                },
            });
            const exportChartButton = document.getElementById('export-chart');
            if (exportChartButton) exportChartButton.disabled = false;
            exportChartButton?.addEventListener('click', () => {
                const canvas = document.createElement('canvas');
                canvas.width = 1400;
                canvas.height = Math.max(500, breakdownChart.data.labels.length * 42 + 160 + {{ count($chartScope) * 24 }});
                const chart = new Chart(canvas, {
                    type: 'bar',
                    data: structuredClone(breakdownChart.data),
                    options: {
                        responsive: false, animation: false, devicePixelRatio: 1, indexAxis: 'y',
                        layout: { padding: 30 },
                        plugins: {
                            legend: { display: false },
                            title: { display: true, text: @json('CBM Ondo · Registrations by '.$breakdownLabel), font: { size: 24 }, padding: 16 },
                            subtitle: { display: true, text: @json([number_format($stats['total']).' matching registrations · '.now()->format('d M Y'), ...$chartScope]), font: { size: 16 }, padding: 16 },
                        },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { ticks: { autoSkip: false, font: { size: 14 } } } },
                    },
                    plugins: [{ id: 'whiteBackground', beforeDraw(chart) {
                        const context = chart.ctx;
                        context.save();
                        context.fillStyle = '#ffffff';
                        context.fillRect(0, 0, chart.width, chart.height);
                        context.restore();
                    } }],
                });
                const link = document.createElement('a');
                link.download = 'cbm-registrations-{{ now()->format('Y-m-d') }}.png';
                link.href = chart.toBase64Image();
                link.click();
                chart.destroy();
            });
        </script>
    @endif


@endpush
