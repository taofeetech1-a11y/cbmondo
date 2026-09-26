<!DOCTYPE html>
<html lang="en">

<head>
	    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <meta name="description"
        content="City Boy Movement - Membership Dashboard">

    <meta name="keywords"
        content="City Boy Movement, City Boy Movement Ondo State, CBM Ondo, CBM Nigeria, City Boy Ondo, Ondo State movement, City Boy registration, CBM registration">

    <meta name="author" content="Taofeeq Olatigbe">

    <meta name="robots" content="noindex, nofollow, noarchive">

	<meta name="csrf-token" content="{{ csrf_token() }}">



    <!-- Favicon -->
    <link rel="shortcut icon"
        href="{{ asset('assets/logo-cityboy.png') }}"
        type="image/x-icon">
    <title>@yield('title', 'CBM Ondo — Admin Portal')</title>

    <link rel="stylesheet" href="{{ asset('css/m.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-refresh.css') }}">
    @stack('styles')
</head>

<body>

    <div class="shell">

        @include('dashboard.partials.sidebar')
        {{-- @include('partials.sidebar') --}}

        <div class="backdrop" id="backdrop"></div>

        <!-- Main -->
        <div class="main">
            @include('dashboard.partials.topbar')

            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js"
        integrity="sha512-8LENNbXmzI/Gbj+OwXmqR6V4QaUAw0/porPzy1+dQoJqC0JPHedWoe0DDOTL2uHA5XXJyIsPtiMHH86pVlay6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <script src="{{ asset('js/a.js') }}" type="module"></script>
    @stack('scripts')
</body>

</html>
