<header class="topbar">
    <button class="hamburger" id="hamburger" aria-label="Open menu"><span></span></button>

    <form class="search-box" action="{{ route(request()->routeIs('excos.*') ? 'excos.index' : 'membership.index') }}" method="GET">
        @foreach (['lga', 'ward', 'pu', 'has_voters_card', 'gender', 'period', 'date_from', 'date_to', 'trend', 'sort', 'direction', 'per_page'] as $filter)
            @if (request()->filled($filter))
                <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
            @endif
        @endforeach
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" aria-label="{{ request()->routeIs('excos.*') ? 'Search Excos by name or ID' : 'Search members by name, phone or ID' }}" value="{{ request('q') }}" placeholder="{{ request()->routeIs('excos.*') ? 'Search Excos by name or ID...' : 'Search members by name, phone or ID...' }}" />
    </form>

    <div class="topbar-right">
        <div class="bell">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            @if (($unreadNotifications ?? 0) > 0)
                <span class="dot">{{ $unreadNotifications }}</span>
            @endif
        </div>
        <div class="profile">
            <div class="avatar">{{ \Illuminate\Support\Str::of(auth()->user()->name ?? 'Admin User')->explode(' ')->map(fn ($n) => $n[0] ?? '')->take(2)->implode('') }}</div>
            <div class="profile-text">
                <b>{{ auth()->user()->name ?? 'Admin User' }}</b>
                <span>{{ auth()->user()->role ?? 'State Admin' }}</span>
            </div>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
    </div>
</header>
