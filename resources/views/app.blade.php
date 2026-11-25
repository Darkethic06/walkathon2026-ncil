<!DOCTYPE html>
<html lang="en-IN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SSR (when its Node server is running) renders the head from Vue; otherwise Blade renders the SEO tags. --}}
    @inertiaHead
    @if (empty($__inertiaSsrResponse))
        @include('partials.seo', ['seo' => $page['props']['seo'] ?? app(\App\Support\Seo::class)->toArray(request())])
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;1,600;1,700&family=Montserrat:wght@700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
</head>

<body>
    @inertia
</body>

</html>
