@extends('dashboard.app')

@section('title', 'Membership Dashboard — CBM Ondo')

@section('content')

    <div class="page-head">
        <div>
            <h1>Membership Dashboard</h1>
            <p>View and analyse City Boys Movement registrations across Ondo State.</p>
        </div>
        <div class="date" id="todayDate"></div>
    </div>

    {{-- Filters --}}
    <form class="filters" method="GET" action="{{ route('membership.index') }}">
        <div class="field">
            <label for="state">State</label>
            <select id="state" name="state">
                @foreach ($filters['states'] as $state)
                    <option value="{{ $state }}" @selected($filters['selected']['state'] === $state)>{{ $state }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="lga">Local Government (LGA)</label>
            <select id="lga" name="lga">
                @foreach ($filters['lgas'] as $lga)
                    <option value="{{ $lga->id }}" @selected($filters['selected']['lga'] === $lga)>{{ $lga->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="ward">Ward</label>
            <select id="ward" name="ward">
                @foreach ($filters['wards'] as $ward)
                    <option value="{{ $ward }}" @selected($filters['selected']['ward'] === $ward)>{{ $ward }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="polling_unit">Polling Unit</label>
            <select id="polling_unit" name="polling_unit">
                <option value="" @selected($filters['selected']['pollingUnit'] === null)>All Units</option>
                @foreach ($filters['pollingUnits'] as $unit)
                    <option value="{{ $unit }}" @selected($filters['selected']['pollingUnit'] === $unit)>{{ $unit }}</option>
                @endforeach
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

    {{-- Stat cards --}}
    <div class="stats">
        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon ic-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg></div>
                <div>
                    <div class="stat-val">{{ number_format($stats['total']) }}</div>
                    <div class="stat-label">Total Registrations</div>
                </div>
            </div>
            <div class="stat-sub" style="color:var(--green)">
                @if ($stats['totalGrowth'] >= 0)
                    &uarr; +{{ $stats['totalGrowth'] }}%
                @else
                    &darr; {{ $stats['totalGrowth'] }}%
                @endif vs last month
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon ic-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1" />
                    </svg></div>
                <div>
                    <div class="stat-val">{{ number_format($stats['male']) }}</div>
                    <div class="stat-label">Male</div>
                </div>
            </div>
            <div class="stat-bar"><span style="width:{{ $stats['malePct'] }}%;background:var(--blue)"></span></div>
            <div class="stat-sub" style="color:var(--ink-soft)">{{ $stats['malePct'] }}%</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon ic-pink"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1" />
                    </svg></div>
                <div>
                    <div class="stat-val">{{ number_format($stats['female']) }}</div>
                    <div class="stat-label">Female</div>
                </div>
            </div>
            <div class="stat-bar"><span style="width:{{ $stats['femalePct'] }}%;background:var(--pink)"></span></div>
            <div class="stat-sub" style="color:var(--ink-soft)">{{ $stats['femalePct'] }}%</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon ic-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2" />
                        <line x1="2" y1="10" x2="22" y2="10" />
                    </svg></div>
                <div>
                    <div class="stat-val">{{ number_format($stats['withVoterCard']) }}</div>
                    <div class="stat-label">With Voter's Card</div>
                </div>
            </div>
            <div class="stat-bar"><span style="width:{{ $stats['voterCardPct'] }}%;background:var(--green)"></span></div>
            <div class="stat-sub" style="color:var(--ink-soft)">{{ $stats['voterCardPct'] }}%</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon ic-amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg></div>
                <div>
                    <div class="stat-val">{{ number_format($stats['active']) }}</div>
                    <div class="stat-label">Active Members</div>
                </div>
            </div>
            <div class="stat-bar"><span style="width:{{ $stats['activePct'] }}%;background:var(--amber)"></span></div>
            <div class="stat-sub" style="color:var(--ink-soft)">{{ $stats['activePct'] }}%</div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="charts-row">
        <div class="panel">
            <div class="panel-head">
                <h3>Registrations Trend</h3>
                <select id="trendRange">
                    <option value="this_year">This Year</option>
                    <option value="last_year">Last Year</option>
                </select>
            </div>
            <div class="chart-box"><canvas id="trendChart"></canvas></div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Registrations by LGA</h3>
            </div>
            <div id="lgaList">
                @foreach ($lgaBreakdown as $row)
                    <div class="lga-row">
                        <div class="lga-name">{{ $row['name'] }}</div>
                        <div class="lga-track"><span style="width:{{ $row['percent'] }}%"></span></div>
                        <div class="lga-val">{{ number_format($row['count']) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Registrations by Gender</h3>
            </div>
            <div class="donut-wrap">
                <div class="donut-center">
                    <canvas id="genderChart"></canvas>
                    <div class="mid"><b>{{ number_format($stats['total']) }}</b><span>Total</span></div>
                </div>
                <div class="legend">
                    <div class="legend-item"><span class="legend-dot" style="background:var(--blue)"></span>Male
                        ({{ $stats['malePct'] }}%)</div>
                    <div class="legend-item"><span class="legend-dot" style="background:var(--pink)"></span>Female
                        ({{ $stats['femalePct'] }}%)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-panel">
        <div class="table-head">
            <div>
                <h3>Member List @if ($filters['selected']['ward'])
                        ({{ $filters['selected']['ward'] }} Ward)
                    @endif
                </h3>
                <div class="sub">{{ number_format($members->total()) }} total records</div>
            </div>
            <div class="table-actions">
                <form class="mini-search" method="GET" action="{{ route('membership.index') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="text" name="table_q" value="{{ request('table_q') }}"
                        placeholder="Search in this result..." />
                </form>
                <a href="{{ route('membership.export', ['type' => 'excel'] + request()->query()) }}" class="btn btn-xls">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('membership.export', ['type' => 'pdf'] + request()->query()) }}" class="btn btn-pdf">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Export PDF
                </a>
                <button type="button" class="btn btn-print" onclick="window.print()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9" />
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                        <rect x="6" y="14" width="12" height="8" />
                    </svg>
                    Print
                </button>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>S/N</th>
                        <th>Membership ID</th>
                        <th>Full Name</th>
                        <th>Phone Number</th>
                        <th>Gender</th>
                        <th>LGA</th>
                        <th>Ward</th>
                        <th>Polling Unit</th>
                        <th>Voter's Card</th>
                        <th>Status</th>
                        <th>Date Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>{{ $loop->iteration + ($members->currentPage() - 1) * $members->perPage() }}</td>
                            <td>{{ $member->membership_id }}</td>
                            <td>{{ $member->full_name }}</td>
                            <td>{{ $member->phone }}</td>
                            <td>{{ $member->gender }}</td>
                            <td>{{ $member->lga }}</td>
                            <td>{{ $member->ward }}</td>
                            <td>{{ $member->polling_unit }}</td>
                            <td><span
                                    class="badge {{ $member->has_voters_card ? 'badge-yes' : 'badge-no' }}">{{ $member->has_voters_card ? 'Yes' : 'No' }}</span>
                            </td>
                            <td><span class="badge badge-active">{{ ucfirst($member->status) }}</span></td>
                            <td>{{ $member->registered_at->format('d M Y') }}</td>
                            <td>
                                {{-- <a href="{{ route('members.show', $member->id) }}" class="eye"
                                    title="View {{ $member->full_name }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </a> --}}
                                <a href="#" class="eye"
                                    title="View {{ $member->full_name }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="text-align:center;color:var(--ink-soft);padding:1.5rem;">No members
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
            {{ $members->onEachSide(1)->links('dashboard.partials.custom') }}
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Data handed off to a.js so the charts stay backend-driven
        // instead of the hardcoded arrays the static mockup used.
        window.dashboardData = {
            trend: @json($trend),
            gender: @json(
                $stats['malePct'] ?? 0
                    ? ['male' => $stats['malePct'], 'female' => $stats['femalePct']]
                    : ['male' => 0, 'female' => 0]
            ),
        };
    </script>
@endpush
