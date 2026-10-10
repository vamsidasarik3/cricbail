@inject('seo', 'App\Services\SeoService')

@php
    $rawTitle = trim($__env->yieldContent('title'));
    $pageTitle = $seo->title($rawTitle);
    $pageDescription = $seo->description(trim($__env->yieldContent('meta_description')));
    $customCanonical = trim($__env->yieldContent('canonical_url'));
    $pageCanonical = $customCanonical ? $seo->canonicalUrl($customCanonical) : $seo->canonicalUrl();
    $pageRobots = trim($__env->yieldContent('robots')) ?: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
    $ogImage = $seo->socialImage(trim($__env->yieldContent('og_image')));
    $articlePublishedTime = trim($__env->yieldContent('article_published_time'));
    $articleModifiedTime = trim($__env->yieldContent('article_modified_time'));
    $articleAuthor = trim($__env->yieldContent('article_author'));
    $articleSection = trim($__env->yieldContent('article_section'));
@endphp

<!-- Primary Meta Tags -->
<title>{{ $pageTitle }}</title>
<meta name="title" content="{{ $pageTitle }}">
<meta name="description" content="{{ $pageDescription }}">
<meta name="robots" content="{{ $pageRobots }}">
<link rel="canonical" href="{{ $pageCanonical }}">

<!-- Open Graph / Facebook -->
<meta property="og:site_name" content="{{ config('seo.site_name', 'CricBail') }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $pageCanonical }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $pageTitle }}">
<meta property="og:locale" content="en_US">

@if($ogType === 'article')
@if($articlePublishedTime)
<meta property="article:published_time" content="{{ $articlePublishedTime }}">
@endif
@if($articleModifiedTime)
<meta property="article:modified_time" content="{{ $articleModifiedTime }}">
@endif
@if($articleAuthor)
<meta property="article:author" content="{{ $articleAuthor }}">
@endif
@if($articleSection)
<meta property="article:section" content="{{ $articleSection }}">
@endif
@endif

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="{{ config('seo.twitter_handle', '@CricBail') }}">
<meta name="twitter:creator" content="{{ config('seo.twitter_handle', '@CricBail') }}">
<meta name="twitter:url" content="{{ $pageCanonical }}">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $pageTitle }}">

<!-- Favicons & Touch Icons -->
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">

<!-- Search Engine Site Verification -->
@if(config('seo.google_site_verification'))
<meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
@endif
@if(config('seo.bing_site_verification'))
<meta name="msvalidate.01" content="{{ config('seo.bing_site_verification') }}">
@endif

<!-- Structured Data (JSON-LD) -->
@stack('schema')
