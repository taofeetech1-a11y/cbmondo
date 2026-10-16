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
</head>
<body class="coordinator-body {{ auth('coordinator')->check() ? 'coordinator-workspace' : '' }}">
    <header class="coordinator-header">
        <a href="{{ auth('coordinator')->check() ? route('coordinator.dashboard') : route('new.homepage') }}" class="coordinator-brand"><img src="{{ asset('assets/logo-cityboy.png') }}" alt="CBM Ondo"><span>PU CONNECT <small>360</small><br><small>Coordinator workspace</small></span></a>
        @auth('coordinator')
            <form method="POST" action="{{ route('coordinator.logout') }}">
                @csrf
                <button class="btn btn-outline" type="submit">Log out</button>
            </form>
        @endauth
    </header>
    @auth('coordinator')
        <nav class="coordinator-nav" aria-label="Coordinator navigation">
            @foreach (['dashboard' => 'Home', 'register' => 'Voter register', 'profile' => 'My profile'] as $page => $label)
                <a href="{{ route('coordinator.'.$page) }}" @if(request()->routeIs('coordinator.'.$page)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    @endauth
    <main class="coordinator-main">
        @if(session('status'))
            <div class="member-notice" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
