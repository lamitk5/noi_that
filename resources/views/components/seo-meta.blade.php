@props([
    'title' => 'Mộc An | Nội Thất Gỗ Tự Nhiên Hiện Đại',
    'description' => 'Mộc An - Chuyên cung cấp bàn ghế, sofa, tủ kệ từ gỗ tự nhiên cao cấp, phong cách tối giản và ấm cúng cho ngôi nhà Việt.',
    'image' => null,
    'url' => null,
    'type' => 'website',
    'robots' => null,
    'schema' => null,
])

@php
    $canonicalUrl = $url ?? url()->current();
    $defaultImage = asset('images/hero.jpg') ?: 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?auto=format&fit=crop&w=1200&q=80';
    $ogImage = $image ?: $defaultImage;
    $siteName = 'Mộc An Nội Thất';

    // Check private routes or explicit robots prop
    $isPrivate = request()->is('gio-hang*', 'thanh-toan*', 'tai-khoan*', 'admin*', 'api*');
    $robotsMeta = $robots ?? ($isPrivate ? 'noindex, nofollow' : 'index, follow');
@endphp

<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robotsMeta }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $ogImage }}">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">

@if (!empty($schema))
<script type="application/ld+json">
{!! is_array($schema) ? json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : $schema !!}
</script>
@endif
