@php
    $executiveSearch = request()->routeIs('executives.*', 'executive-positions.*');
    $searchRoute = $executiveSearch ? 'executives.index' : (request()->routeIs('excos.*') ? 'excos.index' : 'membership.index');
    $searchLabel = $executiveSearch ? 'Search executives by name or CBM ID' : (request()->routeIs('excos.*') ? 'Search Excos by name or ID' : 'Search members by name, phone or ID');
@endphp
<header class="topbar">
    <button type="button" class="hamburger" id="hamburger" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><span></span></button>

    <form class="search-box" action="{{ route($searchRoute) }}" method="GET">
        @foreach (['level', 'lga', 'ward', 'pu', 'has_voters_card', 'gender', 'period', 'date_from', 'date_to', 'trend', 'sort', 'direction', 'per_page'] as $filter)
            @if (request()->filled($filter))
                <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
            @endif
        @endforeach
        <button type="submit" class="topbar-search-submit" aria-label="Search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
        <input type="text" name="q" aria-label="{{ $searchLabel }}" value="{{ request('q') }}" placeholder="{{ $searchLabel }}…" />
    </form>

    <div class="topbar-right">
        <button type="button" class="profile profile-toggle" popovertarget="staff-account-menu" data-member-actions aria-label="Account options">
            <span class="avatar">{{ \Illuminate\Support\Str::of(auth()->user()->name ?? 'Admin User')->explode(' ')->map(fn ($n) => $n[0] ?? '')->take(2)->implode('') }}</span>
            <span class="profile-text">
                <b>{{ auth()->user()->name ?? 'Admin User' }}</b>
                <span>{{ \App\Models\User::ROLES[auth()->user()->role] ?? 'Staff' }}</span>
            </span>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="staff-account-menu" class="member-actions-menu" popover>
            <div class="member-actions-title">{{ auth()->user()->name }}</div>
            <a href="{{ route('profile.edit') }}">My account</a>
            @can('manage-users')
                <a href="{{ route('staff-users.index') }}">Staff accounts</a>
            @endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="account-logout">Log out</button>
            </form>
        </div>
    </div>
</header>
