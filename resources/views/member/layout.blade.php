<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>@yield('title', 'Member access — CBM Ondo')</title>
    <link rel="stylesheet" href="{{ asset('css/m.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-refresh.css') }}">
    <link rel="stylesheet" href="{{ asset('css/coordinator.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}">
</head>
<body class="workspace-body coordinator-body">
    <a class="workspace-skip-link" href="#workspace-content">Skip to content</a>
    <header class="coordinator-header">
        <a class="coordinator-brand" href="{{ route('new.homepage') }}"><img src="{{ asset('assets/logo-cityboy.png') }}" alt="CBM Ondo"><span>CBM ONDO<br><small>Member space</small></span></a>
        @auth('member')
            <form method="POST" action="{{ route('member.logout') }}">@csrf<button class="btn btn-outline" type="submit">Log out</button></form>
        @endauth
    </header>
    <main class="coordinator-main" id="workspace-content" tabindex="-1">
        @if(session('status'))<div class="member-notice" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="member-notice member-error" role="alert">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</body>
</html>
