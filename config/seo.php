<?php

/*
|--------------------------------------------------------------------------
| SEO metadata
|--------------------------------------------------------------------------
|
| Single source of truth for per-page <title>, description, robots and OG
| image. Resolved by App\Support\Seo (keyed by route name), shared to every
| Inertia page as the `seo` prop, and rendered server-side by
| resources/views/partials/seo.blade.php so crawlers get it without JS.
|
*/

return [

    // Canonical host. Every canonical / og:url is built from this, never from
    // the request host.
    'base_url' => rtrim(env('SEO_BASE_URL', env('APP_URL', 'http://localhost')), '/'),

    'site_name' => 'NephroCare Annual Walkathon',

    'locale' => 'en_IN',

    'defaults' => [
        'title' => 'Annual Walkathon 2026 | Walk for Kidney, Walk for Health',
        'description' => 'Join the 4 km Annual Walkathon 2026 by Nephro Care India Ltd. with Vivacity Multispeciality Hospital. Sunday, 13 December 2026, Salt Lake, Kolkata.',
        'image' => '/images/hero-photo.jpg',
        'robots' => 'index,follow',
        'og_type' => 'website',
    ],

    // Keyed by route name.
    'pages' => [
        'frontend.home' => [],

        'frontend.privacy' => [
            'title' => 'Privacy Policy | Annual Walkathon 2026',
            'description' => 'Privacy Policy for the Annual Walkathon 2026 by NephroCare in association with Vivacity Multispeciality Hospital.',
        ],

        // Personal page (bib + QR): never indexed.
        'frontend.thankyou' => [
            'title' => 'You are registered! | Annual Walkathon 2026',
            'description' => 'Your bib number and QR code for the Annual Walkathon 2026.',
            'robots' => 'noindex,nofollow',
        ],

        // Admin routes are forced to noindex,nofollow in App\Support\Seo.
        'admin.login' => [
            'title' => 'Admin Login | Annual Walkathon 2026',
            'description' => 'Walkathon admin panel.',
            'robots' => 'noindex,nofollow',
        ],
    ],
];
