<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', 'Coordinator-agent — CBM Ondo')</title>
    <link rel="stylesheet" href="{{ asset('css/m.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-refresh.css') }}">
    <link rel="stylesheet" href="{{ asset('css/coordinator.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}">
</head>
<body class="workspace-body coordinator-body {{ auth('coordinator')->check() ? 'coordinator-workspace' : '' }}">
    <a class="workspace-skip-link" href="#workspace-content">Skip to content</a>
    <header class="coordinator-header">
        <a href="{{ auth()->user() instanceof \App\Models\User ? route(auth()->user()->hasPermission('import-voters') ? 'voters.import.create' : 'voters.index') : (auth('coordinator')->check() ? route('coordinator.dashboard') : route('new.homepage')) }}" class="coordinator-brand"><img src="{{ asset('assets/logo-cityboy.png') }}" alt="CBM Ondo"><span>PU CONNECT <small>360</small><br><small>{{ auth()->user() instanceof \App\Models\User ? 'Staff workspace' : 'Coordinator workspace' }}</small></span></a>
        @if(auth()->user() instanceof \App\Models\User)
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Log out</button></form>
        @elseif(auth('coordinator')->check())
            <form method="POST" action="{{ route('coordinator.logout') }}">
                @csrf
                <button class="btn btn-outline" type="submit">Log out</button>
            </form>
        @endif
    </header>
    @auth('coordinator')
        <nav class="coordinator-nav" aria-label="Coordinator navigation">
            @foreach (['dashboard' => 'Home', 'register' => 'Voter register', 'profile' => 'My profile'] as $page => $label)
                <a href="{{ route('coordinator.'.$page) }}" @if(request()->routeIs('coordinator.'.$page) || ($page === 'register' && request()->routeIs('voters.*'))) aria-current="page" @endif><span class="coordinator-nav-icon" aria-hidden="true">{{ ['dashboard' => '⌂', 'register' => '▤', 'profile' => '◉'][$page] }}</span><span>{{ $label }}</span></a>
            @endforeach
        </nav>
    @endauth
    <main class="coordinator-main" id="workspace-content" tabindex="-1">
        @if(session('status'))
            <div class="member-notice" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
