@php
    $appName = config('app.name', 'Pick Me Retailers');
    $siteUrl = rtrim(config('app.url'), '/');

    $title = ($seo['title'] ?? null)
        ? (str_contains($seo['title'], $appName) ? $seo['title'] : $seo['title'] . ' | ' . $appName)
        : $appName . ' | Shop Retail & Bulk Online';

    $description = $seo['description'] ?? 'Pick Me Retailers is an online shopping platform where you can order everyday products at retail prices or in bulk at resale prices. Shop online with fast, reliable delivery right to your door.';

    $type = $seo['type'] ?? 'website';

    $image = $seo['image'] ?? ($type === 'product' ? null : $siteUrl . '/frontend/images/hero-main.png');
    if ($image && !preg_match('#^https?://#', $image)) {
        $image = $siteUrl . '/' . ltrim($image, '/');
    }

    $url = url()->current();
    $robots = $seo['robots'] ?? 'index, follow';
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $url }}">

<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $url }}">
@if ($image)
    <meta property="og:image" content="{{ $image }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $image }}">
@else
    <meta name="twitter:card" content="summary">
@endif
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
