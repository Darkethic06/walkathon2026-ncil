<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ])->redirectGuestsTo(fn () => route('admin.login'));

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            // Unmatched URLs skip the web middleware, so HandleInertiaRequests
            // never shares `seo` here — pass it explicitly.
            $seo = app(\App\Support\Seo::class)->set([
                'title' => 'Page Not Found | Annual Walkathon 2026',
                'description' => "The page you're looking for doesn't exist or has moved.",
                'robots' => 'noindex,nofollow',
            ])->toArray($request);

            return Inertia::render('Frontend/Errors/NotFound', [
                'seo' => $seo,
                'ziggy' => config('inertia.ssr.enabled')
                    ? array_merge((new \Tighten\Ziggy\Ziggy)->toArray(), ['location' => $request->url()])
                    : null,
            ])
                ->toResponse($request)
                ->setStatusCode(404);
        });
    })->create();
