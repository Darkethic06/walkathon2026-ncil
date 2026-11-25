{{--
    Server-rendered SEO tags (from the shared `seo` Inertia prop, see App\Support\Seo).
    The `inertia="<key>"` attributes must match the head-keys in
    resources/js/Components/SeoHead.vue: on hydration Inertia's head manager
    replaces these with the Vue-managed copies, so tags stay in sync on
    client-side navigation without being duplicated.
--}}
<title inertia>{{ $seo['title'] }}</title>
<meta inertia="description" name="description" content="{{ $seo['description'] }}">
<meta inertia="robots" name="robots" content="{{ $seo['robots'] }}">
<link inertia="canonical" rel="canonical" href="{{ $seo['canonical'] }}">
<meta inertia="og:type" property="og:type" content="{{ $seo['og_type'] }}">
<meta inertia="og:site_name" property="og:site_name" content="{{ $seo['site_name'] }}">
<meta inertia="og:locale" property="og:locale" content="{{ $seo['locale'] }}">
<meta inertia="og:title" property="og:title" content="{{ $seo['title'] }}">
<meta inertia="og:description" property="og:description" content="{{ $seo['description'] }}">
<meta inertia="og:url" property="og:url" content="{{ $seo['canonical'] }}">
<meta inertia="og:image" property="og:image" content="{{ $seo['image'] }}">
<meta inertia="twitter:card" name="twitter:card" content="summary_large_image">
<meta inertia="twitter:title" name="twitter:title" content="{{ $seo['title'] }}">
<meta inertia="twitter:description" name="twitter:description" content="{{ $seo['description'] }}">
<meta inertia="twitter:image" name="twitter:image" content="{{ $seo['image'] }}">
