@props(['page', 'eyebrow', 'title', 'intro'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <x-seo :page="$page" />
    <link rel="icon" href="{{ asset('assets/logo-cityboy.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/public-pages.css') }}">
</head>
<body class="public-page">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="public-header">
        <a class="public-brand" href="{{ route('new.homepage') }}"><img src="{{ asset('assets/logo-cityboy.png') }}" alt="" width="56" height="56"><span>City Boy Movement<small>Ondo State</small></span></a>
        <nav class="public-nav" aria-label="Main navigation">
            @foreach (['new.homepage' => 'Home', 'about.page' => 'About', 'updates.page' => 'Updates', 'contact.page' => 'Contact', 'support.page' => 'Support'] as $name => $label)
                <a href="{{ route($name) }}" @if ($page === $name) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </header>
    <main id="main-content">
        <section class="public-hero"><div class="public-container"><p class="public-eyebrow">{{ $eyebrow }}</p><h1>{{ $title }}</h1><p class="public-intro">{{ $intro }}</p></div></section>
        <div class="public-container public-content">{{ $slot }}</div>
    </main>
    <footer class="public-footer"><div class="public-container"><strong>City Boy Movement · Ondo State</strong><p>Inform. Engage. Empower.</p><nav aria-label="Footer navigation"><a href="{{ route('about.page') }}">About</a><a href="{{ route('contact.page') }}">Contact</a><a href="{{ route('updates.page') }}">Updates</a><a href="{{ route('new.homepage') }}#reg">Membership registration</a></nav></div></footer>
</body>
</html>
