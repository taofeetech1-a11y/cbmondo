@php
    // Centralised nav config so every page shares the same sidebar and
    // active-state logic. Add new pages here rather than editing the markup.
    // $mainNav = [
    //     ['label' => 'Membership Dashboard', 'route' => 'membership.index', 'icon' => 'grid'],
    //     ['label' => 'All Members',          'route' => 'members.index',    'icon' => 'users'],
    //     ['label' => 'Registrations',        'route' => 'registrations.index', 'icon' => 'bars'],
    //     ['label' => 'Verification',         'route' => 'verification.index', 'icon' => 'check'],
    // ];
    $mainNav = [
        ['label' => 'Membership Dashboard', 'route' => 'membership.index', 'icon' => 'grid'],
        // ['label' => 'All Members',          'route' => 'members.index',    'icon' => 'users'],
        // ['label' => 'Registrations',        'route' => 'registrations.index', 'icon' => 'bars'],
        // ['label' => 'Verification',         'route' => 'verification.index', 'icon' => 'check'],
    ];
    $insightsNav = [
        ['label' => 'Reports & Analytics', 'route' => 'reports.index', 'icon' => 'chart'],
        ['label' => 'LGA & Ward Map',      'route' => 'map.index',     'icon' => 'map'],
    ];
    $systemNav = [
        ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'gear'],
    ];

    $icons = [
        'grid'  => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'bars'  => '<path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>',
        'check' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'map'   => '<path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><path d="M16 8L2 22"/><path d="M17.5 15H9"/>',
        'gear'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
    ];

    // route() throws if a route name doesn't exist yet -- fall back to '#'
    // so this partial still renders while you're wiring up the other pages.
    $navHref = fn ($name) => \Illuminate\Support\Facades\Route::has($name) ? route($name) : '#';
    $navActive = fn ($name) => \Illuminate\Support\Facades\Route::has($name) && request()->routeIs($name);
@endphp

<aside class="sidebar" id="sidebar">
    <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
    <div class="sidebar-brand">
        <div class="brand-mark">CB</div>
        <div class="brand-text">
            <b>CBM Ondo</b>
            <span>City Boys Movement</span>
        </div>
    </div>

    <nav class="nav-scroll">
        <div class="nav-label">MAIN</div>
        @foreach ($mainNav as $item)
            <a href="{{ $navHref($item['route']) }}" class="nav-item {{ $navActive($item['route']) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$item['icon']] !!}</svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        {{-- <div class="nav-label">INSIGHTS</div>
        @foreach ($insightsNav as $item)
            <a href="{{ $navHref($item['route']) }}" class="nav-item {{ $navActive($item['route']) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$item['icon']] !!}</svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        <div class="nav-label">SYSTEM</div>
        @foreach ($systemNav as $item)
            <a href="{{ $navHref($item['route']) }}" class="nav-item {{ $navActive($item['route']) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$item['icon']] !!}</svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : '#' }}">
            @csrf
            <button type="submit" class="nav-item" style="width:100%;border:none;background:none;text-align:left;cursor:pointer;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </button>
        </form> --}}
    </nav>

    <div class="sidebar-foot">CBM Ondo Admin Portal<br>v1.0 &middot; Ondo State</div>
</aside>
