<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', 'Unit workspace — CBM Ondo')</title>
    <link rel="stylesheet" href="{{ asset('css/m.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-refresh.css') }}">
    <link rel="stylesheet" href="{{ asset('css/voters.css') }}">
    <link rel="stylesheet" href="{{ asset('css/coordinator-canvass.css') }}">
</head>
<body class="canvass-body">
    <a class="canvass-skip" href="#workspace-content">Skip to content</a>
    <header class="canvass-header"><div class="canvass-header-inner">
        @hasSection('back-url')<a class="canvass-header-action" href="@yield('back-url')" aria-label="Back"><x-canvass-icon name="back"/></a>@else<span class="canvass-header-spacer" aria-hidden="true"></span>@endif
        <h1>@yield('screen-title', 'Dashboard')</h1>
        <details class="canvass-menu"><summary aria-label="Open workspace navigation"><x-canvass-icon name="menu"/></summary>
            <nav aria-label="Coordinator navigation">
                @foreach(['dashboard' => 'Dashboard', 'register' => 'Polling unit register', 'memberships' => 'Ward memberships', 'contributions' => 'My contributions', 'profile' => 'My profile'] as $page => $label)
                    <a href="{{ route('coordinator.'.$page) }}" @if(request()->routeIs('coordinator.'.$page)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
                <form method="POST" action="{{ route('coordinator.logout') }}">@csrf<button type="submit">Log out</button></form>
            </nav>
        </details>
    </div></header>
    <main class="canvass-main" id="workspace-content" tabindex="-1">
        @if(session('status'))<div class="canvass-notice" role="status">{{ session('status') }}</div>@endif
        <div class="voter-print-notice">Confidential voter information. Printing is restricted.</div>
        <div class="voter-private" data-watermark="CONFIDENTIAL · Account #{{ auth()->id() }}">@yield('content')</div>
    </main>
</body>
</html>
