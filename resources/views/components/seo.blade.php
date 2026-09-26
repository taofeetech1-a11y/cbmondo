@props(['page'])
@php
    $metadata = config('seo.pages')[$page];
    $base = rtrim(config('app.url'), '/');
    $canonical = $base.$metadata['path'];
    $logo = $base.'/assets/logo-cityboy.png';
@endphp
<title>{{ $metadata['title'] }}</title>
<meta name="description" content="{{ $metadata['description'] }}">
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="City Boy Movement Ondo State">
<meta property="og:locale" content="en_NG">
<meta property="og:title" content="{{ $metadata['title'] }}">
<meta property="og:description" content="{{ $metadata['description'] }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $logo }}">
<meta property="og:image:alt" content="City Boy Movement logo">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $metadata['title'] }}">
<meta name="twitter:description" content="{{ $metadata['description'] }}">
<meta name="twitter:image" content="{{ $logo }}">
<meta name="twitter:image:alt" content="City Boy Movement logo">
@if (config('seo.verification'))
<meta name="google-site-verification" content="{{ config('seo.verification') }}">
@endif
@if ($page === 'new.homepage')
@php
    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $base.'/#organization',
                'name' => 'City Boy Movement Ondo State',
                'alternateName' => 'CBM Ondo',
                'url' => $base.'/',
                'logo' => $logo,
                'description' => $metadata['description'],
                'areaServed' => ['@type' => 'AdministrativeArea', 'name' => 'Ondo State, Nigeria'],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $base.'/#website',
                'name' => 'City Boy Movement Ondo State',
                'alternateName' => 'CBM Ondo',
                'url' => $base.'/',
                'inLanguage' => 'en',
                'publisher' => ['@id' => $base.'/#organization'],
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($structuredData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}</script>
@endif
