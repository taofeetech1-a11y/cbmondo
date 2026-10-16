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
</head>
<body class="coordinator-body">
    <header class="coordinator-header">
        <a href="{{ route('new.homepage') }}" class="coordinator-brand"><img src="{{ asset('assets/logo-cityboy.png') }}" alt="CBM Ondo"><span>CBM Ondo<br><small>Unit coordinator-agent</small></span></a>
        @auth('coordinator')
            <form method="POST" action="{{ route('coordinator.logout') }}">
                @csrf
                <button class="btn btn-outline" type="submit">Log out</button>
            </form>
        @endauth
    </header>
    <main class="coordinator-main">
        @if(session('status'))
            <div class="member-notice" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
