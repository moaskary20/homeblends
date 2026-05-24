@php
    $seo = $seo ?? app(\App\Services\Seo\SeoService::class)->defaults();
@endphp

@if($seo->description)
    <meta name="description" content="{{ $seo->description }}">
@endif

@if($seo->robots)
    <meta name="robots" content="{{ $seo->robots }}">
@endif

@if($seo->canonical)
    <link rel="canonical" href="{{ $seo->canonical }}">
@endif

@if($seo->googleVerification)
    <meta name="google-site-verification" content="{{ $seo->googleVerification }}">
@endif

<meta property="og:locale" content="ar_EG">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ $seo->ogTitle() }}">
@if($seo->ogDescription())
    <meta property="og:description" content="{{ $seo->ogDescription() }}">
@endif
@if($seo->ogUrl)
    <meta property="og:url" content="{{ $seo->ogUrl }}">
@endif
@if($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
    <meta property="og:image:alt" content="{{ $seo->ogTitle() }}">
@endif

@if($seo->twitterCard)
    <meta name="twitter:card" content="{{ $seo->twitterCard }}">
@endif
@if($seo->twitterSite)
    <meta name="twitter:site" content="{{ $seo->twitterSite }}">
@endif
<meta name="twitter:title" content="{{ $seo->ogTitle() }}">
@if($seo->ogDescription())
    <meta name="twitter:description" content="{{ $seo->ogDescription() }}">
@endif
@if($seo->ogImage)
    <meta name="twitter:image" content="{{ $seo->ogImage }}">
@endif

@foreach($seo->schema as $graph)
    <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach

@stack('meta')
