<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
                'role_name' => auth()->user()->role_name ?? null,
                // What the admin panel UI may show; the routes enforce the same rules.
                'is_admin' => (bool) $request->user()?->hasAnyRole(\App\Support\Roles::ADMINS),
                'can_manage_sub_users' => (bool) $request->user()?->hasRole(\App\Support\Roles::SUPER_ADMIN),
            ],
            'flash' => function () use ($request) {
                return [
                    'form_id' => $request->session()->get('form_id'),
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                ];
            },
            'showingMobileMenu' => false,
            // Resolved lazily so controllers can call Seo::set() before render.
            'seo' => fn () => app(\App\Support\Seo::class)->toArray($request),
            // Only needed by the SSR renderer (resources/js/ssr.js) for route().
            'ziggy' => fn () => config('inertia.ssr.enabled')
                ? array_merge((new \Tighten\Ziggy\Ziggy)->toArray(), ['location' => $request->url()])
                : null,
        ]);
    }
}
